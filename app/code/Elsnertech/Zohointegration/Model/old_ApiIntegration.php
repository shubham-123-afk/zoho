<?php
namespace Elsnertech\Zohointegration\Model;
use Magento\Customer\Model\CustomerFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;
use Elsnertech\Zohointegration\Model\Api;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Catalog\Model\ProductFactory;

class ApiIntegration extends \Magento\Framework\Model\AbstractModel
{ 
    const SALES_ORDER = "https://inventory.zoho.com/api/v1/salesorders/";
    const CUSTOMER_API = "https://inventory.zoho.com/api/v1/contacts";
    const ITEM_API     = "https://inventory.zoho.com/api/v1/items";
    const ITEMGRP_API  = "https://inventory.zoho.com/api/v1/itemgroups";
    const ITEMGRPDELETE_API = "https://inventory.zoho.com/api/v1/itemgroups/";
    const ITEMDELETE_API = "https://inventory.zoho.com/api/v1/items/";
    const ITEMEDIT_API = "https://inventory.zoho.com/api/v1/items/";
    const ITEMGRPEDIT_API = "https://inventory.zoho.com/api/v1/itemgroups/";
    const COMPOSITETEM_API = "https://inventory.zoho.com/api/v1/compositeitems";
    const SALESORDER_API = "https://inventory.zoho.com/api/v1/salesorders";
    const SALESORDEREDIT_API = "https://inventory.zoho.com/api/v1/salesorders/";
    const INVOICE_API = "https://inventory.zoho.com/api/v1/invoices";
    const PACKAGE_API ="https://inventory.zoho.com/api/v1/packages";
    const SHIPMENTORDER_API = "https://inventory.zoho.com/api/v1/shipmentorders";
    const CREDITNOTES_API = " https://inventory.zoho.com/api/v1/creditnotes";
    const CUSTOMER_PAYMENT_API = "https://inventory.zoho.com/api/v1/customerpayments?organization_id=";
    const PACKET_ID = "https://inventory.zoho.com/api/v1/packages?organization_id=";
    const DELIVERY = "https://inventory.zoho.com/api/v1/shipmentorders/";
    const TOKEN = "https://accounts.zoho.com/oauth/v2/token?refresh_token=";


    protected $_customerFactory;

    public function __construct(
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerFactory,
        CustomerFactory $customerloader,
        StoreManagerInterface $storeManager,
        Website $websiteModel,
        ScopeConfigInterface $scopeConfig,
        Curl $curl,
        Api $api,
        ProductFactory $_productloader,
        \Magento\Framework\Filesystem $filesystem,
        \Elsnertech\Zohointegration\Logger\Logger $customLogger            
    ) {
        $this->_customerFactory = $customerFactory;
        $this->_customerloader = $customerloader;
        $this->_storeManager = $storeManager;
        $this->_websiteModel = $websiteModel;
        $this->scopeConfig = $scopeConfig;
        $this->_curl = $curl;
        $this->_Api = $api;
        $this->_customLogger = $customLogger;
        $this->_productloader = $_productloader;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
    }

    public function CustomerApi() {
        $customerCollection = $this->_customerFactory->create();
        foreach ($customerCollection as $customer) {
            $customer_id[] = $customer->getId();
        }
        return $this->createCustomer($customer_id,count($customer_id));
    }

    public function ProductApi() {
        $productCollection = $this->_productloader->create()->getCollection();

        foreach ($productCollection as $product) {
            if ($product->getTypeId()=="simple"){
                $product_type[] = $product->getid();
            }   
        }

        return $this->simpleProduct($product_type);
    }

    public function simpleProduct($all_id) {
            $product_type = "goods";
            $unit = $this->scopeConfig->getValue('general/locale/weight_unit');
            $url = $this->getItemApi();
            foreach ($all_id as $id) {
                $product = $this->_productloader->create();
                $product->load($id);
                $type_id = $product->getTypeId();
                if ($type_id=="simple") {
                
                    $data = [    
                        "unit"=> $unit,
                        "item_type"=> "inventory",
                        "product_type"=>  $product_type,
                        "description"=> strip_tags(str_replace('&nbsp;', ' ', $product->getDescription())),
                        "name"=> $product->getName(),
                        "rate"=> $product->getPrice(),
                        "purchase_rate"=> $product->getPrice(),
                        // "initial_stock"=> $product['quantity_and_stock_status']['qty'],
                        // "initial_stock_rate"=> $product['quantity_and_stock_status']['qty'],
                        "initial_stock"=> 0,
                        "initial_stock_rate"=> 0,
                        "sku"=> $product->getSku()
                    ];
                    try {
                        $this->_curl->setHeaders($this->getHeaders());
                        $this->_curl->post($url , json_encode($data));
                        $response = $this->_curl->getBody();
                        $response = json_decode($response, true);
                    } catch (\Exception $e) {
                        $this->_customLogger->info($e->getMessage());
                    }
                }
            } 
    }

    public function SalesOrderApi() {
        return "Sales Order Api";
    }


    public function createCustomer($customer_id) {
        $i = 1;
        foreach ($customer_id as $customerId) {
            $customer = $this->_customerloader->create()->load($customerId);
            if(empty($customer->getZohoCustomerId()) || $customer->getZohoCustomerId()==123456) {
                $shipAdd = $this->getCustomerShippingAddress($customer);
                $billAdd = $this->getCustomerBillingAddress($customer);
                $customer_name = $customer->getFirstname() .' '. $customer->getLastname();
                $telephone = " ";
                if($customer->getDefaultShippingAddress()!=Null){
                    $telephone = $customer->getDefaultShippingAddress()->getData('telephone');
                }
                if($customer->getDefaultBillingAddress()!=Null){
                    $telephone = $customer->getDefaultBillingAddress()->getData('telephone');
                }
                $customer_name_info = [[
                    "first_name"=> $customer->getFirstname(),
                    "last_name"=> $customer->getLastname(),
                    "email"=> $customer->getEmail(),
                    "phone"=> $telephone,
                    "mobile"=> $telephone,
                    "is_primary_contact"=> true
                ]];
                $customer_api_list = [
                    "contact_name" => $customer_name,
                    "contact_type" => "customer",
                    "contact_persons" => $customer_name_info
                ];
                if ($shipAdd!=NULL) {
                    $customer_api_list['company_name'] = $customer->getDefaultShippingAddress()->getData('company');
                    $customer_api_list['shipping_address'] = $shipAdd;                    
                } 
                if ($billAdd!=NULL) {
                    $customer_api_list['company_name'] = $customer->getDefaultShippingAddress()->getData('company');
                    $customer_api_list['billing_address'] = $billAdd;                    
                }

                try {
                    $this->_curl->setHeaders($this->getHeaders());
                    $this->_curl->post($this->getCustomerApi(), json_encode($customer_api_list));
                    $response = $this->_curl->getBody();
                    $response = json_decode($response, true);
                    if (isset($response['contact']['contact_id'])) {
                        $response_id = (int)$response['contact']['contact_id'];
                        $this->_Api->ZohoId($customer->getEmail(), $response_id);
                        $i++;
                        $customer->setzohoCustomerId($response_id);
                        $customer->save();
                        $this->_customLogger->info($customer->getEmail().'Customer create message');
                    }  
                } catch (\Exception $e) {

                }
            } else {
                $this->_customLogger->info($customer->getEmail().'Customer Alredy Created');
            }
        }
    }

    public function getCustomerShippingAddress($customer) {
        $shippingaddress = $customer->getDefaultShippingAddress();

        if($shippingaddress!=NULL) {
            $shipping_address = [
                "attention" => $customer->getName(),
                "address"=> $shippingaddress->getData('street') ,
                "street2"=> $shippingaddress->getData('street'),
                "city"=>$shippingaddress->getData('city'),
                "state"=> $shippingaddress->getData('region'),
                // "company"=> $shippingaddress->getData('company'),
                "zip"=>$shippingaddress->getData('postcode'),
                "country"=>$shippingaddress->getData('country_id')
            ];
            return $shipping_address;
        }
    }

    public function getCustomerBillingAddress($customer) {
        
        $billingaddress = $customer->getDefaultBillingAddress();

        if($billingaddress!=NULL) {
            $billing_address = [
                "attention"=> $customer->getName(),
                "address"=>$billingaddress->getData('street') ,
                "street2"=> $billingaddress->getData('street'),
                "city"=>$billingaddress->getData('city'),
                "state"=> $billingaddress->getData('region'),
                // "company"=> $billingaddress->getData('telephone'),
                "zip"=>$billingaddress->getData('postcode'),
                "country"=> $billingaddress->getData('country_id')
            ];
            return $billing_address;
    
        }
    }

    public function getStoreCode() {
        return $this->_storeManager->getStore()->getName();
    }

    public function getWebsiteName($websiteId) {
        $collection = $this->_websiteModel->load($websiteId,'website_id');
        return $collection->getName();
    }

    public function getCustomerApi() {
        return self::CUSTOMER_API.'?organization_id='.
        $this->scopeConfig->getValue('zohointegration/department/organization_id');
    }

    public function getItemApi(){
        return self::ITEM_API . '?organization_id='.
        $this->scopeConfig->getValue('zohointegration/department/organization_id');
    }

    public function getHeaders() {
        // $refress = $this->scopeConfig->getValue('zohointegration/department/refress_token');
        // $client =  $this->scopeConfig->getValue('zohointegration/department/client_id');
        // $cs = $this->scopeConfig->getValue('zohointegration/department/client_secret');
        // $redirect =  $this->scopeConfig->getValue('zohointegration/department/redirect_uri');
        // $url = self::TOKEN.$refress."&client_id=".
        // $client."&client_secret=".$cs."&redirect_uri=".$redirect."&grant_type=refresh_token";
        // $this->_curl->post($url, " ");
        // $response = $this->_curl->getBody();
        // $response = json_decode($response);
        // $foodArray = (array)$response;
        // $access_token =  $foodArray['access_token'];

        return [
            "Authorization" => "Zoho-oauthtoken 1000.8bd270e9f1d68a53d243ad0c12155ec2.912143a5a1059c8cde7ab7100c4835a8",
            "Content-Type" => "application/json",
            "Cache-Control"=>"no-cache"
        ];
    }

}
