<?php
namespace Elsnertech\Zohointegration\Observer;

/**
 * Class CustomerMassDelete
 *
 * @package Elsnertech\Zohointegration\Observer
 */
use Magento\Framework\Event\ObserverInterface;

class CustomerMassDelete implements ObserverInterface
{
    protected $_helper;
    protected $customer;
    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Elsnertech\Zohointegration\Model\Api $Api,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Elsnertech\Zohointegration\Helper\Data $helperData
    ) {
        $this->_request = $request;
        $this->_Api = $Api;
        $this->_customerFactory = $customerFactory;
        $this->_helper = $helperData;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $id = $this->_request->getParams();
        if (isset($id['excluded'])) {
            $id = $id['excluded'];
            $data =  $this->_customerFactory->create()->getCollection();
            $collection = $data->addAttributeToFilter('entity_id', ['nin' => $id])->getdata();
            foreach ($collection as $key) {
                $id = $key['entity_id'];
                $customer = $this->_customerFactory->create();
                $customer = $customer->load($id);
                $zohocustomer = $customer->getzoho_id();
                $gatewayUrl = $this->_helper->getDeleteCustomerurl().$zohocustomer.
                "?organization_id=".$this->_helper->getorg();
                $this->_Api->deleteApi($gatewayUrl);
            }
        } elseif (isset($id['selected'])) {
            $id = $id['selected'];
            foreach ($id as $i) {
                $customer = $this->_customerFactory->create();
                $customer = $customer->load($i);
                $zohocustomer = $customer->getzoho_id();
                $gatewayUrl = $this->_helper->getDeleteCustomerurl().$zohocustomer.
                "?organization_id=".$this->_helper->getorg();
                $this->_Api->deleteApi($gatewayUrl);
            }
        }
    }
}
