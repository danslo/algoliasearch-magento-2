<?php

namespace Algolia\AlgoliaSearch\Block\Adminhtml\Analytics;

class Form extends Index
{
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
