<?php

namespace Elsnertech\Zohointegration\Cron;
use Elsnertech\Zohointegration\Model\ApiIntegration;

class CustomerSync
{
	protected $_apiintegrationl;

    public function __construct(
        ApiIntegration $apiintegrationl
    ) {
        $this->_apiintegrationl = $apiintegrationl;
    }
	public function execute() {
		try{
			$customer = $this->_apiintegrationl->CustomerApi();
			return $customer;			
		} catch (\Exception $e) {
			$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/templog.log');
			$logger = new \Zend\Log\Logger();
			$logger->addWriter($writer);
			$logger->info("Info ". $e );
		}
	}
}