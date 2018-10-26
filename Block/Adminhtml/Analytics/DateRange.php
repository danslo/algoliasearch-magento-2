<?php

namespace Algolia\AlgoliaSearch\Block\Adminhtml\Analytics;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

class DateRange extends Template
{
    /**
     * DateRange constructor.
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );

        $this->setTemplate('analytics/daterange.phtml');
    }
}
