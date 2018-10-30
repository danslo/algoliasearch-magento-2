<?php

namespace Algolia\AlgoliaSearch\Block\Adminhtml\Analytics;

use Algolia\AlgoliaSearch\Helper\AlgoliaHelper;
use Algolia\AlgoliaSearch\Helper\ConfigHelper;
use Algolia\AlgoliaSearch\Helper\AnalyticsHelper;
use Algolia\AlgoliaSearch\Helper\Data;
use Algolia\AlgoliaSearch\Helper\Entity\ProductHelper;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

class Index extends Template
{
    const LIMIT_RESULTS = 5;

    /** @var Context */
    private $backendContext;

    /** @var AlgoliaHelper */
    private $algoliaHelper;

    /** @var ConfigHelper */
    private $configHelper;

    /** @var AnalyticsHelper */
    private $analyticsHelper;

    /** @var Data */
    private $dataHelper;

    /** @var Product */
    private $productHelper;

    /**
     * Index constructor.
     * @param Context $context
     * @param AlgoliaHelper $algoliaHelper
     * @param ConfigHelper $configHelper
     * @param AnalyticsHelper $analyticsHelper
     * @param ProductHelper $productHelper
     * @param Data $dataHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        AlgoliaHelper $algoliaHelper,
        ConfigHelper $configHelper,
        AnalyticsHelper $analyticsHelper,
        ProductHelper $productHelper,
        Data $dataHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->backendContext = $context;
        $this->algoliaHelper = $algoliaHelper;
        $this->configHelper = $configHelper;
        $this->dataHelper = $dataHelper;
        $this->productHelper = $productHelper;
        $this->analyticsHelper = $analyticsHelper;
    }

    /**
     * @return string
     */
    public function getIndexName()
    {
        return $this->dataHelper->getIndexName($this->productHelper->getIndexNameSuffix(), $this->getStore()->getId());
    }

    /**
     * @param array $additional
     * @return array
     */
    public function getAnalyticsParams($additional = array())
    {
        $params = array('index' => $this->getIndexName());

        if ($formData = $this->_backendSession->getAlgoliaAnalyticsFormData()) {
            if (isset($formData['from']) && $formData['from'] !== '') {
                $params['startDate'] = $this->backendContext->getLocaleDate()->convertConfigTimeToUtc($formData['from'],
                    'Y-m-d');
            }
            if (isset($formData['to']) && $formData['to'] !== '') {
                $params['endDate'] = $this->backendContext->getLocaleDate()->convertConfigTimeToUtc($formData['to'],
                    'Y-m-d');
            }
        }

        return array_merge($params, $additional);
    }

    public function getTopSearches()
    {
        $topSearches = $this->analyticsHelper->getTopSearches($this->getAnalyticsParams());
        return isset($topSearches['searches']) ? array_slice($topSearches['searches'], 0, self::LIMIT_RESULTS) : array();
    }

    public function getNoResultSearches()
    {
        $noResults = $this->analyticsHelper->getTopSearchesNoResults($this->getAnalyticsParams());
        return isset($noResults['searches']) ? array_slice($noResults['searches'], 0, self::LIMIT_RESULTS) : array();
    }

    /**
     * @return \Magento\Store\Api\Data\StoreInterface|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStore()
    {
        $storeManager = $this->backendContext->getStoreManager();
        if ($storeId = $this->getRequest()->getParam('store')) {
            return $storeManager->getStore($storeId);
        }

        return $storeManager->getDefaultStoreView();
    }
}
