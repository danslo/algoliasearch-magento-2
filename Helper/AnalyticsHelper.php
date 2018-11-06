<?php

namespace Algolia\AlgoliaSearch\Helper;

use Algolia\AlgoliaSearch\Helper\AlgoliaHelper;
use AlgoliaSearch\Analytics;
use AlgoliaSearch\Version;

class AnalyticsHelper extends Analytics
{
    const ANALYTICS_SEARCH_PATH = '/2/searches';
    const ANALYTICS_HITS_PATH = '/2/hits';
    const ANALTYICS_FILTER_PATH = '/2/filters';

    /** Cache variables to prevent excessive calls */
    protected $_searches;
    protected $_users;
    protected $_rateOfNoResults;

    /** @var \Algolia\AlgoliaSearch\Helper\AlgoliaHelper */
    private $algoliaHelper;

    private $logger;

    public function __construct(
        AlgoliaHelper $algoliaHelper,
        Logger $logger
    ) {
        $this->algoliaHelper = $algoliaHelper;
        $this->logger = $logger;

        parent::__construct($algoliaHelper->getClient());
    }

    /**
     * Search Analytics
     *
     * @param array $params
     * @return mixed
     */
    public function getTopSearches(array $params)
    {
        return $this->fetch(self::ANALYTICS_SEARCH_PATH, $params);
    }

    public function getCountOfSearches(array $params)
    {
        if (!$this->_searches) {
            $this->_searches = $this->fetch(self::ANALYTICS_SEARCH_PATH . '/count', $params);
        }
        return $this->_searches;
    }

    public function getTotalCountOfSearches(array $params)
    {
        $searches = $this->getCountOfSearches($params);
        return $searches && isset($searches['count']) ? $searches['count'] : 0;
    }

    public function getSearchesByDates(array $params)
    {
        $searches = $this->getCountOfSearches($params);
        return $searches && isset($searches['dates']) ? $searches['dates'] : array();
    }

    public function getTopSearchesNoResults(array $params)
    {
        return $this->fetch(self::ANALYTICS_SEARCH_PATH . '/noResults', $params);
    }

    public function getRateOfNoResults(array $params)
    {
        if (!$this->_rateOfNoResults) {
            $this->_rateOfNoResults = $this->fetch(self::ANALYTICS_SEARCH_PATH . '/noResultRate', $params);
        }
        return $this->_rateOfNoResults;
    }

    public function getTotalResultRates(array $params)
    {
        $result = $this->getRateOfNoResults($params);
        return $result && isset($result['rate']) ? round($result['rate'] * 100, 2) . '%' : 0;
    }

    public function getResultRateByDates(array $params)
    {
        $result = $this->getRateOfNoResults($params);
        return $result && isset($result['dates']) ? $result['dates'] : array();
    }

    /**
     * Hits Analytics
     *
     * @param array $params
     * @return mixed
     */
    public function getTopHits(array $params)
    {
        return $this->fetch(self::ANALYTICS_HITS_PATH, $params);
    }

    public function getTopHitsForSearch($search, array $params)
    {
        return $this->fetch(self::ANALYTICS_HITS_PATH . '?search=' . urlencode($search), $params);
    }

    /**
     * Get Count of Users
     *
     * @param array $params
     * @return mixed
     */
    public function getUsers(array $params)
    {
        if (!$this->_users) {
            $this->_users = $this->fetch('/2/users/count', $params);
        }
        return $this->_users;
    }

    public function getTotalUsersCount(array $params)
    {
        $users = $this->getUsers($params);
        return $users && isset($users['count']) ? $users['count'] : 0;
    }

    public function getUsersCountByDates(array $params)
    {
        $users = $this->getUsers($params);
        return $users && isset($users['dates']) ? $users['dates'] : array();
    }

    /**
     * Filter Analytics
     *
     * @param array $params
     * @return mixed
     */
    public function getTopFilterAttributes(array $params)
    {
        return $this->fetch(self::ANALTYICS_FILTER_PATH, $params);
    }

    public function getTopFiltersForANoResultsSearch($search, array $params)
    {
        return $this->fetch(self::ANALTYICS_FILTER_PATH . '/noResults?search=' . urlencode($search), $params);
    }

    public function getTopFiltersForASearch($search, array $params)
    {
        return $this->fetch(self::ANALTYICS_FILTER_PATH . '?search=' . urlencode($search), $params);
    }

    public function getTopFiltersForAttributesAndSearch(array $attributes, $search, array $params)
    {
        return $this->fetch(self::ANALTYICS_FILTER_PATH . '/' . implode(',',
                $attributes) . '?search=' . urlencode($search), $params);
    }

    public function getTopFiltersForAttribute($attribute, array $params)
    {
        return $this->fetch(self::ANALTYICS_FILTER_PATH . '/' . $attribute, $params);
    }

    /**
     * Pass through method for handling API Versions
     *
     * @param string $path
     * @param array $params
     * @return mixed
     */
    protected function fetch($path, array $params)
    {
        $response = false;

        try {
            // analytics api requires index name for all calls
            if (!isset($params['index'])) {
                throw new \Exception('Analytics API requires index name.');
            }

            $response = $this->request('GET', $path, $params);

        } catch (\Exception $e) {
            $this->logger->log($e->getMessage());
        }

        return $response;
    }
}
