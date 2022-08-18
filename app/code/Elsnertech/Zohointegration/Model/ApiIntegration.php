<?php
namespace Elsnertech\Zohointegration\Model;

use Elsnertech\Zohointegration\Model\Api;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use Magento\Eav\Model\Config;

class ApiIntegration extends \Magento\Framework\Model\AbstractModel
{
    const CUSTOMER_API = "https://inventory.zoho.com/api/v1/contacts";

    const ITEM_API = "https://inventory.zoho.com/api/v1/items";
    const ITEMDELETE_API = "https://inventory.zoho.com/api/v1/items/";
    const ITEMEDIT_API = "https://inventory.zoho.com/api/v1/items/";
    const COMPOSITETEM_API = "https://inventory.zoho.com/api/v1/compositeitems";
    const ITEMGRP_API = "https://inventory.zoho.com/api/v1/itemgroups";
    const ITEMGRPDELETE_API = "https://inventory.zoho.com/api/v1/itemgroups/";
    const ITEMGRPEDIT_API = "https://inventory.zoho.com/api/v1/itemgroups/";
    const SALES_ORDER = "https://inventory.zoho.com/api/v1/salesorders/";
    const SALESORDER_API = "https://inventory.zoho.com/api/v1/salesorders";
    const SALESORDEREDIT_API = "https://inventory.zoho.com/api/v1/salesorders/";
    const INVOICE_API = "https://inventory.zoho.com/api/v1/invoices";
    const PACKAGE_API = "https://inventory.zoho.com/api/v1/packages";
    const SHIPMENTORDER_API = "https://inventory.zoho.com/api/v1/shipmentorders";
    const CREDITNOTES_API = " https://inventory.zoho.com/api/v1/creditnotes";
    const CUSTOMER_PAYMENT_API = "https://inventory.zoho.com/api/v1/customerpayments?organization_id=";
    const PACKET_ID = "https://inventory.zoho.com/api/v1/packages?organization_id=";
    const DELIVERY = "https://inventory.zoho.com/api/v1/shipmentorders/";
    const TOKEN = "https://accounts.zoho.com/oauth/v2/token?refresh_token=";

    protected $_customerFactory;
    protected $_productFactory;
    protected $_productloader;
    protected $_modelProductFactory;

    public function __construct(
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerFactory,
        \Magento\Customer\Model\ResourceModel\Customer\Collection $customerCollection,
        \Magento\Customer\Model\ResourceModel\Customer $customerResource,
        CustomerFactory $customerloader,
        \Magento\Customer\Api\CustomerRepositoryInterface $CustomerRepositoryInterface,
        \Magento\Customer\Model\AddressFactory $addressFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productFactory,
        \Magento\Catalog\Model\ResourceModel\Product\Collection $productDataCollection,
        \Magento\Catalog\Model\ProductFactory $productloader,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Catalog\Api\CategoryLinkManagementInterface $categoryLink,
        \Magento\Catalog\Model\Product $ModelProductFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $ProductRepositoryInterface,
        \Magento\Catalog\Api\Data\ProductLinkInterfaceFactory $ProductLinkInterface,
        StoreManagerInterface $storeManager,
        Website $websiteModel,
        ScopeConfigInterface $scopeConfig,
        Curl $curl,
        Api $api,
        Config $eavConfig
        // \Elsnertech\Zohointegration\Logger\Logger $customLogger
    ) {
        $this->_customerFactory = $customerFactory;
        $this->_customerCollection = $customerCollection;
        $this->_customerResource = $customerResource;
        $this->_customerloader = $customerloader;
        $this->_addressFactory = $addressFactory;
        $this->_customerRepositoryInterface = $CustomerRepositoryInterface;
        $this->_productDataCollection = $productDataCollection;
        $this->_productFactory = $productFactory;
        $this->_modelProductFactory = $ModelProductFactory;
        $this->_productloader = $productloader;
        $this->_ProductRepositoryInterface = $ProductRepositoryInterface;
        $this->_ProductLinkInterface = $ProductLinkInterface;
        $this->stockRegistry = $stockRegistry;
        $this->categoryLink = $categoryLink;
        $this->_storeManager = $storeManager;
        $this->_websiteModel = $websiteModel;
        $this->scopeConfig = $scopeConfig;
        $this->_curl = $curl;
        $this->_Api = $api;
        $this->_eavConfig = $eavConfig;
        // $this->_customLogger = $customLogger;
    }

    public function CustomerApi()
    {
        $this->_curl->setHeaders($this->getHeaders());
        $this->_curl->get($this->getCustomerApi());
        $response = $this->_curl->getBody();
        $data = json_decode($response, true);

        $customerCollection = $this->_customerFactory->create();
        foreach ($customerCollection as $customerData) {
            $customer_entity_id[] = $customerData->getEntity_id();
            $customer_email_id[] = $customerData->getEmail();
        }

        foreach ($customer_entity_id as $customerZohoId) {
            $customerData = $this->_customerloader->create()->load($customerZohoId);
            $zoho_customer_id[] = $customerData->getZohoId();
        }

        $customerMageData = $this->_customerCollection;
        $collection = $customerMageData->addAttributeToSelect('*')
            ->addAttributeToFilter('zoho_id', $zoho_customer_id)
            ->load();
        foreach ($collection as $customerMageId) {
            $c_data = $customerMageId->getData();
            $magento_customer_id[$c_data['zoho_id']] = $c_data['entity_id'];
        }

        if (isset($data['contacts']) && count($data['contacts']) > 0) {
            foreach ($data['contacts'] as $customer) {

                $ID = $customer['contact_id'];
                $this->_curl->setHeaders($this->getHeaders());
                $this->_curl->get($this->getCustomerApiById($ID));
                $response = $this->_curl->getBody();
                $datas = json_decode($response, true);

                $Data['contact_id'] = $datas['contact']['contact_id'];
                $Data['first_name'] = $datas['contact']['first_name'];
                $Data['last_name'] = $datas['contact']['last_name'];
                $Data['email'] = $datas['contact']['email'];
                $Data['company_name'] = $datas['contact']['company_name'];
                $Data['country_code'] = $datas['contact']['billing_address']['country_code'];
                $Data['zip'] = $datas['contact']['billing_address']['zip'];
                $Data['city'] = $datas['contact']['billing_address']['city'];
                $Data['phone'] = $datas['contact']['billing_address']['phone'];
                $Data['address'] = $datas['contact']['billing_address']['address'];
                $Data['street2'] = $datas['contact']['billing_address']['street2'];
                $Data['state'] = $datas['contact']['billing_address']['state'];
                $Data['country'] = $datas['contact']['billing_address']['country'];
                $Data['Shipping_country_code'] = $datas['contact']['shipping_address']['country_code'];
                $Data['Shipping_zip'] = $datas['contact']['shipping_address']['zip'];
                $Data['Shipping_city'] = $datas['contact']['shipping_address']['city'];
                $Data['Shipping_phone'] = $datas['contact']['shipping_address']['phone'];
                $Data['Shipping_address'] = $datas['contact']['shipping_address']['address'];
                $Data['Shipping_street2'] = $datas['contact']['shipping_address']['street2'];
                $Data['Shipping_state'] = $datas['contact']['shipping_address']['state'];
                $Data['Shipping_country'] = $datas['contact']['shipping_address']['country'];

                if (!in_array($Data['contact_id'], $zoho_customer_id)) {
                    if (!in_array($Data['email'], $customer_email_id)) {
                        $temp = $Data['email'];
                        $EmailRight = (in_array($temp, $customer_email_id));
                        if ($EmailRight != true) {
                            try {

                                $websiteId = $this->_storeManager->getWebsite()->getWebsiteId();
                                $customer = $this->_customerloader->create();
                                $customer->setWebsiteId($websiteId);

                                // Preparing data for new customer
                                $customer->setZohoId($Data['contact_id']);
                                $customer->setfirstname($Data['first_name']);
                                $customer->setlastname($Data['last_name']);
                                $customer->setemail($Data['email']);
                                $customer->setpassword("MageNewAC");

                                // Save Customer data
                                $customer->save();
                                $customer->sendNewAccountEmail();

                                $latestCustomerId = $customer->getId();

                                // Preparing Billing Address Data for customer
                                $billingAddress = $this->_addressFactory->create();

                                if (!empty($Data['state'] && $Data['country_code'] && $Data['city'] && $Data['zip'] && $Data['phone'] && $Data['address'])) {

                                    $billingAddress->setCustomerId($latestCustomerId)
                                        ->setFirstname($Data['first_name'])
                                        ->setLastname($Data['last_name'])
                                        ->setCountryId($Data['country_code'])
                                        ->setRegion($Data['state'])
                                        ->setPostcode($Data['zip'])
                                        ->setCity($Data['city'])
                                        ->setTelephone($Data['phone'])
                                        ->setCompany($Data['company_name'])
                                        ->setStreet($Data['address'])
                                        ->setIsDefaultBilling('1')
                                        ->setIsDefaultShipping('0')
                                        ->setSaveInAddressBook('1');

                                    // Save Billing Address
                                    $billingAddress->save();
                                }

                                // Preparing Shipping Address Data for customer
                                $shippingAddress = $this->_addressFactory->create();

                                if (!empty($Data['state'] && $Data['country_code'] && $Data['city'] && $Data['zip'] && $Data['phone'] && $Data['address'])) {
                                    $shippingAddress->setCustomerId($latestCustomerId)
                                        ->setFirstname($Data['first_name'])
                                        ->setLastname($Data['last_name'])
                                        ->setCountryId($Data['Shipping_country_code'])
                                        ->setRegion($Data['Shipping_state'])
                                        ->setPostcode($Data['Shipping_zip'])
                                        ->setCity($Data['Shipping_city'])
                                        ->setTelephone($Data['Shipping_phone'])
                                        ->setCompany($Data['company_name'])
                                        ->setStreet($Data['Shipping_address'])
                                        ->setIsDefaultShipping(true)
                                        ->setIsDefaultBilling(false)
                                        ->setSaveInAddressBook('1');

                                    // Save Shipping Address
                                    $shippingAddress->save();
                                }

                            } catch (Exception $e) {
                                return "We can't able to create Customer now." . $e->getMessage();
                            }
                        }
                    }
                }

                if (in_array($Data['contact_id'], $zoho_customer_id)) {
                    try {

                        $websiteId = $this->_storeManager->getWebsite()->getWebsiteId();

                        $customerRepository = $this->_customerRepositoryInterface;
                        $customer = $customerRepository->getById($magento_customer_id[$Data['contact_id']]);

                        $customer->setfirstname($Data['first_name']);
                        $customer->setlastname($Data['last_name']);

                        if (!in_array($Data['email'], $customer_email_id)) {
                            $customer->setemail($Data['email']);
                        }

                        // Save Customer data
                        $customerRepository->save($customer);

                        $latestCustomerId = $customer->getId();

                        // Preparing Billing Address Data for customer
                        $billingAddress = $this->_addressFactory->create();

                        if (!empty($Data['state'] && $Data['country_code'] && $Data['city'] && $Data['zip'] && $Data['phone'] && $Data['address'])) {

                            $billingAddress->setCustomerId($latestCustomerId)
                                ->setFirstname($Data['first_name'])
                                ->setLastname($Data['last_name'])
                                ->setCountryId($Data['country_code'])
                                ->setRegion($Data['state'])
                                ->setPostcode($Data['zip'])
                                ->setCity($Data['city'])
                                ->setTelephone($Data['phone'])
                                ->setCompany($Data['company_name'])
                                ->setStreet($Data['address'])
                                ->setIsDefaultBilling('1')
                                ->setIsDefaultShipping('0')
                                ->setSaveInAddressBook('1');

                            // Save Billing Address
                            $billingAddress->save();
                        }

                        // Preparing Shipping Address Data for customer
                        $shippingAddress = $this->_addressFactory->create();

                        if (!empty($Data['state'] && $Data['country_code'] && $Data['city'] && $Data['zip'] && $Data['phone'] && $Data['address'])) {
                            $shippingAddress->setCustomerId($latestCustomerId)
                                ->setFirstname($Data['first_name'])
                                ->setLastname($Data['last_name'])
                                ->setCountryId($Data['Shipping_country_code'])
                                ->setRegion($Data['Shipping_state'])
                                ->setPostcode($Data['Shipping_zip'])
                                ->setCity($Data['Shipping_city'])
                                ->setTelephone($Data['Shipping_phone'])
                                ->setCompany($Data['company_name'])
                                ->setStreet($Data['Shipping_address'])
                                ->setIsDefaultShipping(true)
                                ->setIsDefaultBilling(false)
                                ->setSaveInAddressBook('1');

                            // Save Shipping Address
                            $shippingAddress->save();
                        }

                    } catch (Exception $e) {
                        return "We can't able to create Customer now." . $e->getMessage();
                    }
                }
            }
            return "Customer Created Successfully";
        }
        // $customerCollection = $this->_customerFactory->create();
        // foreach ($customerCollection as $customer) {
        //     $customer_id[] = $customer->getEmail();
        // }
        // print_r($customer_id);die;
        // return $this->createCustomer($customer_id,count($customer_id));
    }

    public function ProductApi()
    {
        // $this->_curl->setHeaders($this->getHeaders());
        // $this->_curl->get($this->getProductApi());
        // $response = $this->_curl->getBody();
        // $data = json_decode($response, true);
        // $this->_curl->setHeaders($this->getHeaders());
        // $this->_curl->get($this->getCompositeProductApi());
        // $response = $this->_curl->getBody();
        // $composite = json_decode($response, true);
        // foreach ($composite['composite_items'] as $product) {
        //     $compositeSKU[] = $product['sku'];
        // }

        $this->_curl->setHeaders($this->getHeaders());
        $this->_curl->get($this->getConfigurableProductApi());
        $response = $this->_curl->getBody();
        $configurable = json_decode($response, true);
        // echo "<pre>";print_r($Configurable);die;
            
        if (isset($configurable['itemgroups']) && count($configurable['itemgroups'])) {
           $itemgroup = $configurable['itemgroups'];
            foreach ($itemgroup as $config_product) {
                $zoho_group_id =  $config_product['group_id'];
                $zoho_product_id =  $config_product['group_name'];
                $zoho_product_description =  $config_product['description'];
                $zoho_attribute_name1 =  $config_product['attribute_name1'];
                $zoho_attribute_name2 =  $config_product['attribute_name2'];
                $zoho_attribute_name3 =  $config_product['attribute_name3'];
                $all_atttribute = [];
                $attribute_name1 = $this->isProductAttributeExists($zoho_attribute_name1);
                if (!is_null($zoho_attribute_name2)) {
                    $attribute_name2 = $this->isProductAttributeExists($zoho_attribute_name2);
                }
                if (!is_null($zoho_attribute_name3)) {
                    $attribute_name3 = $this->isProductAttributeExists($zoho_attribute_name3);
                }
                foreach($config_product['items'] as $item) {
                    echo "<pre>";print_r($item);
                    $sku[] = $item['sku']; 
                    // echo $this->createSimpleProduct($item);
                }
                print_r($sku);die;
            }
           die;
        }

        if (isset($composite['composite_items']) && count($composite['composite_items']) > 0) {
            foreach ($composite['composite_items'] as $composites) {
                $compositeID = $composites['composite_item_id'];
                $this->_curl->setHeaders($this->getHeaders());
                $this->_curl->get($this->getCompositeProductApiById($compositeID));
                $response = $this->_curl->getBody();
                $composites = json_decode($response, true);

                $compositeData['composite_item_id'] = $composites['composite_item']['composite_item_id'];
                $compositeData['sku'] = $composites['composite_item']['sku'];
                $compositeData['name'] = $composites['composite_item']['name'];
                $compositeData['rate'] = $composites['composite_item']['rate'];
                $compositeData['description'] = $composites['composite_item']['description'];
                $compositeData['available_stock'] = $composites['composite_item']['available_stock'];
                $compositeData['mapped_items'] = $composites['composite_item']['mapped_items'];
                $ass = [];
                foreach ($compositeData['mapped_items'] as $associateItems) {
                    $ass[] = $associateItems['sku'];
                }

                if (!in_array($compositeData['composite_item_id'], $ZohoGroupedProductId)) {
                    try {
                        $product = $this->_productloader->create();
                        $product->setzohoproatt($compositeData['composite_item_id']);
                        $product->setsku($compositeData['sku']);
                        $product->settype_id('grouped');
                        $product->setstatus(1);
                        $product->setprice_type(0);
                        $product->setsetShipmentType(0);
                        $product->setname($compositeData['name']);
                        $product->setdescription($compositeData['description']);
                        $product->setvisibility(4);
                        $product->setAttributeSetId(4);
                        $product->setWebsiteIds(array(1));
                        $product->setTaxClassId(0);

                        $product->save();

                        $latestProductSku = $product->getsku();

                        $categoryIds = array(2);
                        $this->categoryLink->assignProductToCategories($latestProductSku, $categoryIds);

                        $stockItem = $this->stockRegistry->getStockItemBySku($latestProductSku);
                        $stockItem->setQty($compositeData['available_stock']);
                        $stockItem->setIsInStock((bool) $compositeData['available_stock']);
                        $this->stockRegistry->updateStockItemBySku($latestProductSku, $stockItem);

                        $associated_array = [];
                        $associated_product_position = 0;

                        foreach ($ass as $asso) {
                            $productId = $this->_modelProductFactory->getIdBySku($asso);
                            $product_repository_interface = $this->_ProductRepositoryInterface->getById($productId);
                            $product_link_interface = $this->_ProductLinkInterface->create();
                            $product_link_interface->setSku($product->getSku())
                                ->setLinkType('associated')
                                ->setLinkedProductSku($product_repository_interface->getSku())
                                ->setLinkedProductType($product_repository_interface->getTypeId())
                                ->setPosition($associated_product_position)
                                ->getExtensionAttributes()
                                ->setQty(1);
                            $associated_array[] = $product_link_interface;
                            $associated_product_position++;
                            $product->setProductLinks($associated_array);
                            $product->save();
                        }
                        if ($product->getId()) {
                            echo "Grouped Product Created Successfully";
                        }
                    } catch (Exception $e) {
                        return "We can't able to create Grouped Product now." . $e->getMessage();
                    }
                }

                if (in_array($compositeData['composite_item_id'], $ZohoGroupedProductId)) {
                    try {
                        $product = $this->_modelProductFactory->load($MagentoGroupedId[$compositeData['composite_item_id']]);
                        $product->setzohoproatt($compositeData['composite_item_id']);
                        $product->setsku($compositeData['sku']);
                        $product->settype_id('grouped');
                        $product->setstatus(1);
                        $product->setprice_type(0);
                        $product->setsetShipmentType(0);
                        $product->setname($compositeData['name']);
                        $product->setdescription($compositeData['description']);
                        $product->setvisibility(4);
                        $product->setAttributeSetId(4);
                        $product->setWebsiteIds(array(1));
                        $product->setTaxClassId(0);
                        $product->save();

                        $latestProductSku = $product->getsku();

                        $categoryIds = array(2);
                        $this->categoryLink->assignProductToCategories($latestProductSku, $categoryIds);

                        $stockItem = $this->stockRegistry->getStockItemBySku($latestProductSku);
                        $stockItem->setQty($compositeData['available_stock']);
                        $stockItem->setIsInStock((bool) $compositeData['available_stock']);
                        $this->stockRegistry->updateStockItemBySku($latestProductSku, $stockItem);

                        $associated_array = [];
                        $associated_product_position = 0;

                        foreach ($ass as $asso) {

                            $productId = $this->_modelProductFactory->getIdBySku($asso);
                            $product_repository_interface = $this->_ProductRepositoryInterface->getById($productId);
                            $product_link_interface = $this->_ProductLinkInterface->create();
                            $product_link_interface->setSku($product->getSku())
                                ->setLinkType('associated')
                                ->setLinkedProductSku($product_repository_interface->getSku())
                                ->setLinkedProductType($product_repository_interface->getTypeId())
                                ->setPosition($associated_product_position)
                                ->getExtensionAttributes()
                                ->setQty(1);
                            $associated_array[] = $product_link_interface;
                            $associated_product_position++;
                            $product->setProductLinks($associated_array);
                            $product->save();
                        }
                        if ($product->getId()) {
                            echo "Grouped Product Updated Successfully";
                        }
                    } catch (Exception $e) {
                        return "We can't able to create Grouped Product now." . $e->getMessage();
                    }
                }
            }
        }

        if (isset($Configurable['itemgroups']) && count($Configurable['itemgroups']) > 0) {
            foreach ($Configurable['itemgroups'] as $configurables) {
                $configurableID = $configurables['group_id'];
                $this->_curl->setHeaders($this->getHeaders());
                $this->_curl->get($this->getConfigurableProductApiById($configurableID));
                $response = $this->_curl->getBody();
                $Configurables = json_decode($response, true);
                echo "<pre>";
                echo "TESTDATA123";
                print_r($Configurable);die;

                $ConfigurableData['group_id'] = $Configurables['item_group']['group_id'];
                $ConfigurableData['group_name'] = $Configurables['item_group']['group_name'];
                $ConfigurableData['description'] = $Configurables['item_group']['description'];

                $ConfigurableData['attributes'] = $Configurables['item_group']['attributes'];
                $attrName = [];
                $attrOptions = [];
                foreach ($ConfigurableData['attributes'] as $attributesZoho) {
                    $attrName[] = $attributesZoho['name'];
                    // $attrOptions[] = $attributesZoho['options'];
                }

                // $options = [];
                // foreach ($attrName as $attrNames) {
                //     $options[] = [
                //         [
                //             'title' => $attrNames,
                //             'type' => 'drop_down',
                //             'is_required' => 1,
                //             'sort_order' => 0,
                //         ],
                //     ];
                // }

                $options = [
                    [
                        'title' => 'New',
                        'type' => 'drop_down',
                        'is_required' => 1,
                        'sort_order' => 0,
                        'values' => [
                            [
                                'title' => 'Blue',
                                'price' => 10.50,
                                'price_type' => 'fixed',
                                'sku' => '',
                                'sort_order' => 0,
                            ],
                        ],
                    ],
                    [
                        'title' => 'New_Two',
                        'type' => 'drop_down',
                        'is_required' => 1,
                        'sort_order' => 0,
                        'values' => [
                            [
                                'title' => 'S',
                                'price' => 0,
                                'price_type' => 'fixed',
                                'sku' => '',
                                'sort_order' => 0,
                            ],
                        ],
                    ],
                ];

                // print_r($attrOptions);die;
                // $attrOptionsDatas = [];
                // foreach ($attrOptions as $attrOptionsData) {
                //     $attrOptionsDatas[] = $attrOptionsData['name'];
                // }

                $ConfigurableData['items'] = $Configurables['item_group']['items'];
                $assGroupSku = [];
                $assGroupRate = [];
                foreach ($ConfigurableData['items'] as $associateItemsGroup) {
                    $assGroupSku[] = $associateItemsGroup['sku'];
                    $assGroupRate[] = $associateItemsGroup['rate'];
                }
                foreach ($assGroupSku as $assoGroup) {
                    $configProductId[] = $this->_modelProductFactory->getIdBySku($assoGroup);
                }

                if (!in_array($ConfigurableData['group_id'], $ZohoSimpleProductId)) {
                    try {
                        $product = $this->_productloader->create();

                        $product->setzoho_data($ConfigurableData['group_id']);
                        $product->setname($ConfigurableData['group_name']);
                        $product->setsku($ConfigurableData['group_name']);
                        $product->setprice($assGroupRate);
                        $product->settype_id('configurable');
                        $product->setstatus(1);
                        $product->setdescription($ConfigurableData['description']);
                        $product->setvisibility(4);
                        $product->setAttributeSetId(4);
                        $product->setWebsiteIds(array(1));
                        $product->setTaxClassId(0);
                        $product->setStockData(array(
                            'use_config_manage_stock' => 0,
                            'manage_stock' => 1,
                            'is_in_stock' => 1,
                        )
                        );

                        $product->save();

                        $latestProductSku = $product->getsku();

                        $associated_array = [];
                        $associated_product_position = 0;

                        foreach ($options as $arrayOption) {
                            $product->setHasOptions(1);
                            $product->getResource()->save($product);
                            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                            $option = $objectManager->create('\Magento\Catalog\Model\Product\Option')
                                ->setProductId($product->getId())
                                ->setStoreId($product->getStoreId())
                                ->addData($arrayOption);
                            $option->save();
                            $product->addOption($option);
                        }

                        foreach ($configProductId as $config_product_id) {

                            $product_repository_interface = $this->_ProductRepositoryInterface->getById($config_product_id);
                            $product_link_interface = $this->_ProductLinkInterface->create();
                            $product_link_interface->setSku($product->getSku())
                                ->setLinkType('associated')
                                ->setLinkedProductSku($product_repository_interface->getSku())
                                ->setLinkedProductType($product_repository_interface->getTypeId())
                                ->setPosition($associated_product_position)
                                ->getExtensionAttributes()
                                ->setQty(1);
                            $associated_array[] = $product_link_interface;
                            $associated_product_position++;
                            $product->setProductLinks($associated_array);
                            $product->save();
                        }
                        if ($product->getId()) {
                            echo "Configurable Product Created Successfully";
                        }
                    } catch (Exception $e) {
                        return "We can't able to create Configurable Product now." . $e->getMessage();
                    }
                }
            }
        }
    }

    public function createSimpleProduct($item)
    {
        $status = $item['status'];
        if ($status=='active') {
            $status = "1";
        } else {
            $status = "0";
        }
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $product = $objectManager->create('\Magento\Catalog\Model\Product');
        $product->setSku($item['sku']);
        $product->setName($item['name']);
        $product->setAttributeSetId(4); 
        $product->setStatus($status); 
        $product->setWeight(10);
        $product->setVisibility(4);
        $product->setTaxClassId(0);
        $product->setTypeId('simple');
        $product->setPrice($item['rate']);
        $product->setStockData(
            array(
                'use_config_manage_stock' => 0,
                'manage_stock' => 1,
                'is_in_stock' => 1,
                'qty' => $item['initial_stock']
            )
        );
        $product->save();
    }

    public function isProductAttributeExists($field)
    {
        $attr = $this->_eavConfig->getAttribute("catalog_product",$field);
        if (!is_null($attr->getId())) {
            return true;
        } else {
            return false;
        }
    }

    public function cretaeNewAttribute()
    {
    }

    public function SalesOrderApi()
    {
        return "Sales Order Api";
    }

    public function getCustomerApi()
    {
        return self::CUSTOMER_API . '?organization_id=' .
        $this->scopeConfig->getValue('zohointegration/department/organization_id');
    }

    public function getCustomerApiById($ID)
    {
        return self::CUSTOMER_API . '/' . $ID . '?organization_id=' .
        $this->scopeConfig->getValue('zohointegration/department/organization_id');
    }

    public function getProductApi()
    {
        return self::ITEM_API . '?organization_id=' .
        $this->scopeConfig->getValue('zohointegration/department/organization_id');
    }

    public function getCompositeProductApi()
    {
        return self::COMPOSITETEM_API . '?organization_id=' .
        $this->scopeConfig->getValue('zohointegration/department/organization_id');
    }

    public function getCompositeProductApiById($compositeID)
    {
        return self::COMPOSITETEM_API . '/' . $compositeID . '?organization_id=' .
        $this->scopeConfig->getValue('zohointegration/department/organization_id');
    }

    public function getConfigurableProductApi()
    {
        return self::ITEMGRP_API . '?organization_id=' .
        $this->scopeConfig->getValue('zohointegration/department/organization_id');
    }

    public function getConfigurableProductApiById($configurableID)
    {
        return self::ITEMGRP_API . '/' . $configurableID . '?organization_id=' .
        $this->scopeConfig->getValue('zohointegration/department/organization_id');
    }

    public function getStoreCode()
    {
        return $this->_storeManager->getStore()->getName();
    }

    public function getWebsiteName($websiteId)
    {
        $collection = $this->_websiteModel->load($websiteId, 'website_id');
        return $collection->getName();
    }

    public function getHeaders()
    {
        $refress = $this->scopeConfig->getValue('zohointegration/department/refress_token');
        $client = $this->scopeConfig->getValue('zohointegration/department/client_id');
        $cs = $this->scopeConfig->getValue('zohointegration/department/client_secret');
        $redirect = $this->scopeConfig->getValue('zohointegration/department/redirect_uri');
        $url = self::TOKEN . $refress . "&client_id=" .
            $client . "&client_secret=" . $cs . "&redirect_uri=" . $redirect . "&grant_type=refresh_token";
        $this->_curl->post($url, " ");
        $response = $this->_curl->getBody();
        $response = json_decode($response);
        $foodArray = (array) $response;
        $access_token = $foodArray['access_token'];
        return ["Authorization" => "Zoho-oauthtoken " . $access_token,
            "Content-Type" => "application/json",
            "Cache-Control" => "no-cache",
        ];
    }
}
