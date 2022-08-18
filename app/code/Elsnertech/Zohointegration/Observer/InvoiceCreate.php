<?php
namespace Elsnertech\Zohointegration\Observer;

/**
 * Class InvoiceCreate
 *
 * @package Elsnertech\Zohointegration\Observer
 */

use Magento\Framework\Event\ObserverInterface;

class InvoiceCreate implements ObserverInterface
{
  
    protected $_Order;

    public function __construct(
        \Elsnertech\Zohointegration\Model\Order $Order,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->_Order = $Order;
        $this->messageManager = $messageManager;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $invoice = $observer->getEvent()->getInvoice();
            $zohoinvoice = $invoice;
            $this->_Order->Invoiceafter($zohoinvoice);
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage("Error to create Invoice");
        }
    }
}
