<?php
namespace Elsnertech\Zohointegration\Model;

/**
 * Class Api
 *
 * @package Elsnertech\Zohointegration\Model
 */
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Configrable extends \Magento\Framework\Model\AbstractModel
{
    protected $_curl;
    private $scopeConfig;
    protected $_helper;
    protected $_Api;
    protected $_productloader;
    protected $orderFactory;

    public function __construct(
        \Elsnertech\Zohointegration\Helper\Data $helper,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Elsnertech\Zohointegration\Model\Api $Api,
        \Magento\Catalog\Model\ProductFactory $_productloader,
        \Magento\Framework\HTTP\Client\Curl $curl,
        ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Customer\Model\CustomerFactory $_customerloader
    ) {
        $this->_helper = $helper;
        $this->_Api = $Api;
        $this->_productloader = $_productloader;
        $this->_orderFactory = $orderFactory;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->_customerloader = $_customerloader;
        $this->_curl = $curl;
        $this->scopeConfig = $scopeConfig;
    }

    public function itemGroup($product)
    {
        $item = $this->_helper->getItemeditApi();
        $itemGroup = $this->_helper->getItemgrpeditApi();
        $zohodata = $product->getzoho_data();
        $itemGroupid = $product->getzohoproatt();
        $org = $this->_helper->getorg();
        $weightUnit = $this->scopeConfig->getValue(
            'general/locale/weight_unit',
            ScopeInterface::SCOPE_STORE
        );
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
