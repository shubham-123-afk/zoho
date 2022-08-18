<?php
namespace Elsnertech\Zohointegration\Observer\Adminhtml;

/**
 * Class AfterAddressSaveObserver
 *
 * @package Elsnertech\Zohointegration\Observer\Adminhtml
 */
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class AfterAddressSaveObserver implements ObserverInterface
{

    protected $customerRepository;
    protected $_logger;
    protected $_curl;
    protected $_helper;
    protected $_Api;

    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\ObjectManagerInterface $objectmanager,
        \Magento\Customer\Model\AddressFactory $addressFactory,
        \Elsnertech\Zohointegration\Helper\Data $helperData,
        \Magento\Customer\Model\CustomerFactory $_customerloader,
        \Elsnertech\Zohointegration\Model\Api $Api,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->customerRepository = $customerRepository;
        $this->_logger = $logger;
        $this->_addressfactory = $addressFactory;
        $this->_helper = $helperData;
        $this->_customerloader = $_customerloader;
        $this->_Api = $Api;
        $this->_objectManager = $objectmanager;
        $this->_curl = $curl;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $customerAddress = $observer->getEvent()->getCustomerAddress();
        $id = $customerAddress->getparent_id();
        $customer = $this->_customerloader->create();
        $customer = $customer->load($id);
        $biling = $customer->getDefaultBilling();
        $shipping = $customer->getDefaultShipping();
        $adress = $this->_addressfactory->create();
        $baddr = $adress->load($biling);
        $saddr = $adress->load($shipping);
        $first = $saddr->getData('street');
        $Zid = $customer->getZohoId();
        $name = $customer->getname();
        if (isset($first)) {
            $shipping_address = [
            "attention" => $name,
            "address"=> $saddr->getData('street'),
            "city"=>$saddr->getData('city'),
            "state"=> $saddr->getData('region'),
            "company"=> $saddr->getData('company'),
            "zip"=>$saddr->getData('postcode'),
            "country"=>$saddr->getData('country_id')
            ];
            $billing_address = [
            "attention"=> $name,
            "address"=>$baddr->getData('street') ,
            "city"=>$baddr->getData('city'),
            "state"=> $baddr->getData('region'),
            "company"=> $baddr->getData('company'),
            "zip"=>$baddr->getData('postcode'),
            "country"=> $baddr->getData('country_id')
            ];
            $contact_persons= [
            "first_name"=>  $customer->getFirstname(),
            "last_name"=>  $customer->getLastname(),
            "email"=>  $customer->getemail(),
            "phone"=> $saddr->getData('telephone'),
            "mobile"=> $baddr->getData('telephone')
            ];

            $data = [
            'company_name' =>$saddr->getData('company') ,
            'billing_address'=> $billing_address,
            'shipping_address'=> $shipping_address,
            'contact_persons' => [$contact_persons]
            ];
            $data = json_encode($data);
            $gatewayUrl =$this->_helper->getDeleteCustomerurl().$Zid."?organization_id=".$this->_helper->getorg();
            $remove = [[
            "email"=>" "
            ]];
            $data1 = [
            'contact_name' => $customer->getname(),
            'contact_persons' =>$remove
            ];
            $data1 = json_encode($data1);
            $this->_Api->makeApiRequest($gatewayUrl, $data1, $method = 'PUT');
            $this->_Api->makeApiRequest($gatewayUrl, $data, $method = 'PUT');
        } else {
            $shipping_address = [
            "attention" => $name,
            "address"=> $customerAddress->getData('street'),
            "city"=>$customerAddress->getData('city'),
            "state"=> $customerAddress->getData('region'),
            "company"=> $customerAddress->getData('company'),
            "zip"=>$customerAddress->getData('postcode'),
            "country"=>$customerAddress->getData('country_id')
            ];
            $billing_address = [
            "attention"=> $name,
            "address"=>$customerAddress->getData('street') ,
            "city"=>$customerAddress->getData('city'),
            "state"=> $customerAddress->getData('region'),
            "company"=> $customerAddress->getData('company'),
            "zip"=>$customerAddress->getData('postcode'),
            "country"=> $customerAddress->getData('country_id')
            ];
            $contact_persons= [
            "first_name"=>  $customer->getFirstname(),
            "last_name"=>  $customer->getLastname(),
            "email"=>  $customer->getemail(),
            "phone"=> $customerAddress->getData('telephone'),
            "mobile"=> $customerAddress->getData('telephone')
            ];

            $data = [
            'company_name' =>$customerAddress->getData('company') ,
            'billing_address'=> $billing_address,
            'shipping_address'=> $shipping_address,
            'contact_persons' => [$contact_persons]
            ];
            $data = json_encode($data);
            $gatewayUrl =$this->_helper->getDeleteCustomerurl().$Zid."?organization_id=".$this->_helper->getorg();
            $remove = [[
            "email"=>" "
            ]];
            $data1 = [
            'contact_name' => $customer->getname(),
            'contact_persons' =>$remove
            ];
            $data1 = json_encode($data1);
            $this->_Api->makeApiRequest($gatewayUrl, $data1, $method = 'PUT');
            $this->_Api->makeApiRequest($gatewayUrl, $data, $method = 'PUT');
        }
    }
}
