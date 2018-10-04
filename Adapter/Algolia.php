<?php

namespace Algolia\AlgoliaSearch\Adapter;

use Algolia\AlgoliaSearch\Helper\ConfigHelper;
use Algolia\AlgoliaSearch\Helper\Data as AlgoliaHelper;
use AlgoliaSearch\AlgoliaConnectionException;
use Magento\CatalogSearch\Helper\Data;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Registry;
use Magento\Framework\Search\Adapter\Mysql\Aggregation\Builder as AggregationBuilder;
use Magento\Framework\Search\Adapter\Mysql\DocumentFactory;
use Magento\Framework\Search\Adapter\Mysql\Mapper;
use Magento\Framework\Search\Adapter\Mysql\ResponseFactory;
use Magento\Framework\Search\Adapter\Mysql\TemporaryStorageFactory;
use Magento\Framework\Search\AdapterInterface;
use Magento\Framework\Search\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Algolia Search Adapter
 */
class Algolia implements AdapterInterface
{
    /** @var Mapper */
    private $mapper;

    /** @var ResponseFactory */
    private $responseFactory;

    /** @var ResourceConnection */
    private $resource;

    /** @var AggregationBuilder */
    private $aggregationBuilder;

    /** @var TemporaryStorageFactory */
    private $temporaryStorageFactory;

    /** @var ConfigHelper */
    private $config;

    /** @var Data */
    private $catalogSearchHelper;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var Registry */
    private $registry;

    /** @var AlgoliaHelper */
    private $algoliaHelper;

    /** @var Http */
    private $request;

    /** @var DocumentFactory */
    private $documentFactory;

    /**
     * @param Mapper $mapper
     * @param ResponseFactory $responseFactory
     * @param ResourceConnection $resource
     * @param AggregationBuilder $aggregationBuilder
     * @param TemporaryStorageFactory $temporaryStorageFactory
     * @param ConfigHelper $config
     * @param Data $catalogSearchHelper
     * @param StoreManagerInterface $storeManager
     * @param Registry $registry
     * @param AlgoliaHelper $algoliaHelper
     * @param Http $request
     * @param DocumentFactory $documentFactory
     */
    public function __construct(
        Mapper $mapper,
        ResponseFactory $responseFactory,
        ResourceConnection $resource,
        AggregationBuilder $aggregationBuilder,
        TemporaryStorageFactory $temporaryStorageFactory,
        ConfigHelper $config,
        Data $catalogSearchHelper,
        StoreManagerInterface $storeManager,
        Registry $registry,
        AlgoliaHelper $algoliaHelper,
        Http $request,
        DocumentFactory $documentFactory
    ) {
        $this->mapper = $mapper;
        $this->responseFactory = $responseFactory;
        $this->resource = $resource;
        $this->aggregationBuilder = $aggregationBuilder;
        $this->temporaryStorageFactory = $temporaryStorageFactory;
        $this->config = $config;
        $this->catalogSearchHelper = $catalogSearchHelper;
        $this->storeManager = $storeManager;
        $this->registry = $registry;
        $this->algoliaHelper = $algoliaHelper;
        $this->request = $request;
        $this->documentFactory = $documentFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function query(RequestInterface $request)
    {
        $storeId = $this->storeManager->getStore()->getId();

        if (!$this->isAllowed($storeId)
            || !($this->isSearch() ||
                $this->isReplaceCategory($storeId) ||
                $this->isReplaceAdvancedSearch($storeId))
        ) {
            return $this->nativeQuery($request);
        }

        $query = $this->catalogSearchHelper->getEscapedQueryText();
        $temporaryStorage = $this->temporaryStorageFactory->create();
        $documents = [];
        $table = null;

        try {
            $algoliaQuery = $query !== '__empty__' ? $query : '';

            // If instant search is on, do not make a search query unless SEO request is set to 'Yes'
            if (!$this->config->isInstantEnabled($storeId) || $this->config->makeSeoRequest($storeId)) {

                $contextParams = [];

                if ($this->isReplaceCategory($storeId)) {
                    $category = $this->registry->registry('current_category');
                    $page = !is_null($this->request->getParam('page')) ?
                        (int) $this->request->getParam('page') - 1 :
                        0;

                    if ($category) {
                        $contextParams = [
                            'facetFilters' => [
                                'categoryIds:' . $category->getEntityId(),
                            ],
                            'page' => $page
                        ];
                    }
                }

                $documents = $this->algoliaHelper->getSearchResult($algoliaQuery, $storeId, $contextParams);
            }

            $apiDocuments = array_map(array($this, 'getAlgoliaDocument'), $documents);
            $table = $temporaryStorage->storeApiDocuments($apiDocuments);

        } catch (AlgoliaConnectionException $e) {
            $this->nativeQuery($request);
        }

        $aggregations = $this->aggregationBuilder->build($request, $table, $documents);
        $response = [
            'documents' => $documents,
            'aggregations' => $aggregations,
        ];

        return $this->responseFactory->create($response);
    }

    private function nativeQuery(RequestInterface $request)
    {
        $query = $this->mapper->buildQuery($request);
        $temporaryStorage = $this->temporaryStorageFactory->create();
        $table = $temporaryStorage->storeDocumentsFromSelect($query);

        $documents = $this->getDocuments($table);

        $aggregations = $this->aggregationBuilder->build($request, $table, $documents);
        $response = [
            'documents' => $documents,
            'aggregations' => $aggregations,
        ];
        return $this->responseFactory->create($response);
    }

    private function getAlgoliaDocument($document)
    {
        return $this->documentFactory->create($document);
    }

    /**
     * Checks if Algolia is properly configured and enabled
     *
     * @param int $storeId
     *
     * @return bool
     */
    private function isAllowed($storeId)
    {
        return
            $this->config->getApplicationID($storeId)
            && $this->config->getAPIKey($storeId)
            && $this->config->isEnabledFrontEnd($storeId)
            && $this->config->makeSeoRequest($storeId);
    }

    /** @return bool */
    private function isSearch()
    {
        return $this->request->getFullActionName() === 'catalogsearch_result_index';
    }

    /**
     * Checks if Algolia should replace category results
     *
     * @param  int     $storeId
     *
     * @return bool
     */
    private function isReplaceCategory($storeId)
    {
        return
            $this->request->getControllerName() === 'category'
            && $this->config->replaceCategories($storeId) === true
            && $this->config->isInstantEnabled($storeId) === true;
    }

    /**
     * Checks if Algolia should replace advanced search results
     *
     * @param  int      $storeId
     *
     * @return bool
     */
    private function isReplaceAdvancedSearch($storeId)
    {
        return
            $this->request->getFullActionName() === 'catalogsearch_advanced_result'
            && $this->config->isInstantEnabled($storeId) === true;
    }

    /**
     * Executes query and return raw response
     *
     * @param Table $table
     *
     * @throws \Zend_Db_Exception
     *
     * @return array
     *
     */
    private function getDocuments(Table $table)
    {
        $connection = $this->getConnection();
        $select = $connection->select();
        $select->from($table->getName(), ['entity_id', 'score']);

        return $connection->fetchAssoc($select);
    }

    /** @return \Magento\Framework\DB\Adapter\AdapterInterface */
    private function getConnection()
    {
        return $this->resource->getConnection();
    }
}
