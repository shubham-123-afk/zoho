<?php

/**
 * Class Shipmentafter
 *
 * @package Elsnertech\Zohointegration\Observer
 */

namespace Elsnertech\Zohointegration\Observer;

use Magento\Framework\Event\ObserverInterface;

class Shipmentafter implements ObserverInterface
{
    protected $_Order;
    public function __construct(\Elsnertech\Zohointegration\Model\Order $Order)
    {
        $this->_Order = $Order;
    }
    public function execute(\magento\Framework\Event\Observer $observer)
    {
        $shipment = $observer->getEvent()->getShipment();
        $this->_Order->Shipmentafter($shipment);
    }
}
