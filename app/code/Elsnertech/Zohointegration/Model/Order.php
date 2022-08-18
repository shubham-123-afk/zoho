<?php
namespace Elsnertech\Zohointegration\Model;

/**
 * Class Order
 *
 * @package Elsnertech\Zohointegration\Model
 */
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Order extends \Magento\Framework\Model\AbstractModel
{
    protected $_curl;
    protected $_helper;
    private $scopeConfig;
    protected $orderfactory;
    protected $_productloader;
    protected $_orderFactory;
    protected $_invoice;
    protected $_order;

    public function __construct(
        \Elsnertech\Zohointegration\Helper\Data $helper,
        \Magento\Catalog\Model\ProductFactory $_productloader,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Sales\Model\OrderFactory $orderfactory,
        \Magento\Sales\Model\Order\Invoice $invoice,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Elsnertech\Zohointegration\Model\Api $Api,
        ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Customer\Model\CustomerFactory $_customerloader
    ) {
        $this->_helper = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->_orderfactory = $orderfactory;
        $this->_productloader = $_productloader;
        $this->messageManager = $messageManager;
        $this->_orderFactory = $orderFactory;
        $this->_invoice = $invoice;
        $this->_customerloader = $_customerloader;
        $this->_curl = $curl;
        $this->_Api = $Api;
    }

    public function shipmentAfter($shipment)
    {
        $id = $shipment['order_id'];
        $order = $this->_orderfactory->create();
        $order = $order->load($id);
        $packetid = $order->getpacket_id();
        $invoiceCollection = $order->getInvoiceCollection();
        foreach ($invoiceCollection as $invoice) {
            $zid = $invoice->getid();
            $zohoinvoice = $this->_invoice->load($zid);
            $custom = $zohoinvoice->getzohoinvoice_id();
        }
        if (!empty($packetid)) {
            $invoice = $order->getInvoiceCollection()->count();
            $shipcheck = $order->getShipmentsCollection()->count();
            $url = $this->_helper->getshipmentorder();
            $order = $shipment->getOrder();
            $paid = $order->getBaseTotalDue();
            $salesorderid = $order->getsalesorder_id();
            if (isset($salesorderid)) {
                $url =  $url."&salesorder_id=".$order->getsalesorder_id()."&package_ids=".$order->getpacket_id();
                $date = date("Y-m-d");
                $ship = [
                "shipment_number"=>$shipment['increment_id'],
                "date"=> $date,
                "reference_number"=> " ",
                "delivery_method"=> $order['shipping_method'],
                "tracking_number"=> "TRK214124124",
                "shipping_charge"=> $order['base_shipping_amount'],
                "notes"=> "notes"
                ];
                $ship = json_encode($ship);
                $this->_curl->setHeaders($this->_helper->getHeaders());
                $this->_curl->post($url, $ship);
                $response = $this->_curl->getBody();
                $response = json_decode($response, true);
                if (isset($response['shipmentorder']['shipment_id'])) {
                    $shipment_id = $response['shipmentorder']['shipment_id'];
                    $amount = $response['shipmentorder']['total'];
                    $order->setshipment_id($shipment_id);
                    $order->save();
                    if ($paid==0) {
                        $this->_Api->Payment($id, $custom, $amount);
                    }
                    if ($shipcheck==1 && $invoice==1 && $paid==0) {
                        $this->_Api->ProductDelivery($shipment_id);
                    }
                }
            }
        }
    }
      
    public function invoiceAfter($invoice)
    {
        $id = $invoice->getorder_id();
        $order = $this->_orderFactory->create();
        $customer = $this->_customerloader->create();
        $order = $order->load($id);
        $paid = $order->getBaseTotalDue();
        $invoicecheck = $order->getInvoiceCollection()->count();
        $shipcheck = $order->getShipmentsCollection()->count();
        $payment = $order->getPayment();
        $shipcharge = $order->getshipping_amount();
        $method = $payment->getMethodInstance();
        $customerid = $order->getCustomerId();
        $customer = $customer->load($customerid);
        $zohoid = $customer->getzoho_id();
        $i = 0 ;
        $soid = explode(" ", $order['so_line_item_id']);
        foreach ($order->getAllItems() as $item) {
            $idd =  $item->getProductId();
            $sku = $item->getsku();
            $product = $this->_productloader->create();
            $product = $product->load($idd);
            $zohodata = $product->getzoho_data();
            if ($zohodata!=12345) {
                $items[] = [
                  "item_id"=> $zohodata,
                  "name"=> $item->getname(),
                  "bcy_rate"=>" ",
                  "rate"=> $item->getprice(),
                  "quantity"=>(int)$item->getQtyOrdered(),
                  "unit"=> $this->scopeConfig->getValue('general/locale/weight_unit', ScopeInterface::SCOPE_STORE),
                  "discount_amount" => $item->getdiscount_amount(),
                  "item_total" =>(int)$order->gettotal_qty_ordered(),
                  "salesorder_item_id"=>$soid[$i]
                ];
                $i++;
            }
        }
            $data = [
            "customer_id"=> $zohoid,
            "line_items"=>$items,
            "shipping_charge"=> $shipcharge,
            ];
            $this->_curl->setHeaders($this->_helper->getHeaders());
            $this->_curl->post($this->_helper->getinvoiceapi(), json_encode($data));
            $response = $this->_curl->getBody();
            $response = json_decode($response, true);
            print_r($response);die;
            if (isset($response['invoice']['invoice_id'])) {
                $invoiceid = $response['invoice']['invoice_id'];
                $amount = $response['invoice']['total'];
                $invoice->setzohoinvoice_id($invoiceid);
                $invoice->save();
                $this->_Api->Payment($id, $invoiceid, $amount);
                if ($shipcheck==1 && $invoicecheck==1) {
                    $this->_Api->Payment($id, $invoiceid, $amount);
                    $shipment_id = $order->getshipment_id();
                    $this->_Api->ProductDelivery($shipment_id);
                }
            }
    }
          
    public function creditSave($creditMemo)
    {
        $customerid = $creditMemo->getcustomer_id();
        $orderid = $creditMemo->getorder_id();
        $order = $this->_orderFactory->create();
        $customer = $this->_customerloader->create();
        $customer = $customer->load($customerid);
        $product = $this->_productloader->create();
        $order = $order->load($orderid);
        $date = date("d-m-Y");
        $zohocustomerid = $customer->getzoho_id();
        foreach ($order->getAllItems() as $item) {
            $id = $item->getproduct_id();
            $product = $this->_productloader->create();
            $product = $product->load($id);
            $data[] = [
              "item_id" => $product->getzoho_data(),
              "description" => $product->getdescription(),
              "name" => $product->getname(),
              "quantity" => 1
            ];
        }
        $credit =
        [
          "customer_id"=>$customer->getzoho_id(),
          "date"=> date("Y-m-d"),
          "line_items" =>$data
        ];
        $url = "https://inventory.zoho.com/api/v1/creditnotes?organization_id=".$this->_helper->getorg();
        $this->_curl->setHeaders($this->_helper->getHeaders());
        $this->_curl->post($url, json_encode($credit));
        $response = $this->_curl->getBody();
        $response = json_decode($response, true);
    }
}
