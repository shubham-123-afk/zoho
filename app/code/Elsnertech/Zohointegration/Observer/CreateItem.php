<?php
namespace Elsnertech\Zohointegration\Observer;

/**
 * Class CreateItem
 *
 * @package Elsnertech\Zohointegration\Observer
 */
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Event\ObserverInterface;

class CreateItem implements ObserverInterface
{
    protected $_productCollectionFactory;
    protected $_helper;
    private $scopeConfig;
    protected $_productloader;
    protected $_Api;

    public function __construct(
        \Elsnertech\Zohointegration\Helper\Data $helperData,
        ScopeConfigInterface $scopeConfig,
        \Elsnertech\Zohointegration\Model\Api $Api,
        \Elsnertech\Zohointegration\Model\Configrable $Configrable,
        \Magento\Catalog\Model\ProductFactory $_productloader,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        array $data = []
    ) {
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_helper = $helperData;
        $this->_configrable = $Configrable;
        $this->_productloader = $_productloader;
        $this->scopeConfig = $scopeConfig;
        $this->_Api = $Api;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        $zohoprice = $product->getprice();
        $edit = $product->getedit();
        if ($edit=="12345") {
            $type_id = $product->gettype_id();
            if ($type_id=="simple") {
                $this->_Api->SimpleProduct($product);
            } elseif ($type_id=="configurable") {
                $this->_Api->ItemGroup($product);
            } elseif ($type_id=="virtual") {
                $this->_Api->Virtualproduct($product);
            } elseif ($type_id=="downloadable") {
                $this->_Api->Virtualproduct($product);
            } elseif ($type_id=="grouped") {
                $this->_Api->Compositeproduct($product);
            } elseif ($type_id=="bundle") {
                $this->_Api->BundleProduct($product);
            }
        } else {
            $item = $this->_helper->getItemeditApi();
            $itemGroup = $this->_helper->getItemgrpeditApi();
            $zohodata = $product->getzoho_data();
            $itemGroupid = $product->getzohoproatt();
            $org = $this->_helper->getorg();
            $weightUnit = $this->scopeConfig->getValue(
                'general/locale/weight_unit',
                ScopeInterface::SCOPE_STORE
            );
            $id = $product->getId();
            $ProductModel = $this->_productloader->create();
            $ProductModel->load($id);
            $producttype = $ProductModel->gettype_id();
            if ($producttype=="simple") {
                $gatewayUrl = $item.$zohodata."?organization_id=".$org;
                $data = [
                        "unit"=> $weightUnit,
                        "item_type"=> "inventory",
                        "description"=>strip_tags(str_replace('&nbsp;', ' ', $product->getDescription())),
                        "name"=> $product->getname(),
                        "rate"=>$zohoprice,
                        "purchase_rate"=> $zohoprice,
                        "initial_stock"=> $ProductModel['quantity_and_stock_status']['qty'],
                        "initial_stock_rate"=> $ProductModel['quantity_and_stock_status']['qty'],
                        "sku"=> $product->getsku(),
                        "status"=> $product->getstatus(),
                    ];

                $data = json_encode($data);
                $this->_Api->makeApiRequest($gatewayUrl, $data, "PUT");
            } elseif ($producttype=="virtual") {
                $gatewayUrl = $item.$zohodata."?organization_id=".$org;
                $data = [
                       "unit"=> " ",
                       "description"=>strip_tags(str_replace('&nbsp;', ' ', $product->getDescription())),
                       "name"=> $product->getname(),
                       "rate"=>$zohoprice,
                       "purchase_rate"=> $product->getprice(),
                       "initial_stock"=> $ProductModel['quantity_and_stock_status']['qty'],
                       "initial_stock_rate"=> $ProductModel['quantity_and_stock_status']['qty'],
                       "sku"=> $product->getsku(),
                       "status"=> $product->getstatus(),
                   ];
                $data = json_encode($data);
                $this->_Api->makeApiRequest($gatewayUrl, $data, "PUT");

            } elseif ($producttype=="grouped") {
                $gatewayUrl = $this->_helper->getcompositeeditapi().'/'.$zohodata."?organization_id=".$org;
                $p_id = $product->getid();
                $product  = $this->_productloader->create();
                $product = $product->load($p_id);
                $name =  $product->getname();
                $stoke = $product['initial_stock'];
                $rate = $product->getrate();
                $description = strip_tags(str_replace('&nbsp;', ' ', $product->getDescription()));
                $sku = $product->getsku();
                $children = $product->getTypeInstance()->getAssociatedProducts($product);
                foreach ($children as $child) {
                    $product  = $this->_productloader->create();
                    $id = $child->getid();
                    $data = $product->load($id);
                     $p_qty = $data['quantity_and_stock_status']['qty'];
                       $group[] =  [
                        "name"=>$child->getname(),
                        "quantity"=> $p_qty,
                        "item_id"=> $data->getzoho_data(),
                        "rate"=>$child->getPrice(),
                        "sku"=>$child->getsku()
                        ];
                }
                $composite =  [
                    "name"=> $name,
                    "mapped_items"=> $group,
                    "description"=> $description,
                    "is_combo_product"=> true,
                    "initial_stock"=> $stoke,
                    "sku"=>$sku,
                    "unit"=> $this->scopeConfig->getValue('general/locale/weight_unit', ScopeInterface::SCOPE_STORE),
                    "item_type"=> "inventory",
                    "rate"=>$rate
                ];
                $data = json_encode($composite);
                $this->_Api->makeApiRequest($gatewayUrl, $data, "PUT");
            } elseif ($producttype=="configurable") {
                $gatewayUrl = $itemGroup.$itemGroupid."?organization_id=".$org;
                $p_id = $product->getId();
                $ProductModel = $this->_productloader->create();
                $product = $ProductModel->load($p_id);
                $name = $product->getname();
                $_children = $product->getTypeInstance()->getUsedProducts($product);
                $productAttributeOptions = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
                $attributeOptions = [];
                foreach ($productAttributeOptions as $productAttribute) {
                    $value[] = $productAttribute['label'];
                    $atcode[] = $productAttribute['attribute_code'];
                    foreach ($productAttribute['values'] as $attribute) {
                        $value[$productAttribute['attribute_code']][] = $attribute['label'];
                        $label[] = $attribute['label'];
                    }
                }
                $i = 0;
                foreach ($value as $v) {
                    if ($i<count($atcode)) {
                        $a1[] = $value[$atcode[$i]];
                        $i++;
                    }
                }
                        $j =0;
                $x =0;
                $y =0;
                $z =0;
                        $s1 = $a1[0];
                        $cs1 = count($s1);
                foreach ($s1 as $c) {
                    if (isset($a1[1])) {
                        $s2 = $a1[1];
                        foreach ($s2 as $t) {
                            if (isset($a1[2])) {
                                $s3 = $a1[2];
                                foreach ($s3 as $f) {
                                    $abc1[] = $c;
                                    $abc2[] = $t;
                                    $abc3[] = $f;
                                }
                            } else {
                                $abc1[] = $c;
                                $abc2[] = $t;
                            }
                        }
                    } else {
                        $abc1[] = $c;
                        $abc2[] = " ";
                        $abc3[$z] = " ";
                    }
                }
                foreach ($_children as $child) {
                        $id = $child->getid();
                        $productdata = $this->_productloader->create();
                        $productdata = $product->load($id);

                    if (!isset($abc2[$y])) {
                        $abc2[$y]=" ";
                    }
                    if (!isset($abc3[$z])) {
                        $abc3[$z]=" ";
                    }
                        $bulkid[] = $child->getsku();
                        $a[] = [
                                "name"=>$child->getName(),
                                "rate"=>$child->getprice(),
                                "purchase_rate"=> $child->getprice(),
                                "sku"=> $child->getSku(),
                                "initial_stock"=> $productdata['quantity_and_stock_status']['qty'],
                                "initial_stock_rate"=>$child->getprice(),
                                "attribute_option_name1"=>$abc1[$x],
                                "attribute_option_name2"=>$abc2[$y],
                                "attribute_option_name3"=>$abc3[$z]

                        ];
                        $x++;
                        $y++;
                        $j++;
                        $z++;
                }
                if (!isset($value[1])) {
                    $value[1] = " ";
                }
                if (!isset($value[2])) {
                    $value[2] = " ";
                }
                 $a1 = 0;

                    $data = [
                        "group_name"=>$name,
                        "unit" => $this->scopeConfig->getValue(
                            'general/locale/weight_unit',
                            ScopeInterface::SCOPE_STORE
                        ),
                        "description" => strip_tags(str_replace('&nbsp;', ' ', $product->getDescription())),
                        "attribute_name1" =>$value[0],
                        "attribute_name2" =>$value[1],
                        "attribute_name3" =>$value[2],
                        "items" =>$a
                    ];
                    $data = json_encode($data);
                    $this->_Api->makeApiRequest($gatewayUrl, $data, "PUT");

            }
        }
    }
}
