<?php

namespace Algolia\AlgoliaSearch\Block\Adminhtml\Analytics;

use Algolia\AlgoliaSearch\Helper\ConfigHelper;
use Algolia\AlgoliaSearch\Helper\AnalyticsHelper;
use Algolia\AlgoliaSearch\Helper\Data;
use Algolia\AlgoliaSearch\Helper\Entity\ProductHelper;
use Algolia\AlgoliaSearch\Helper\Entity\CategoryHelper;
use Algolia\AlgoliaSearch\Helper\Entity\PageHelper;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class Index extends Template
{
    const LIMIT_RESULTS = 5;
    const DEFAULT_TYPE = 'products';

    /** @var Context */
    private $backendContext;

    /** @var ConfigHelper */
    private $configHelper;

    /** @var AnalyticsHelper */
    private $analyticsHelper;

    /** @var Data */
    private $dataHelper;

    /** @var Product */
    private $productHelper;

    /** @var CategoryHelper */
    private $categoryHelper;

    /** @var PageHelper */
    private $pageHelper;

    /** @var TimezoneInterface */
    private $dateTime;

    /** @var CollectionFactory */
    private $productCollection;

    protected $_analyticsParams = array();

    /** Cache variables to prevent excessive calls */
    protected $_searches;
    protected $_users;
    protected $_rateOfNoResults;

    /**
     * Index constructor.
     * @param Context $context
     * @param ConfigHelper $configHelper
     * @param AnalyticsHelper $analyticsHelper
     * @param ProductHelper $productHelper
     * @param CategoryHelper $categoryHelper
     * @param PageHelper $pageHelper
     * @param Data $dataHelper
     * @param TimezoneInterface $dateTime
     * @param CollectionFactory $productCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        AnalyticsHelper $analyticsHelper,
        ProductHelper $productHelper,
        CategoryHelper $categoryHelper,
        PageHelper $pageHelper,
        Data $dataHelper,
        TimezoneInterface $dateTime,
        CollectionFactory $productCollection,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->backendContext = $context;
        $this->configHelper = $configHelper;
        $this->dataHelper = $dataHelper;
        $this->productHelper = $productHelper;
        $this->categoryHelper = $categoryHelper;
        $this->pageHelper = $pageHelper;
        $this->analyticsHelper = $analyticsHelper;
        $this->dateTime = $dateTime;
        $this->productCollection = $productCollection;
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getIndexName()
    {
        $sections = $this->getSections();
        return $sections[$this->getCurrentType()];
    }

    /**
     * @param array $additional
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAnalyticsParams($additional = array())
    {
        if (!count($this->_analyticsParams)) {

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

            $this->_analyticsParams = $params;
        }

        return array_merge($this->_analyticsParams, $additional);
    }

    public function getSearches()
    {
        if (!$this->_searches) {
            $this->_searches = $this->analyticsHelper->getCountOfSearches($this->getAnalyticsParams());
        }
        return $this->_searches;
    }

    public function getTotalCountOfSearches()
    {
        $searches = $this->getSearches();
        return $searches && isset($searches['count']) ? $searches['count'] : 0;
    }

    public function getSearchesByDates()
    {
        $searches = $this->getSearches();
        return $searches && isset($searches['dates']) ? $searches['dates'] : array();
    }

    public function getUsers()
    {
        if (!$this->_users) {
            $this->_users = $this->analyticsHelper->getUserCount($this->getAnalyticsParams());
        }
        return $this->_users;
    }

    public function getTotalUsersCount()
    {
        $users = $this->getUsers();
        return $users && isset($users['count']) ? $users['count'] : 0;
    }

    public function getUsersCountByDates()
    {
        $users = $this->getUsers();
        return $users && isset($users['dates']) ? $users['dates'] : array();
    }

    public function getRateOfNoResults()
    {
        if (!$this->_rateOfNoResults) {
            $this->_rateOfNoResults = $this->analyticsHelper->getRateOfNoResults($this->getAnalyticsParams());
        }
        return $this->_rateOfNoResults;
    }

    public function getTotalResultRates()
    {
        $result = $this->getRateOfNoResults();
        return $result && isset($result['rate']) ? round($result['rate'] * 100, 2) . '%' : 0;
    }

    public function getResultRateByDates()
    {
        $result = $this->getRateOfNoResults();
        return $result && isset($result['dates']) ? $result['dates'] : array();
    }

    public function getDailySearchData()
    {
        $searches = $this->getSearchesByDates();
        $users = $this->getUsersCountByDates();
        $rates = $this->getResultRateByDates();

        foreach ($searches as &$search) {
            $search['users'] = $this->getDateValue($users, $search['date'], 'count');
            $search['rate'] = $this->getDateValue($rates, $search['date'], 'rate');

            $date = $this->dateTime->date($search['date']);
            $search['formatted'] = date('M, d', $date->getTimestamp());
        }

        return $searches;
    }

    /**
     * @param $array
     * @param $date
     * @param $valueKey
     * @return string
     */
    protected function getDateValue($array, $date, $valueKey)
    {
        $value = '';
        foreach ($array as $item) {
            if ($item['date'] === $date) {
                $value = $item[$valueKey];
                break;
            }
        }
        return $value;
    }


    public function getTopSearches()
    {
        $topSearches = $this->analyticsHelper->getTopSearches($this->getAnalyticsParams(array('limit' => self::LIMIT_RESULTS)));
        return isset($topSearches['searches']) ? $topSearches['searches'] : array();
    }

    public function getPopularResults()
    {
        $popular = $this->analyticsHelper->getTopHits($this->getAnalyticsParams(array('limit' => self::LIMIT_RESULTS)));
        $hits = isset($popular['hits']) ? $popular['hits'] : array();

        if (count($hits)) {
            $objectIds = array_map(function($arr) {
                return $arr['hit'];
            }, $hits);

            if ($this->getCurrentType() == 'products') {
                $collection = $this->productCollection->create();
                $collection->addAttributeToSelect('name');
                $collection->addAttributeToFilter('entity_id', array('in' => $objectIds));

                foreach ($hits as &$hit) {
                    $item = $collection->getItemById($hit['hit']);
                    $hit['name'] = $item->getName();
                }
            }
        }

        return $hits;
    }

    public function getNoResultSearches()
    {
        $noResults = $this->analyticsHelper->getTopSearchesNoResults($this->getAnalyticsParams(array('limit' => self::LIMIT_RESULTS)));
        return $noResults && isset($noResults['searches']) ? $noResults['searches'] : array();
    }

    public function checkIsValidDateRange()
    {
        if ($formData = $this->_backendSession->getAlgoliaAnalyticsFormData()) {
            if (isset($formData['from']) && !empty($formData['from'])) {

                $startDate = $this->dateTime->date($formData['from']);
                $diff = date_diff($startDate, $this->dateTime->date());
                if ($diff->days > 7) {
                    return false;
                }
            }
        }

        return true;
    }

    public function getCurrentType()
    {
        if ($formData = $this->_backendSession->getAlgoliaAnalyticsFormData()) {
            if (isset($formData['type'])) {
                return $formData['type'];
            }
        }
        return self::DEFAULT_TYPE;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSections()
    {
        return $sections = array(
            'products' => $this->dataHelper->getIndexName($this->productHelper->getIndexNameSuffix(), $this->getStore()->getId()),
            'categories' => $this->dataHelper->getIndexName($this->categoryHelper->getIndexNameSuffix(), $this->getStore()->getId()),
            'pages' => $this->dataHelper->getIndexName($this->pageHelper->getIndexNameSuffix(), $this->getStore()->getId())
        );
    }

    public function getTypeEditUrl($objectId)
    {
        if ($this->getCurrentType() == 'products') {
            return $this->getUrl('catalog/product/edit', array('id' => $objectId));
        }

        if ($this->getCurrentType() == 'categories') {
            return $this->getUrl('catalog/category/edit', array('id' => $objectId));
        }

        if ($this->getCurrentType() == 'pages') {
            return $this->getUrl('cms/page/edit', array('page_id' => $objectId));
        }
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getDailyChartHtml()
    {
        $block = $this->getLayout()->createBlock(\Algolia\AlgoliaSearch\Block\Adminhtml\Analytics\Ui\Searches::class);
        $block->setAnalytics($this->getDailySearchData());
        return $block->toHtml();
    }

    /**
     * @param $message
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getTooltipHtml($message)
    {
        $block = $this->getLayout()->createBlock(\Magento\Backend\Block\Template::class);
        $block->setTemplate('Algolia_AlgoliaSearch::analytics/ui/tooltips.phtml');
        $block->setData('message', $message);
        return $block->toHtml();
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
