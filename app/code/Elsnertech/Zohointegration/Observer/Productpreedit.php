<?php
namespace Elsnertech\Zohointegration\Observer;

/**
 * Class Productpreedit
 *
 * @package Elsnertech\Zohointegration\Observer
 */
    use Magento\Framework\Event\ObserverInterface;

class Productpreedit implements ObserverInterface
{
        protected $_helper;

    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Magento\Catalog\Model\ProductFactory $_productloader,
        \Elsnertech\Zohointegration\Helper\Data $helperData
    ) {
            $this->_request = $request;
            $this->_productloader = $_productloader;
            $this->_helper = $helperData;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
            $product = $observer->getEvent()->getProduct();
            $productid = $this->_request->getParams();
        if (isset($productid['id'])) {
                $id = $productid['id'];
                $product = $this->_productloader->create();
                $product = $product->load($id);
                $product->setCustomattribute('edit', "123478945454");
                $product->save();
        }
    }
}
