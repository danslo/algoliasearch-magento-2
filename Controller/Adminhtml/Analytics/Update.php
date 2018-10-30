<?php

namespace Algolia\AlgoliaSearch\Controller\Adminhtml\Analytics;

class Update extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @param \Magento\Backend\App\Action\Context              $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\View\LayoutFactory            $layoutFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\View\LayoutFactory $layoutFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->layoutFactory = $layoutFactory;
    }

    /**
     * Return AJAX Overview Content Section.
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $response = $this->_objectManager->create(\Magento\Framework\DataObject::class);
        $response->setError(false);

        $this->_getSession()->setAlgoliaAnalyticsFormData($this->getRequest()->getParams());

        $layout = $this->layoutFactory->create();
        $block = $layout->createBlock('\Algolia\AlgoliaSearch\Block\Adminhtml\Analytics\Index')
            ->setTemplate('Algolia_AlgoliaSearch::analytics/overview.phtml')
            ->toHtml();

        $response->setData(array('html_content' => $block));

        return $this->resultJsonFactory->create()->setJsonData($response->toJson());
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return true;
    }
}
