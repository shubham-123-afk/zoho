<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Elsnertech\Zohointegration\Controller\Adminhtml\System\Config;

use \Magento\Catalog\Model\Product\Visibility;

class Synchronize extends \Magento\Backend\App\Action
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_logger = $logger;
        parent::__construct($context);
    }

    /**
     * Synchronize
     *
     * @return void
     */
    public function execute()
    {
        $this->_logger->debug('Sync Starts!!');
        // do whatever you want to do
    }
}