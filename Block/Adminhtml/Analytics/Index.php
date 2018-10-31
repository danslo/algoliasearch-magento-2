<?php

namespace Algolia\AlgoliaSearch\Block\Adminhtml\Analytics;

use Algolia\AlgoliaSearch\Helper\AlgoliaHelper;
use Algolia\AlgoliaSearch\Helper\ConfigHelper;
use Algolia\AlgoliaSearch\Helper\AnalyticsHelper;
use Algolia\AlgoliaSearch\Helper\Data;
use Algolia\AlgoliaSearch\Helper\Entity\ProductHelper;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

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

    /** @var TimezoneInterface */
    private $dateTime;

    /** @var array  */
    protected $_analyticsParams = array();

    protected $_totalSearches;

    /**
     * Index constructor.
     * @param Context $context
     * @param AlgoliaHelper $algoliaHelper
     * @param ConfigHelper $configHelper
     * @param AnalyticsHelper $analyticsHelper
     * @param ProductHelper $productHelper
     * @param Data $dataHelper
     * @param TimezoneInterface $dateTime
     * @param array $data
     */
    public function __construct(
        Context $context,
        AlgoliaHelper $algoliaHelper,
        ConfigHelper $configHelper,
        AnalyticsHelper $analyticsHelper,
        ProductHelper $productHelper,
        Data $dataHelper,
        TimezoneInterface $dateTime,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->backendContext = $context;
        $this->algoliaHelper = $algoliaHelper;
        $this->configHelper = $configHelper;
        $this->dataHelper = $dataHelper;
        $this->productHelper = $productHelper;
        $this->analyticsHelper = $analyticsHelper;
        $this->dateTime = $dateTime;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getIndexName()
    {
        return $this->dataHelper->getIndexName($this->productHelper->getIndexNameSuffix(), $this->getStore()->getId());
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

    public function getTotalSearches()
    {
        if (!$this->_totalSearches) {
            $this->_totalSearches = $this->analyticsHelper->getCountOfSearches($this->getAnalyticsParams());
        }
        return $this->_totalSearches;
    }

    public function getDateCounts()
    {
        $total = $this->getTotalSearches();
        return $total && isset($total['dates']) ? $total['dates'] : array();
    }

    public function getTotalCount()
    {
        $total = $this->getTotalSearches();
        return $total && isset($total['count']) ? $total['count'] : 0;
    }

    public function getUsers()
    {
        $analytics = $this->analyticsHelper->getUserCount($this->getAnalyticsParams());
        return isset($analytics['count']) ? $analytics['count'] : 0;
    }

    public function getResultRate()
    {
        $analytics = $this->analyticsHelper->getRateOfNoResults($this->getAnalyticsParams());
        return isset($analytics['rate']) ? round($analytics['rate'] * 100) . '%' : 0;
    }

    public function getTopSearches()
    {
        $topSearches = $this->analyticsHelper->getTopSearches($this->getAnalyticsParams(array('limit' => self::LIMIT_RESULTS)));
        return isset($topSearches['searches']) ? $topSearches['searches'] : array();
    }

    public function getNoResultSearches()
    {
        $noResults = $this->analyticsHelper->getTopSearchesNoResults($this->getAnalyticsParams(array('limit' => self::LIMIT_RESULTS)));
        return isset($noResults['searches']) ? $noResults['searches'] : array();
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
