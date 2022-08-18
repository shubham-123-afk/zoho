<?php

namespace Elsnertech\Zohointegration\Controller\Adminhtml\System;

use Magento\Framework\Controller\Result\JsonFactory;
use Elsnertech\Zohointegration\Model\ApiIntegration;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;

class Products extends \Magento\Backend\App\Action
{
    protected $_resultJsonFactory;
    protected $_apiintegrationl;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ApiIntegration $apiintegrationl,
        ManagerInterface $messageManager
    ) {
        parent::__construct($context);
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_apiintegrationl = $apiintegrationl;
        $this->messageManager = $messageManager;
    }

    public function execute()
    {
        $result = $this->_resultJsonFactory->create();
        $result1 = $this->_apiintegrationl->ItemsImport();
        $result1 = $this->_apiintegrationl->VirtualItemsImport();
        $result2 = $this->_apiintegrationl->ItemGroupImport();
        $result3 = $this->_apiintegrationl->CompositeImport();
        $result4 = $this->_apiintegrationl->BundleImport();
        return $result->setData(['output' => $result2]);
    }
}
