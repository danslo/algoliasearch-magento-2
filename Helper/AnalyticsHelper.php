<?php

namespace Algolia\AlgoliaSearch\Helper;

use AlgoliaHelper;
use AlgoliaSearch\Analytics;
use AlgoliaSearch\Version;

class AnalyticsHelper extends Analytics
{
    const ANALYTICS_SEARCH_PATH = '/2/searches';
    const ANALYTICS_HITS_PATH = '/2/hits';
    const ANALTYICS_FILTER_PATH = '/2/filters';

    /** @var \Algolia\AlgoliaSearch\Helper\AlgoliaHelper */
    private $algoliaHelper;

    public function __construct(
        AlgoliaHelper $algoliaHelper
    ) {
        $this->algoliaHelper = $algoliaHelper;
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
        return $this->_call(self::ANALYTICS_SEARCH_PATH, $params);
    }

    public function getCountOfSearches(array $params)
    {
        return $this->_call(self::ANALYTICS_SEARCH_PATH . '/count', $params);
    }

    public function getTopSearchesNoResults(array $params)
    {
        return $this->_call(self::ANALYTICS_SEARCH_PATH . '/noResults', $params);
    }

    public function getRateOfNoResults(array $params)
    {
        return $this->_call(self::ANALYTICS_SEARCH_PATH . '/noResultRate', $params);
    }

    /**
     * Hits Analytics
     *
     * @param array $params
     * @return mixed
     */
    public function getTopHits(array $params)
    {
        return $this->_call(self::ANALYTICS_HITS_PATH, $params);
    }

    public function getTopHitsForSearch($search, array $params)
    {
        return $this->_call(self::ANALYTICS_HITS_PATH . '?search=' . urlencode($search), $params);
    }

    /**
     * Get Count of Users
     *
     * @param array $params
     * @return mixed
     */
    public function getUserCount(array $params)
    {
        return $this->_call('/2/users/count', $params);
    }

    /**
     * Filter Analytics
     *
     * @param array $params
     * @return mixed
     */
    public function getTopFilterAttributes(array $params)
    {
        return $this->_call(self::ANALTYICS_FILTER_PATH, $params);
    }

    public function getTopFiltersForANoResultsSearch($search, array $params)
    {
        return $this->_call(self::ANALTYICS_FILTER_PATH . '/noResults?search=' . urlencode($search), $params);
    }

    public function getTopFiltersForASearch($search, array $params)
    {
        return $this->_call(self::ANALTYICS_FILTER_PATH . '?search=' . urlencode($search), $params);
    }

    public function getTopFiltersForAttributesAndSearch(array $attributes, $search, array $params)
    {
        return $this->_call(self::ANALTYICS_FILTER_PATH . '/' . implode(',',
                $attributes) . '?search=' . urlencode($search), $params);
    }

    public function getTopFiltersForAttribute($attribute, array $params)
    {
        return $this->_call(self::ANALTYICS_FILTER_PATH . '/' . $attribute, $params);
    }

    /**
     * @param string $path
     * @param array $params
     * @return mixed
     */
    protected function _call($path, array $params)
    {
        return $this->request('GET', $path, $params);
    }
}
