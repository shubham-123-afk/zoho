<?php
namespace Elsnertech\Zohointegration\Block\Adminhtml;
class Integration extends \Magento\Framework\View\Element\Template
{
	public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }

    public function getStoreUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    public function getAjaxCustomersUrl()
    {
        return $this->getUrl('zoho_integration/system/customers');
    }

    public function getAjaxProductsUrl()
    {
        return $this->getUrl('zoho_integration/system/products');
    }

    public function getAjaxSalesOrdersUrl()
    {
        return $this->getUrl('zoho_integration/system/salesorders');
    }
}