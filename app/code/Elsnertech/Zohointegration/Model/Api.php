<?php
namespace Elsnertech\Zohointegration\Model;

/**
 * Class Api
 *
 * @package Elsnertech\Zohointegration\Model
 */
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Api extends \Magento\Framework\Model\AbstractModel
{
    protected $_curl;
    private $scopeConfig;
    protected $_helper;
    protected $_productloader;
    protected $orderFactory;

    public function __construct(
        \Elsnertech\Zohointegration\Helper\Data $helper,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Catalog\Model\ProductFactory $_productloader,
        \Magento\Framework\HTTP\Client\Curl $curl,
        ScopeConfigInterface $scopeConfig,
        \Elsnertech\Zohointegration\Logger\Logger $customLogger,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Customer\Model\CustomerFactory $_customerloader
    ) {
        $this->_helper = $helper;
        $this->_productloader = $_productloader;
        $this->_orderFactory = $orderFactory;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->_customerloader = $_customerloader;
        $this->_customLogger = $customLogger;
        $this->_curl = $curl;
        $this->scopeConfig = $scopeConfig;
    }

    public function zohoId($email, $rid)
    {
        $CustomerModel = $this->_customerloader->create();
        $CustomerModel->setWebsiteId(1);
        $CustomerModel->loadByEmail($email);
        $CustomerModel->setzoho_id($rid);
        $data = $rid;
        $customerData = $CustomerModel->getDataModel();
        $customerData->setCustomAttribute('zoho_id', $data);
        $customerData->setCustomattribute('zoho_customer_id', $data);
        $CustomerModel->updateData($customerData);
        $CustomerModel->save();
    }
        
    public function ProductDelivery($shipment_id)
    {
        $deliver = $this->_helper->getdeliveryApi().$shipment_id.
            "/status/delivered?organization_id=".$this->_helper->getorg();
        $this->_curl->setHeaders($this->_helper->getHeaders());
        $this->_curl->post($deliver, " ");
        $response = $this->_curl->getBody();
        $response = json_decode($response, true);
    }

    public function Payment($orderid, $invoiceid, $amount)
    {
        $order = $this->_orderFactory->create();
        $order = $order->load($orderid);
        $paid = $order->getBaseTotalDue();
        $payment = $order->getPayment();
        $method = $payment->getMethodInstance();
        $customerid = $order->getCustomerId();
        $customer = $this->_customerloader->create();
        $customer->load($customerid);
        $zohoid = $customer->getzoho_id();
        $a = $order->getInvoiceCollection()->count();
        $b = $order->getShipmentsCollection()->count();
        $in =
        [
         "invoice_id"=> $invoiceid,
         "amount_applied"=> $amount
        ];
        $date = date("Y-m-d");
        $payment = [
            "customer_id" => $zohoid,
            "payment_mode" => $method->getTitle(),
            "amount"=> $amount,
            "date" => $date,
            "reference_number"=> $order->getincrement_id(),
            "invoices"=>[$in]
        ];
        $url = $this->_helper->getPaymentApi();
        $this->_curl->setHeaders($this->_helper->getHeaders());
        $this->_curl->post($url, json_encode($payment));
        $response = $this->_curl->getBody();
        $response = json_decode($response, true);
    }

    function makeApiRequest($gatewayUrl, $requestArr, $method)
    {
        $headers = [
        'Authorization: Zoho-oauthtoken '.$this->scopeConfig->getValue('zohointegration/departmes/access_token'),
        'Content-Type: application/json',
        'Cache-Control: no-cache'
        ];
        $requestString = $requestArr;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $gatewayUrl);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestString);
        $data = curl_exec($ch);
        curl_close($ch);
    }

    function deleteApi($gatewayUrl)
    {
        $headers = [
            'Authorization: Zoho-oauthtoken '.$this->scopeConfig->getValue('zohointegration/departmes/access_token'),
            'Content-Type: application/json',
            'Cache-Control: no-cache'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $gatewayUrl);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        $data = curl_exec($ch);
        curl_close($ch);
        unset($ch);
    }

    public function simpleProduct($product) {
        $p_id = $product->getid();
        $ProductModel = $this->_productloader->create();
        $ProductModel->load($p_id);
        $p_qty = $ProductModel['quantity_and_stock_status']['qty'];
        $type_id = $product->gettype_id();
        $p_name = $product->getname();
        $p_sku = $product->getsku();
        $description = strip_tags(str_replace('&nbsp;', ' ', $product->getDescription()));
        $p_price = $product->getprice();
        if ($type_id=='virtual') {
            $product_type = "service";
        } else {
            $product_type = "goods";
        }
            $data = [    
                "unit"=> $this->scopeConfig->getValue('general/locale/weight_unit', ScopeInterface::SCOPE_STORE),
                "item_type"=> "inventory",
                "product_type"=>  $product_type,
                "description"=> $description,
                "name"=> $p_name,
                "rate"=>$p_price,
                "purchase_rate"=> $p_price,
                "initial_stock"=> 0,
                "initial_stock_rate"=> 0,
                "sku"=> $p_sku
                ];
            $this->_curl->setHeaders($this->_helper->getHeaders());
            $this->_curl->post($this->_helper->getItemApi(), json_encode($data));
            $response = $this->_curl->getBody();
            $response = json_decode($response, true);
            if (isset($response['item'])) {
                $value = "Editmode";
                $item = $response['item']['item_id'];
                $product = $this->_productloader->create();
                $prod = $product->load($p_id);
                $prod-> setCustomattribute('zoho_data', $item);
                $prod->setCustomattribute('edit', $value);
                $this->_customLogger->info($prod->getName().'Product create message');
                $prod->save();
            }
    }
        
    public function itemGroup($product)
    {
        $type_id = $product->gettype_id();
        if ($type_id=='configurable') {
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
                    "unit" => $this->scopeConfig->getValue('general/locale/weight_unit', ScopeInterface::SCOPE_STORE),
                    "description" => strip_tags(str_replace('&nbsp;', ' ', $product->getDescription())),
                    "attribute_name1" =>$value[0],
                    "attribute_name2" =>$value[1],
                    "attribute_name3" =>$value[2],
                    "items" =>$a
                ];
                $this->_curl->setHeaders($this->_helper->getHeaders());
                $this->_curl->post($this->_helper->getItemGrpApi(), json_encode($data));
                $response = $this->_curl->getBody();
                $response = json_decode($response, true);
                if (isset($response['item_group'])) {
                    $itemseach = $response["item_group"]['items'];
                    $item = $response['item_group']['group_id'];
                    $product = $this->_productloader->create();
                    $prod = $product->load($p_id);
                    $prod->setCustomattribute('zohoproatt', $item);
                    $prod->save();
                    $bunkids = $bulkid;
                    $i = 0 ;
                    foreach ($itemseach as $a) {
                        $id = $bunkids[$i];
                        $itemid = $itemseach[$i]['item_id'];
                        $itemname = $itemseach[$i]['name'];
                        $itemsku = $itemseach[$i]['sku'];
                        $product = $this->_productloader->create();
                        $prod = $product->load($product->getIdBySku($itemsku));
                        $prod->setCustomattribute('zoho_data', $itemid);
                        $prod->setCustomattribute('zohoproatt', $item);
                        $prod->save();
                        $i++;
                    }
                }
        }
    }
    public function virtualProduct($product)
    {
             $p_id = $product->getid();
             $ProductModel = $this->_productloader->create();
             $ProductModel->load($p_id);
             $p_qty = $ProductModel['quantity_and_stock_status']['qty'];
             $type_id = $product->gettype_id();
             $p_name = $product->getname();
             $p_sku = $product->getsku();
             $description = strip_tags(str_replace('&nbsp;', ' ', $product->getDescription()));
             $p_price = $product->getprice();
             $product_type = "service";
                 $data = [
                        "unit"=> $this->scopeConfig->getValue(
                            'general/locale/weight_unit',
                            ScopeInterface::SCOPE_STORE
                        ),
                         "product_type"=>  $product_type,
                         "description"=> $description,
                         "name"=> $p_name,
                         "rate"=>$p_price,
                         "purchase_rate"=> $p_price,
                         "initial_stock"=> $p_qty,
                         "initial_stock_rate"=> $p_qty,
                         "sku"=> $p_sku
                   ];
                 $this->_curl->setHeaders($this->_helper->getHeaders());
                 $this->_curl->post($this->_helper->getItemApi(), json_encode($data));
                 $response = $this->_curl->getBody();
                 $response = json_decode($response, true);
                 if (isset($response['item'])) {
                     $value = 12345678;
                     $item = $response['item']['item_id'];
                     $product = $this->_productloader->create();
                     $prod = $product->load($p_id);
                     $prod->setCustomattribute('zoho_data', $item);
                     $prod->setCustomattribute('edit', '12345');
                     $prod->save();
                 }
    }
    public function compositeProduct($product)
    {
        $p_id = $product->getid();
        $product = $this->_productloader->create();
        $product = $product->load($p_id);
        $name =  $product->getname();
        $stoke = $product['initial_stock'];
        $rate = $product['rate'];
        $description = strip_tags(str_replace('&nbsp;', ' ', $product->getDescription()));
        $sku = $product->getsku();
        $children = $product->getTypeInstance()->getAssociatedProducts($product);
        foreach ($children as $child) {
            $product = $this->_productloader->create();
            $data = $product->load($child->getid());
            $p_qty = $data['quantity_and_stock_status']['qty'];
               $group[] =  [
                "quantity"=> $p_qty,
                "item_id"=> $data->getzoho_data()
                ];
        }
        $composite =  [
            "name"=> $name,
            "mapped_items"=> $group,
            "description"=> $description,
            "is_combo_product"=> true,
            "purchase_rate"=> $rate,
            "initial_stock"=>$stoke,
            "sku"=>$sku,
            "unit"=> $this->scopeConfig->getValue('general/locale/weight_unit', ScopeInterface::SCOPE_STORE),
            "item_type"=> "inventory",
            "rate"=>$rate
        ];
        $url = $this->_helper->getcompositeapi();
        $this->_curl->setHeaders($this->_helper->getHeaders());
        $this->_curl->post($url, json_encode($composite));
        $response = $this->_curl->getBody();
        $response = json_decode($response, true);
        if (isset($response['composite_item'])) {
            $value = 12345678;
            $item = $response['composite_item']['composite_item_id'];
            $product = $this->_productloader->create();
            $prod = $product->load($p_id);
            $prod->setCustomattribute('zoho_data', $item);
            $prod->setCustomattribute('edit', '12345');
            $prod->save();
        }
    }

    public function bundleProduct($product)
    {
        $p_id = $product->getid();
        $product = $this->_productloader->create();
        $product = $product->load($p_id);
        $initialstock = $product['initial_stock'];
        $rate = $product['rate'];
        $name = $product->getname();
        $sku = $product->getsku();
        $id = $p_id;
        $collection = $product->getTypeInstance(true)->getSelectionsCollection($product->getTypeInstance(true)->getOptionsIds($product), $product);

        foreach ($collection as $item) {
            $data = $product->load($item->getid());
            $qty = $data->getQuantityAndStockStatus();
            $compo[] = [
                "quantity"=> $qty['qty'],
                "item_id"=> $data->getzohoData()
            ];
        }
        $composite =  [
            "name"=> $name,
            "mapped_items"=> $compo,
            "description"=> strip_tags(str_replace('&nbsp;', ' ', $product->getDescription())),
            "is_combo_product"=> true,
            "purchase_rate"=> $rate,
            "initial_stock"=>$initialstock,
            "sku"=>$sku,
            "unit"=> $this->scopeConfig->getValue('general/locale/weight_unit', ScopeInterface::SCOPE_STORE),
            "item_type"=> "inventory",
            "rate"=>$rate
        ];
        $this->_curl->setHeaders($this->_helper->getHeaders());
        $this->_curl->post($this->_helper->getcompositeapi(), json_encode($composite));
        $response = $this->_curl->getBody();
        $response = json_decode($response, true);
    }
}
