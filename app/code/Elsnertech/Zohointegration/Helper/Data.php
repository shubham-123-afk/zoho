<?php
/**
 * Class Data
 *
 * @package Elsnertech\Zohointegration\Helper
 */
namespace Elsnertech\Zohointegration\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_TESTI = 'zohointegration/';
    const SALES_ORDER = "https://inventory.zoho.com/api/v1/salesorders/";
    const CUSTOMER_API = "https://inventory.zoho.com/api/v1/contacts/";
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

    public function __construct(
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_curl = $curl;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }
    
    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getHeaders() {
        $refress = $this->scopeConfig->getValue('zohointegration/department/refress_token');
        $client =  $this->scopeConfig->getValue('zohointegration/department/client_id');
        $cs = $this->scopeConfig->getValue('zohointegration/department/client_secret');
        $redirect =  $this->scopeConfig->getValue('zohointegration/department/redirect_uri');
        $url = "https://accounts.zoho.com/oauth/v2/token?refresh_token=".$refress."&client_id=".
        $client."&client_secret=".$cs."&redirect_uri=".$redirect."&grant_type=refresh_token";
        $this->_curl->post($url, " ");
        $response = $this->_curl->getBody();
        $response = json_decode($response);
        $foodArray = (array)$response;
        $a =  $foodArray['access_token'];
        return [ "Authorization" => "Zoho-oauthtoken ".$a,
        "Content-Type" => "application/json",
        "Cache-Control"=>"no-cache"
        ];
    }

    public function getHead()
    {
        $headers = [
        "Authorization" => "Zoho-oauthtoken ".
        $this->scopeConfig->getValue('zohointegration/department/access_token', ScopeInterface::SCOPE_STORE),
        "Content-Type" => "multipart/form-data",
        "Cache-Control"=>"no-cache"
           ];

        return $headers;
    }

    public function getTest()
    {
        
        $refress = $this->scopeConfig->getValue('zohointegration/department/refress_token');
        $client =  $this->scopeConfig->getValue('zohointegration/department/client_id');
        $cs = $this->scopeConfig->getValue('zohointegration/department/client_secret');
        $redirect =  $this->scopeConfig->getValue('zohointegration/department/redirect_uri');
        $url = "https://accounts.zoho.com/oauth/v2/token?refresh_token=".$refress."&client_id=".
        $client."&client_secret=".$cs."&redirect_uri=".$redirect."&grant_type=refresh_token";
        $this->_curl->post($url, " ");
        $response = $this->_curl->getBody();
        $response = json_decode($response);
        $foodArray = (array)$response;
        $a =  $foodArray['access_token'];
        return $a;
        // return [ "Authorization" => "Zoho-oauthtoken " .$a,
        // "Content-Type" => "application/form-data",
        // "Cache-Control"=>"no-cache"
        // ];
    }

    public function getorg()
    {
        return $this->scopeConfig->getValue('zohointegration/department/organization_id');
    }

    public function gettoken()
    {
        $refress = $this->scopeConfig->getValue('zohointegration/department/refress_token');
        $client =  $this->scopeConfig->getValue('zohointegration/department/client_id');
        $cs = $this->scopeConfig->getValue('zohointegration/department/client_secret');
        $redirect =  $this->scopeConfig->getValue('zohointegration/department/redirect_uri');
        $url = "https://accounts.zoho.com/oauth/v2/token?refresh_token=".$refress.
        "&client_id=".$client."&client_secret=".$cs."&redirect_uri=".$redirect."&grant_type=refresh_token";
        $this->_curl->post($url, " ");
        $response = $this->_curl->getBody();
        $response = json_decode($response);
        $foodArray = (array)$response;
        return $foodArray['access_token'];
    }

    public function getDeleteCustomerurl()
    {
        return "https://inventory.zoho.com/api/v1/contacts/";
    }

    public function getCustomerApi()
    {
        return self::CUSTOMER_API.'?organization_id='.
        $this->scopeConfig->getValue('zohointegration/department/organization_id', ScopeInterface::SCOPE_STORE);
    }

    public function getItemApi()
    {
        return self::ITEM_API . '?organization_id='.
        $this->scopeConfig->getValue('zohointegration/department/organization_id', ScopeInterface::SCOPE_STORE);
    }

    public function getPaymentApi()
    {
        return self::CUSTOMER_PAYMENT_API.
        $this->scopeConfig->getValue('zohointegration/department/organization_id', ScopeInterface::SCOPE_STORE);
    }

    public function getItemGrpApi()
    {
        return self::ITEMGRP_API . '?organization_id=' .
        $this->scopeConfig->getValue('zohointegration/department/organization_id', ScopeInterface::SCOPE_STORE);
    }

    public function getcompositeapi()
    {
        return self::COMPOSITETEM_API . '?organization_id=' .
         $this->scopeConfig->getValue('zohointegration/department/organization_id', ScopeInterface::SCOPE_STORE);
    }

    public function getcompositeeditapi()
    {
        return self::COMPOSITETEM_API;
    }

    public function getGeneralConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_TESTI .'department/'. $code, $storeId);
    }

    public function getItemeditApi()
    {
        return self::ITEMEDIT_API ;
    }

    public function getdeliveryApi()
    {
        return self::DELIVERY ;
    }

    public function getPacketApi()
    {
        return self::PACKET_ID.$this->scopeConfig
            ->getValue('zohointegration/department/organization_id', ScopeInterface::SCOPE_STORE);
    }

    public function getItemgrpeditApi()
    {
        return self::ITEMGRPEDIT_API;
    }

    public function getItemdeleteApi()
    {
        return self::ITEMDELETE_API ;
    }

    public function getzohosalesApi()
    {
        return self::SALES_ORDER;
    }

    public function getItemgrpdeleteApi()
    {
        return self::ITEMGRPDELETE_API ;
    }
    
    public function getsalesorderapi()
    {
        return self::SALESORDER_API. '?organization_id=' .
        $this->scopeConfig->getValue('zohointegration/department/organization_id', ScopeInterface::SCOPE_STORE);
    }

    public function getsalesordereditapi()
    {
        return self::SALESORDEREDIT_API ;
    }

    public function getinvoiceapi()
    {
        return self::INVOICE_API. '?organization_id=' .
        $this->scopeConfig->getValue('zohointegration/department/organization_id', ScopeInterface::SCOPE_STORE);
    }

    public function getPackage()
    {
        return self::PACKAGE_API. '?organization_id=' .
        $this->scopeConfig->getValue('zohointegration/department/organization_id', ScopeInterface::SCOPE_STORE);
    }

    public function getshipmentorder()
    {
        return self::SHIPMENTORDER_API. '?organization_id=' .
        $this->scopeConfig->getValue('zohointegration/department/organization_id', ScopeInterface::SCOPE_STORE);
    }

    public function getcreditnoteapi()
    {
        return self::CREDITNOTES_API. '?organization_id=' .
        $this->scopeConfig->getValue('zohointegration/department/organization_id', ScopeInterface::SCOPE_STORE);
    }
}
