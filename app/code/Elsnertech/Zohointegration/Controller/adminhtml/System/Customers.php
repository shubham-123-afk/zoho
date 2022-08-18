<?php

namespace Elsnertech\Zohointegration\Controller\Adminhtml\System;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\JsonFactory;

class Customers extends \Magento\Backend\App\Action
{
    protected $_resultJsonFactory;

    protected $_apiintegrationl;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        JsonFactory $resultJsonFactory,
        \Elsnertech\Zohointegration\Model\ApiIntegration $apiintegrationl
    ) {
        parent::__construct($context);

        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_apiintegrationl = $apiintegrationl;
    }

    public function execute()
    {
        $result = $this->_resultJsonFactory->create();
        $result1[] = $this->_apiintegrationl->CustomerApi();
        $result->setData(['output' => $result1]);
        return $result;
    }
}
