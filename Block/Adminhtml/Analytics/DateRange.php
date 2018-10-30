<?php

namespace Algolia\AlgoliaSearch\Block\Adminhtml\Analytics;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

class DateRange extends Template
{
    /**
     * DateRange constructor.
     * @param Context $context
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

    /**
     * @return string
     */
    public function getFormAction()
    {
        return $this->getUrl('*/*/update', ['_current' => true]);
    }

    /**
     * @param $key
     * @return string
     */
    public function getFormValue($key)
    {
        $formData = $this->_backendSession->getAlgoliaAnalyticsFormData();

        return ($formData && isset($formData[$key])) ? $formData[$key] :  '';
    }
}
