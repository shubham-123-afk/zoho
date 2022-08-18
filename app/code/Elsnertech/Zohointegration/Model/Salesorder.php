<?php
namespace Elsnertech\Zohointegration\Model;

/**
 * Class Salesorder
 *
 * @package Elsnertech\Zohointegration\Model
 */

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Salesorder extends \Magento\Framework\Model\AbstractModel
{
    protected $_curl;
    protected $_helper;
    private $scopeConfig;
    protected $orderfactory;
    protected $_productloader;
    protected $_orderFactory;
    protected $_order;

    public function __construct(
        \Elsnertech\Zohointegration\Helper\Data $helper,
        \Magento\Catalog\Model\ProductFactory $_productloader,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Elsnertech\Zohointegration\Model\Api $Api,
        \Elsnertech\Zohointegration\Model\Order $Order,
        ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Customer\Model\CustomerFactory $_customerloader
    ) {
        $this->_helper = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->_productloader = $_productloader;
        $this->messageManager = $messageManager;
        $this->_orderFactory = $orderFactory;
        $this->_customerloader = $_customerloader;
        $this->_curl = $curl;
        $this->_Api = $Api;
        $this->_Order = $Order;
    }

    public function createOrder($order)
    {
        $id = $order->getid();
        $email = '';
        if (isset($order['customer_email'])) {
            $email = $order['customer_email'];
        }
        $customer = $this->_customerloader->create();
        $customer->setWebsiteId(1);
        $customer = $customer->loadByEmail($email);
        $zoho_id = $customer->getzoho_id();
        $product = $this->_productloader->create();
        $i = 0 ;
        foreach ($order->getAllItems() as $item) {
            $id =  $item->getProductId();
            $totalitemrate = $item->getbase_row_total();
            $orderqty = $item->getqty_ordered();
            $product = $product->load($id);
            $name = $product->getname();
            $zohodata = $product->getzoho_data();
            if ($zohodata!=12345) {
                $itemsdata[] = [
                "item_id"=> $product->getzoho_data(),
                "name"=>$name,
                "description"=>strip_tags(str_replace('&nbsp;', ' ', $product->getDescription())),
                "rate"=> $item->getprice(),
                "quantity"=>$orderqty,
                "item_total" => $order['base_subtotal']
                ];
            }
        }
        $originalPrice = $order['base_subtotal'];
        $finalPrice = $order['base_grand_total'];
        $dis = $order->getbase_discount_amount();
        $percentage = 0;
        if ($originalPrice > $finalPrice) {
            $percentage = ($originalPrice - $finalPrice) * 100 / $originalPrice;
            $percentage = $percentage."%";
        }
        $date = date("Y-m-d");
        $salesorder = [
         "customer_id"=>$zoho_id,
         "date" => $date,
         "reference_number"=> $order->getincrement_id(),
         "line_items"=>$itemsdata,
         "notes"=> $order['shipping_description'],
         "terms"=>"Terms and Conditions",
         "discount"=>$dis,
         "is_discount_before_tax"=> true,
         "discount_type"=>"entity_level",
         "shipping_charge"=> $order['shipping_amount'],
         "delivery_method"=> $order['shipping_method']
        ];
        $this->_curl->setHeaders($this->_helper->getHeaders());
        $this->_curl->post($this->_helper->getsalesorderapi(), json_encode($salesorder));
        $response = $this->_curl->getBody();
        $response = json_decode($response, true);
        if (isset($response['salesorder'])) {
            $lineitem = $response['salesorder']['line_items'];
            foreach ($lineitem as $value) {
                 $soid[] = $value['line_item_id'];
            }
            $salesorderid = $response['salesorder']['salesorder_id'];
            $order = $order->seteditorder('12345');
            $order = $order->setsalesorder_id($salesorderid);
            $soid = implode(" ", $soid);
            $soid = $soid;
            $order = $order->setso_line_item_id($soid);
            $order->save();
            $url = $this->_helper->getsalesordereditapi().$salesorderid.
            "/status/confirmed?organization_id=".$this->_helper->getorg();
            $this->_curl->setHeaders($this->_helper->getHeaders());
            $this->_curl->post($url, "aa");
            $response = $this->_curl->getBody();
            $abc = $order->getso_line_item_id();
            $abc = explode(" ", $abc);
            $i = 0;
            foreach ($abc as $a) {
                $pitem[] = [
                    "so_line_item_id"=>$abc[$i],
                ];
                $i++;
            }
            $packet =
                [
                "package_number"=> $salesorderid,
                "date"=> $date ,
                "line_items"=>$pitem,
                "notes"=> "notes"
                ];
                $url =  $this->_helper->getPacketApi()."&salesorder_id=".$salesorderid;
                $this->_curl->post($url, json_encode($packet));
                $response = $this->_curl->getBody();
                $response = json_decode($response, true);
            if (isset($response['package']['package_id'])) {
                    $packetid = $response['package']['package_id'];
                    $order = $order->setpacket_id($packetid);
                    $order->save();
            }
            foreach ($order->getInvoiceCollection() as $invoice) {
                    $this->_Order->invoiceAfter($invoice);
            }
                $this->messageManager->addSuccess(__("The salesorder has been created"));
        }
    }
}
