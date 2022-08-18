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
use Magento\ConfigurableProduct\Model\Product\Type\Configurable; 
use Magento\Framework\App\Config\Storage\WriterInterface;
use Elsnertech\Zohointegration\Helper\Token;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Filesystem;

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
    const VARIANTTYPE = "sales";
    const PRODUCTTYPE = "goods";
    const VIRTUAL_PRODUCTTYPE = "service";
    const INVENTORY = "inventory";
    const INVENTORYASSET = "Inventory Asset";
    
    
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
        \Elsnertech\Zohointegration\Logger\Logger $customLogger,
        Configurable $configurable,
        WriterInterface $configWriter,
        Token $token,
        Filesystem $filesystem
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
        $this->configurable = $configurable;
        $this->_configWriter = $configWriter;
        $this->_token = $token;
    }

    public function CustomerApi() {
        $customerCollection = $this->_customerFactory->create();
        foreach ($customerCollection as $customer) {
            $customer_id[] = $customer->getId();
        }
        return $this->createCustomer($customer_id,count($customer_id));
    }

    public function VirtualItemsImport() {
        $productData = $this->_productloader->create();
        $zoho_unit = $this->scopeConfig->getValue('general/locale/weight_unit', ScopeInterface::SCOPE_STORE);
        $productCollection = $this->_productloader->create()->getCollection();
        $filepath = 'export/virtualitems.csv';
        $this->directory->create('export');
        $stream = $this->directory->openFile($filepath, 'w+');
        $stream->lock();
        $header = ['Item Name', 'Sales Description', 'Selling Price','Sales Account','Unit','Product Type','SKU','Purchase Price','Purchase Account','Opening Stock','Opening Stock Value','Item Type'];
        $stream->writeCsv($header);
        foreach ($productCollection as $product) {
            if ($product->getTypeId()=="downloadable" || $product->getTypeId()=="virtual" && $this->getParentProductId($product->getid())=="False"){
                $productData = $productData->load($product->getId());
                $qty = $productData->getQuantityAndStockStatus();
                $product_type = [];
                $product_type[] = $productData->getName();
                $product_type[] = $productData->getDescription();
                $product_type[] = $productData->getprice();
                $product_type[] = SELF::VARIANTTYPE;
                $product_type[] = $zoho_unit;
                $product_type[] = "service";
                $product_type[] = $productData->getSku();
                $product_type[] = $productData->getprice();
                $product_type[] = "Cost of Goods Sold";
                $product_type[] = (!empty($productData->getInitialStock())) ? ($productData->getInitialStock()) : ("0");
                $product_type[] = (!empty($productData->getInitialStock())) ? ($productData->getInitialStock()) : ("0");
                $product_type[] = "Sales and Purchases";
                $stream->writeCsv($product_type);
            }
        }
    }

    public function ItemsImport() {
        $productData = $this->_productloader->create();
        $zoho_unit = $this->scopeConfig->getValue('general/locale/weight_unit', ScopeInterface::SCOPE_STORE);
        $productCollection = $this->_productloader->create()->getCollection();
        $filepath = 'export/items.csv';
        $this->directory->create('export');
        $stream = $this->directory->openFile($filepath, 'w+');
        $stream->lock();
        $header = ['unit', 'item_type', 'product_type','description','name','rate','purchase_rate','initial_stock','initial_stock_rate','sku','weight'];
        $stream->writeCsv($header);
        foreach ($productCollection as $product) {
            if ($product->getTypeId()=="simple" && $this->getParentProductId($product->getid())=="False"){
                $productData = $productData->load($product->getid());
                $qty = $productData->getQuantityAndStockStatus();
                $product_type = [];
                $product_type[] = $zoho_unit;
                $product_type[] = self::INVENTORY;
                $product_type[] = self::PRODUCTTYPE;
                $product_type[] = strip_tags(str_replace('&nbsp;', ' ', $productData->getDescription()));
                $product_type[] = $productData->getName();
                $product_type[] = $productData->getprice();
                $product_type[] = $productData->getprice();
                $product_type[] = 0;
                $product_type[] = 0;
                $product_type[] = $productData->getSku();
                $product_type[] = $productData->getWeight();
                $stream->writeCsv($product_type);
            }
        }
    }

    public function ItemGroupImport(){
        $ProductModel = $this->_productloader->create();
        $productCollection = $this->_productloader->create()->getCollection();
        $filepath = 'export/itemsGroup.csv';
        $this->directory->create('export');
        $stream = $this->directory->openFile($filepath, 'w+');
        $stream->lock();
        $header = ['Product Name', 'unit', 'description','AttributeName1','AttributeName2','AttributeName3','Variant Type','Product Type','Variant Name','Variant Description','Selling Price','SKU','Purchase Price','AttributeOption1',"AttributeOption2",'AttributeOption3','Opening Stock','Opening Stock Value'];
        $stream->writeCsv($header);
        $unit = $this->scopeConfig->getValue('general/locale/weight_unit', ScopeInterface::SCOPE_STORE);
        foreach($productCollection as $productnew) {
            if ($productnew->getTypeId()=="configurable") {
                $new_id[] = $productnew->getId();
            }
        }

        foreach($new_id as $productnew) {
            $product = $ProductModel->load($productnew);
            $parent_name = $product->getName();
            $child_products = $product->getTypeInstance()->getUsedProducts($product);
            $data = $product->getTypeInstance()->getConfigurableOptions($product);
            $options = array();
            foreach($data as $attr){
                foreach($attr as $p){
                    $title[] = $p['attribute_code'];
                    $options[$p['sku']][$p['attribute_code']] = $p['option_title'];
                }
            }
            foreach($options as $sku =>$d){
                $child_product = $product->loadByAttribute('sku', $sku);
                $childId = $child_product->getEntityId();
                $child_product =  $ProductModel->load($childId);
                $qty = $child_product->getQuantityAndStockStatus();
                foreach($d as $key=>$value) {
                    $attribute_value[] = $value;
                    $attribute_key[] = $key;
                }
                $count_total_attribute_key = count(array_unique($attribute_key));
                $count_total_attribute_value = count(array_unique($attribute_value));
                $product_type = [];
                $product_type[] = $parent_name;
                $product_type[] = $unit;
                $product_type[] = $product->getDescription();
                $product_type[] = $attribute_key[0];
                $product_type[] = ($count_total_attribute_key>=2) ? ($attribute_key[1]) : (" ");
                $product_type[] = ($count_total_attribute_key==3) ? ($attribute_key[2]) : (" ");
                $product_type[] = self::VARIANTTYPE;
                $product_type[] = self::PRODUCTTYPE;
                $product_type[] = $child_product->getName();
                $product_type[] = $child_product->getDescription();
                $product_type[] = (!empty($child_product->getPrice())) ? ($child_product->getPrice()) : (0);
                $product_type[] = $sku;
                $product_type[] = (!empty($child_product->getPrice())) ? ($child_product->getPrice()) : (0);
                $product_type[] = $d[$attribute_key[0]];
                $product_type[] = ($count_total_attribute_key>=2) ? ($d[$attribute_key[1]]) : (" ");
                $product_type[] = ($count_total_attribute_key==3) ? ($d[$attribute_key[2]]) : (" ");
                $product_type[] = $qty['qty'];
                $product_type[] = $qty['qty'];
                $stream->writeCsv($product_type);
            }
        } 
    }

    public function CompositeImport() {
        $productData = $this->_productloader->create();
        $zoho_unit = $this->scopeConfig->getValue('general/locale/weight_unit', ScopeInterface::SCOPE_STORE);
        $productCollection = $this->_productloader->create()->getCollection();
        $filepath = 'export/compositeitem.csv';
        $this->directory->create('export');
        $stream = $this->directory->openFile($filepath, 'w+');
        $stream->lock();
        $header = ['Composite Item Name','Selling Price', 'Product Type','Unit','SKU','Purchase Price','Opening Stock','Opening Stock Value','Status','Purchase Account','Inventory Account','Mapped Item Name','Mapped Quantity'];
        $stream->writeCsv($header);
        
        foreach ($productCollection as $product) {
            if ($product->getTypeId()=="grouped" && $this->getParentProductId($product->getid())=="False"){
                $productData = $productData->load($product->getId());
                $name = $productData->getName();
                $sku = $productData->getsku();
                $qty = $productData->getInitialStock();
                $_children = $product->getTypeInstance()->getAssociatedProducts($product);
                foreach ($_children as $child) {
                    $childproductData = $productData->load($child->getId());
                    $childqty = $childproductData->getQuantityAndStockStatus();
                    $product_type = [];
                    $product_type[] = $name;
                    $product_type[] = $childproductData->getPrice();
                    $product_type[] = SELF::PRODUCTTYPE;
                    $product_type[] = $zoho_unit;
                    $product_type[] = $sku;
                    $product_type[] = $childproductData->getPrice();
                    $product_type[] = (!empty($qty)) ? ($qty) : ("0");
                    $product_type[] = (!empty($qty)) ? ($qty) : ("0");
                    $product_type[] = $productData->getStatus();
                    $product_type[] = "Cost of Goods Sold";
                    $product_type[] = SELF::INVENTORYASSET;
                    $product_type[] = $child->getName();
                    $product_type[] = $childqty['qty'];
                    $stream->writeCsv($product_type);
                }
            }
        }
    }

    public function BundleImport() {
        $productData = $this->_productloader->create();
        $zoho_unit = $this->scopeConfig->getValue('general/locale/weight_unit', ScopeInterface::SCOPE_STORE);
        $productCollection = $this->_productloader->create()->getCollection();
        $filepath = 'export/bundleitem.csv';
        $this->directory->create('export');
        $stream = $this->directory->openFile($filepath, 'w+');
        $stream->lock();
        $header = ['Composite Item Name','Selling Price', 'Product Type','Unit','SKU','Purchase Price','Opening Stock','Opening Stock Value','Status','Purchase Account','Inventory Account','Mapped Item Name','Mapped Quantity'];
        $stream->writeCsv($header);
        
        foreach ($productCollection as $product) {
            if ($product->getTypeId() == 'bundle') {
                $typeInstance = $product->getTypeInstance();
                $productData = $productData->load($product->getId());
                $prentName = $productData->getName();
                $qty = $productData->getInitialStock();
                $Sku = $productData->getSku();
                $requiredChildrenIds = $typeInstance->getChildrenIds($product->getId(), false);
                foreach ($requiredChildrenIds as $Childrenkey => $Childrenvalue) {
                    foreach ($Childrenvalue as $key => $value) {
                        $child = $productData->load($value);
                        $childqty = $child->getQuantityAndStockStatus();
                        $product_type = [];
                        $product_type[] = $prentName;
                        $product_type[] = $child->getPrice();
                        $product_type[] = self::PRODUCTTYPE;
                        $product_type[] = $zoho_unit;
                        $product_type[] = $Sku;
                        $product_type[] = $child->getPrice();
                        $product_type[] = $qty;
                        $product_type[] = $qty;
                        $product_type[] = $child->getStatus();
                        $product_type[] = "Cost of Goods Sold";
                        $product_type[] = self::INVENTORYASSET;
                        $product_type[] = $child->getName();
                        $product_type[] = $childqty['qty'];
                        $stream->writeCsv($product_type);
                    }
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
                    $this->_curl->setHeaders($this->_token->getHeaders());
                    $this->_curl->post($this->getCustomerApi(), json_encode($customer_api_list));
                    $response = $this->_curl->getBody();
                    $response = json_decode($response, true);
                    if (isset($response['contact']['contact_id'])) {
                        $this->_Api->zohoId($customer->getEmail(),$response['contact']['contact_id']);
                        $this->_customLogger->info($customer->getEmail().'Customer create message');
                    }  
                } catch (\Exception $e) {

                }
            } else {
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
                $url = $this->getCustomerPutApi($customer->getzohoCustomerId());
                $this->_Api->makeApiRequest($url,"{'contact_persons': [{'email': '',}]}","PUT");
                $this->_Api->makeApiRequest($url,json_encode($customer_api_list),"PUT");
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
                "zip"=>$billingaddress->getData('postcode'),
                "country"=> $billingaddress->getData('country_id')
            ];
            return $billing_address;
    
        }
    }

    public function getStoreCode() {
        return $this->_storeManager->getStore()->getName();
    }

    public function getItemeditApi(){
        return self::ITEMEDIT_API ;
    }

    public function getWebsiteName($websiteId) {
        $collection = $this->_websiteModel->load($websiteId,'website_id');
        return $collection->getName();
    }

    public function getorg(){
        return $this->scopeConfig->getValue('zohointegration/department/organization_id');
    }

    public function getCustomerApi() {
        return self::CUSTOMER_API.'?organization_id='.
        $this->scopeConfig->getValue('zohointegration/department/organization_id');
    }

    public function getCustomerPutApi($id) {
        return self::CUSTOMER_API.'/'.$id.'?organization_id='.
        $this->scopeConfig->getValue('zohointegration/department/organization_id');
    }

    public function getItemApi(){
        return self::ITEM_API . '?organization_id='.
        $this->scopeConfig->getValue('zohointegration/department/organization_id');
    }

    public function getParentProductId($childProductId) {
        $parentConfigObject = $this->configurable->getParentIdsByChild($childProductId);
	    if($parentConfigObject) {
		    return $parentConfigObject[0];
	    }else {
            $info = "False";
            return $info;
        }
    }
}
