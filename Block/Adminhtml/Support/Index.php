<?php

namespace Algolia\AlgoliaSearch\Block\Adminhtml\Support;

use Algolia\AlgoliaSearch\Helper\SupportHelper;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Module\ModuleListInterface;

class Index extends Template
{
    /** @var Context */
    private $backendContext;

    /** @var SupportHelper */
    private $supportHelper;

    /** @var ModuleListInterface */
    private $moduleList;

    /**
     * @param Context $context
     * @param SupportHelper $supportHelper
     * @param ModuleListInterface $moduleList
     * @param array $data
     */
    public function __construct(
        Context $context,
        SupportHelper $supportHelper,
        ModuleListInterface $moduleList,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->backendContext = $context;
        $this->supportHelper = $supportHelper;
        $this->moduleList = $moduleList;
    }

    /** @return bool */
    public function isExtensionSupportEnabled()
    {
        return $this->supportHelper->isExtensionSupportEnabled();
    }

    public function getExtensionVersion()
    {
        return $this->moduleList->getOne('Algolia_AlgoliaSearch')['setup_version'];
    }
}
