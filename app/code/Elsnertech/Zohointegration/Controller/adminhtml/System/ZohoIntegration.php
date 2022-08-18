<?php

namespace Elsnertech\Zohointegration\Controller\Adminhtml\System;

use Magento\Framework\Controller\ResultFactory;

class ZohoIntegration extends \Magento\Backend\App\Action
{

    protected $_soap;
    protected $resultPageFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {

        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        // $resultPage->setActiveMenu('Elsner_Tecalliance::sparebox');
        $resultPage->getConfig()->getTitle()->prepend(__('zoho1 '));
        $resultPage->getConfig()->getTitle()->prepend(__('Zoho Inventory Integration'));
        $resultPage->addBreadcrumb(__('Zoho Inventory Integration'), __('Zoho Inventory Integration'));
        return $resultPage;
    }
}
