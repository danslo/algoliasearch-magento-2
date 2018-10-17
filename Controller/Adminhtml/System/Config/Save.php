<?php

namespace Algolia\AlgoliaSearch\Controller\Adminhtml\System\Config;

class Save extends \Magento\Config\Controller\Adminhtml\System\Config\Save
{
    /**
     * Get groups for save
     *
     * @return array|null
     */
    protected function _getGroupsForSave()
    {
        $groups = parent::_getGroupsForSave();

        return $this->handleDeactivatedSerializedArrays($groups);
    }

    private function handleDeactivatedSerializedArrays($groups)
    {
        if (isset($groups['instant']['fields']['is_instant_enabled']['value'])
                && $groups['instant']['fields']['is_instant_enabled']['value'] == '0') {
            if (isset($groups['instant']['fields']['facets'])) {
                unset($groups['instant']['fields']['facets']);
            }
            if (isset($groups['instant']['fields']['sorts'])) {
                unset($groups['instant']['fields']['sorts']);
            }
        }

        if (isset($groups['autocomplete']['fields']['is_popup_enabled']['value'])
                && $groups['autocomplete']['fields']['is_popup_enabled']['value'] == '0') {
            if (isset($groups['autocomplete']['fields']['sections'])) {
                unset($groups['autocomplete']['fields']['sections']);
            }

            if (isset($groups['autocomplete']['fields']['excluded_pages'])) {
                unset($groups['autocomplete']['fields']['excluded_pages']);
            }
        }

        return $groups;
    }
}
