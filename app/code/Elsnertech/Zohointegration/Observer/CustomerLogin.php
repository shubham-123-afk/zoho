<?php
namespace Elsnertech\Zohointegration\Observer;

/**
 * Class CustomerLogin
 *
 * @package Elsnertech\Zohointegration\Observer
 */
use Magento\Framework\Event\ObserverInterface;

class CustomerLogin implements ObserverInterface
{
        protected $_curl;
        protected $_request;
        protected $_helper;
        protected $_Api;
        protected $scopeConfig;

    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Customer\Model\CustomerFactory $_customerloader,
        \Magento\Framework\ObjectManagerInterface $objectmanager,
        \Elsnertech\Zohointegration\Helper\Data $helperData,
        \Elsnertech\Zohointegration\Model\Api $Api,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_request = $request;
        $this->_curl = $curl;
        $this->_customerloader = $_customerloader;
        $this->_objectManager = $objectmanager;
        $this->_helper = $helperData;
        $this->_Api = $Api;
        $this->scopeConfig = $scopeConfig;
    }
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $customerLoginConform = $this->scopeConfig->getValue("customer/create_account/confirm");
        if ($customerLoginConform==1) {
            $data = $this->_request->getParams();
            $id = $data['id'];
            $customer = $this->_customerloader->create();
            $customer = $customer->load($id);
            $a = $customer->getZohoId();
            if (isset($id) && $a==456) {
                $zoho = $customer->getZohoId();
                $phone = $customer->getphone();
                $name = $customer->getFirstname() .' '. $customer->getLastname();
                $fname = $customer->getFirstname();
                $lname = $customer->getLastname();
                $email = $customer->getEmail();
                $m = " ";
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
                    $this->_Api->ZohoId($email, $rid);
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
                        "address"=> $shippingaddress->getData('street') ,
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
                    $data = [
                        'company_name' =>$billingaddress->getData('company') ,
                        // 'email' => $email,
                        'billing_address'=> $billing_address,
                        'shipping_address'=> $shipping_address
                        ];

                    $data = json_encode($data);
                    $gatewayUrl ="https://inventory.zoho.com/api/v1/contacts/".$Zid.
                    "?organization_id=".$this->_helper->getorg();
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

                    $data = [
                    'company_name' =>$billingaddress->getData('company') ,
                    'billing_address'=> $billing_address
                    ];

                    $data = json_encode($data);
                    $gatewayUrl ="https://inventory.zoho.com/api/v1/contacts/".$Zid.
                    "?organization_id=".$this->_helper->getorg();
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
                    $data = [
                    'company_name' =>$shippingaddress->getData('company'),
                    'shipping_address'=> $shipping_address
                    ];
                    $data = json_encode($data);
                    $gatewayUrl ="https://inventory.zoho.com/api/v1/contacts/".$Zid.
                    "?organization_id=".$this->_helper->getorg();
                    $this->_Api->makeApiRequest($gatewayUrl, $data, $method = 'PUT');
                }
            }
        }
    }
}
