<?php
/**
 * Class Data
 *
 * @package Elsnertech\Zohointegration\Helper
 */
namespace Elsnertech\Zohointegration\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Token extends AbstractHelper
{
    const TOKEN = "https://accounts.zoho.com/oauth/v2/token?refresh_token=";

    public function __construct(
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_curl = $curl;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
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
        // $a =  $foodArray['access_token'];
        return [ "Authorization" => "Zoho-oauthtoken ".$this->scopeConfig->getValue('zohointegration/departmes/access_token'),
        "Content-Type" => "application/json",
        "Cache-Control"=>"no-cache"
        ];
    }
}
