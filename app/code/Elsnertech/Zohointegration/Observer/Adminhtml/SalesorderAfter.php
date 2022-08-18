<?php
namespace Elsnertech\Zohointegration\Observer\Adminhtml;

/**
 * Class SalesorderAfter
 *
 * @package Elsnertech\Zohointegration\Observer\Adminhtml
 */
 
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
 
class SalesorderAfter implements ObserverInterface
{
    protected $logger;
    protected $_salesorder;
    public function __construct(
        LoggerInterface $logger,
        \Elsnertech\Zohointegration\Model\Salesorder $Salesorder
    ) {
        $this->logger = $logger;
        $this->_salesorder = $Salesorder;
    }
 
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $order = $observer->getEvent()->getOrder();
            $id = $order->getid();
            $this->_salesorder->createOrder($order);
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }
}
