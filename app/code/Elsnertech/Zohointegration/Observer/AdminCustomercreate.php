<?php
namespace Elsnertech\Zohointegration\Observer;

/**
 * Class AdminCustomercreate
 *
 * @package Elsnertech\AdminCustomercreate\Observer
 */
use Magento\Framework\Event\ObserverInterface;

class AdminCustomercreate implements ObserverInterface
{
        protected $_curl;
        protected $_request;
        protected $_helper;
        protected $_Api;

    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Magento\Customer\Model\CustomerFactory $_customerloader,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Elsnertech\Zohointegration\Helper\Data $helperData,
        \Elsnertech\Zohointegration\Model\Api $Api
    ) {
            $this->_request = $request;
            $this->_customerloader = $_customerloader;
            $this->_curl = $curl;
            $this->_helper = $helperData;
            $this->_Api = $Api;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        $id = $customer->getId();
        $zoho = $customer->getzoho_id();
        $customer = $this->_customerloader->create();
        $customer = $customer->load($id);
        $a = $customer->getzoho_id();
        if (isset($id) && $a==456) {
            $zoho = $customer->getZohoId();
            $m = $customer->getmobile();
            $name = $customer->getFirstname() .' '. $customer->getLastname();
            $fname = $customer->getFirstname();
            $lname = $customer->getLastname();
            $email = $customer->getEmail();
    
            $a = [[
                    "first_name"=> $fname,
                    "last_name"=> $lname,
                    "email"=> $email,
                    "phone"=> $m,
                    "mobile"=> $m,
                    "is_primary_contact"=> true
                ]];

                $data = [
                    'first_name' => $fname,
                    'last_name' => $lname,
                    'contact_name' => $name,
                    'contact_persons' =>$a
                    
                    ];

                $this->_curl->setHeaders($this->_helper->getHeaders());
                $this->_curl->post($this->_helper->getCustomerApi(), json_encode($data));
                $response = $this->_curl->getBody();
                $response = json_decode($response, true);
                if (isset($response['contact']['contact_id'])) {
                    $rid = (int)$response['contact']['contact_id'];
                    $this->_Api->zohoId($email, $rid);
                }
        } else {

            $id = $customer->getId();
            $Zid = $customer->getZohoId();
            $name = $customer->getName();
            $billingaddress = $customer->getDefaultBillingAddress();
            $shippingaddress = $customer->getDefaultShippingAddress();

            if ($billingaddress && $shippingaddress) {
                $shipping_address = [
                    "attention" => $name,
                    "address"=> $shippingaddress->getData(' street') ,
                    "street2"=> $shippingaddress->getData('street'),
                    "city"=>$shippingaddress->getData('city'),
                    "state"=> $shippingaddress->getData('region'),
                    "company"=> $shippingaddress->getData('company'),
                    "zip"=>$shippingaddress->getData('postcode'),
                    "country"=>$shippingaddress->getData('country_id')
                ];

                $billing_address = [
                    "attention"=> $name,
                    "address"=>$billingaddress->getData('street') ,
                    "street2"=> $billingaddress->getData('street'),
                    "city"=>$billingaddress->getData('city'),
                    "state"=> $billingaddress->getData('region'),
                    "company"=> $shippingaddress->getData('company'),
                    "zip"=>$billingaddress->getData('postcode'),
                    "country"=> $billingaddress->getData('country_id')
                ];
                
                $contact_persons= [
                    "first_name"=>  $customer->getFirstname(),
                    "last_name"=>  $customer->getLastname(),
                    "email"=>  $customer->getemail(),
                    "phone"=> $billingaddress->getData('telephone'),
                    "mobile"=> $shippingaddress->getData('telephone')
                ];

                $data = [
                    'company_name' =>$billingaddress->getData('company') ,
                    'billing_address'=> $billing_address,
                    'shipping_address'=> $shipping_address,
                    'contact_persons' => [$contact_persons]
                    ];

                $data = json_encode($data);
                $gatewayUrl ="https://inventory.zoho.com/api/v1/contacts/".$Zid."?organization_id=".
                $this->_helper->getorg();
                $remove = [[
                    "email"=>""
                ]];
                $data1 = [
                    'contact_name' => $customer->getname(),
                    'contact_persons' =>$remove
                ];
                $data1 = json_encode($data1);
                $this->_Api->makeApiRequest($gatewayUrl, $data1, $method = 'PUT');
                $this->_Api->makeApiRequest($gatewayUrl, $data, $method = 'PUT');

            } elseif ($billingaddress) {
                $billing_address = [
                    "attention"=> $name,
                    "address"=>$billingaddress->getData('street') ,
                    "street2"=> $billingaddress->getData('street'),
                    "city"=>$billingaddress->getData('city'),
                    "state"=> $billingaddress->getData('region'),
                    "zip"=>$billingaddress->getData('postcode'),
                    "country"=> $billingaddress->getData('country_id')
                ];

                $contact_persons= [
                    "first_name"=>  $customer->getFirstname(),
                    "last_name"=>  $customer->getLastname(),
                    "email"=>  $customer->getemail(),
                    "phone"=> $billingaddress->getData('telephone'),
                    "mobile"=> " "
                ];

                $data = [
                    'company_name' =>$billingaddress->getData('company') ,
                    'billing_address'=> $billing_address,
                    'contact_persons' => [$contact_persons]
                  ];

                $data = json_encode($data);
                $gatewayUrl ="https://inventory.zoho.com/api/v1/contacts/".$Zid.
                "?organization_id=".$this->_helper->getorg();
                $remove = [[
                    "email"=>""
                ]];
                $data1 = [
                    'contact_name' => $customer->getname(),
                    'contact_persons' =>$remove
                ];
                $data1 = json_encode($data1);
                $this->_Api->makeApiRequest($gatewayUrl, $data1, $method = 'PUT');
                $this->_Api->makeApiRequest($gatewayUrl, $data, $method = 'PUT');

            } elseif ($shippingaddress) {
                 $shipping_address = [
                    "attention" => $name,
                    "address"=> $shippingaddress->getData('street') ,
                    "street2"=> $shippingaddress->getData('street'),
                    "city"=>$shippingaddress->getData('city'),
                    "state"=> $shippingaddress->getData('region'),
                    "company_name"=> $shippingaddress->getData('company'),
                    "zip"=>$shippingaddress->getData('postcode'),
                    "country"=>$shippingaddress->getData('country_id'),
                 ];

                 $contact_persons= [
                    "first_name"=>  $customer->getFirstname(),
                    "last_name"=>  $customer->getLastname(),
                    "email"=>  $customer->getemail(),
                    "phone"=> $shippingaddress->getData('telephone'),
                    "mobile"=> " "
                 ];
                
                 $data = [
                    'company_name' =>$shippingaddress->getData('company'),
                    'shipping_address'=> $shipping_address,
                    'contact_persons' => [$contact_persons]
                 ];
                 $data = json_encode($data);
                 $gatewayUrl ="https://inventory.zoho.com/api/v1/contacts/".$Zid.
                 "?organization_id=".$this->_helper->getorg();
                 $remove = [[
                    "email"=>""
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
}
