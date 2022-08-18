<?php
/**
 * Class Data
 *
 * @package Elsnertech\Zohointegration\Cron
 */
namespace Elsnertech\Zohointegration\Cron;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface; 

class Token extends AbstractHelper
{
    const TOKEN = "https://accounts.zoho.com/oauth/v2/token?refresh_token=";

    public function __construct(
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->_curl = $curl;
        $this->scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }

    public function execute() {
        $refress = $this->scopeConfig->getValue('zohointegration/department/refress_token');
        $client =  $this->scopeConfig->getValue('zohointegration/department/client_id');
        $cs = $this->scopeConfig->getValue('zohointegration/department/client_secret');
        $redirect =  $this->scopeConfig->getValue('zohointegration/department/redirect_uri');
        $url = self::TOKEN.$refress."&client_id=".
        $client."&client_secret=".$cs."&redirect_uri=".$redirect."&grant_type=refresh_token";
        $this->_curl->post($url, " ");
        $response = $this->_curl->getBody();
        $response = json_decode($response);
        $foodArray = (array)$response;
        $access_token =  $foodArray['access_token'];
        $websites = $this->_storeManager->getWebsites();
        $scope = "websites";
        foreach($websites as $website) {
            $this->_configWriter->save('zohointegration/departmes/access_token', "test", $scope, $website->getId());
        }
        return [
            "Authorization" => "Zoho-oauthtoken ".$access_token,
            "Content-Type" => "application/json",
            "Cache-Control"=>"no-cache"
        ];
    }
}
