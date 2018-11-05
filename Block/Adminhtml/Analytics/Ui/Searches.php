<?php

namespace Algolia\AlgoliaSearch\Block\Adminhtml\Analytics\Ui;

class Searches extends \Magento\Backend\Block\Template
{
    protected $analytics;

    /**
     * Searches constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->setTemplate('Algolia_AlgoliaSearch::analytics/ui/graph.phtml');
    }

    public function setAnalytics($analytics)
    {
        $this->analytics = $analytics;
    }

    public function getAnalytics()
    {
        return $this->analytics;
    }
}