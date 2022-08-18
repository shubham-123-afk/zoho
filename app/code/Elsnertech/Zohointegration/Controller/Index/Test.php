<?php
namespace Elsnertech\Zohointegration\Controller\Index;

class Test extends \Magento\Framework\App\Action\Action
{
	protected $_pageFactory;

	public function __construct(
		\Magento\Framework\App\Action\Context $context,
        \Elsnertech\Zohointegration\Model\ApiIntegration $apiintegrationl,
		\Magento\Framework\View\Result\PageFactory $pageFactory)
	{
		$this->_pageFactory = $pageFactory;
        $this->_apiintegrationl = $apiintegrationl;
		return parent::__construct($context);
	}

	public function execute()
	{
		$result1 = $this->_apiintegrationl->ProductApi();
        return $result1;
	}
}