<?php
namespace Elsnertech\Zohointegration\Observer;

/**
 * Class CreditmemoAfter
 *
 * @package Elsnertech\Zohointegration\Observer
 */
use Magento\Framework\Event\ObserverInterface;

class CreditmemoAfter implements ObserverInterface
{
    protected $_Order;

    public function __construct(
        \Elsnertech\Zohointegration\Model\Order $Order,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->messageManager = $messageManager;
        $this->_Order = $Order;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $creditMemo = $observer->getEvent()->getCreditmemo();
            $this->_Order->Creditsave($creditMemo);
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage("Error to create CreditMemo");
        }
    }
}
