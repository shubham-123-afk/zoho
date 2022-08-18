<?php
namespace Elsnertech\Zohointegration\Controller\Index;

class Save extends \Magento\Framework\App\Action\Action
{
	protected $_pageFactory;
	protected $_curl;

	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Framework\HTTP\Client\Curl $curl,
		 \Magento\Framework\App\RequestInterface $request,
		\Magento\Framework\View\Result\PageFactory $pageFactory)
	{
		$this->_pageFactory = $pageFactory;
		$this->_curl = $curl;
		$this->request = $request;
		return parent::__construct($context);
	}

	public function execute()
	{

		$n1 = $this->request->getParam('n1');
		$n2 = $this->request->getParam('n2');
		$n3 = $this->request->getParam('n2');
		$n4 = $this->request->getParam('n4');

		$url="https://accounts.zoho.com/oauth/v2/token?code=".$n1."&client_id=".$n2."&client_secret=".$n3."&redirect_uri=".$n4."&grant_type=authorization_code";
        $this->_curl->post($url,"");
        $response = $this->_curl->getBody();
        //$response = json_decode($response, true);
        echo $response;
	}
}