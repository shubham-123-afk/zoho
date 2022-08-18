<?php
namespace Elsnertech\Zohointegration\Observer;

/**
 * Class ProductMassDelete
 *
 * @package Elsnertech\Zohointegration\Observer
 */

use Magento\Framework\Event\ObserverInterface;

class ProductMassDelete implements ObserverInterface
{
    protected $_helper;
    protected $_productFactory;

    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Elsnertech\Zohointegration\Model\Api $Api,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\ProductFactory $_productloader,
        \Elsnertech\Zohointegration\Helper\Data $helperData
    ) {
        $this->_productloader = $_productloader;
        $this->_Api = $Api;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_request = $request;
        $this->_helper = $helperData;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $orid  = $this->_helper->getorg();
        $token = $this->_helper->gettoken();
        $id = $this->_request->getParams('id');
        $pro = $this->_productloader->create();
        $i = 0;
        if (isset($id['excluded'])) {
            $Id = $id['excluded'];
            $collection = $this->_productCollectionFactory->create()
                ->addAttributeToFilter('entity_id', ['nin' => $Id])->getdata();
            foreach ($collection as $key) {
                      $id = $key['entity_id'];
                      $product = $this->_productloader->create();
                      $product = $product->load($id);
                      $producttype = $product->gettype_id();
                if ($producttype =="simple") {
                       $zohoproductid = $product->getzoho_data();
                       $update = [
                        "name"=> $product->getname(),
                        "initial_stock"=> 0,
                        "initial_stock_rate"=> 0
                       ];
                       $update = json_encode($update);
                       $url =  "https://inventory.zoho.com/api/v1/items/".$product->getzoho_data().
                       "?organization_id=".$orid;
                       $this->_Api->makeApiRequest($url, $update, "PUT");
                       $gatewayUrl =  $this->_helper->getItemdeleteApi();
                       $gatewayUrl = $gatewayUrl.$zohoproductid."?organization_id=".$orid;
                       $this->_Api->deleteApi($gatewayUrl);
                
                } elseif ($producttype =="virtual") {
                    $zohoproductid = $product->getzoho_data();
                    $gatewayUrl =  $this->_helper->getItemdeleteApi();
                    $gatewayUrl = $gatewayUrl.$zohoproductid."?organization_id=".$orid;
                    $this->_Api->deleteApi($gatewayUrl);
                
                } elseif ($producttype =="downloadable") {
                    $zohoproductid = $product->getzoho_data();
                    $gatewayUrl =  $this->_helper->getItemdeleteApi();
                    $gatewayUrl = $gatewayUrl.$zohoproductid."?organization_id=".$orid;
                    $this->_Api->deleteApi($gatewayUrl);
                
                } elseif ($producttype =="configurable") {
                    $zohoproductid = $product->getzohoproatt();
                    $gatewayUrl =  $this->_helper->getItemgrpdeleteApi();
                    $gatewayUrl = $gatewayUrl.$zohoproductid."?organization_id=".$orid;
                    $this->_Api->deleteApi($gatewayUrl);
                }
            }
        } else {
            $id = $id['selected'];
            foreach ($id as $i) {
                $pro = $pro->load($i);
                $zoho = $pro->getzoho_data();
                if (isset($zoho)) {
                      $type = $pro->gettype_id();
                    if ($type=='simple') {
                        $zohoproductid = $product->getzoho_data();
                        $update = [
                        "name"=> $product->getname(),
                        "initial_stock"=> 0,
                        "initial_stock_rate"=> 0
                        ];
                        $update = json_encode($update);
                        $url =  "https://inventory.zoho.com/api/v1/items/".$product->getzoho_data().
                        "?organization_id=".$orid;
                        $this->_Api->makeApiRequest($url, $update, "PUT");
                        $gatewayUrl =  $this->_helper->getItemdeleteApi();
                        $gatewayUrl = $gatewayUrl.$zoho."?organization_id=".$orid;
                        $this->_Api->deleteApi($gatewayUrl);
                    } elseif ($type=='grouped') {
                        $gatewayUrl =  $this->_helper->getcompositeapi();
                        $gatewayUrl = $gatewayUrl.$zoho."?organization_id=".$orid;
                        $this->_Api->deleteApi($gatewayUrl);

                    } elseif ($type=='configurable') {
                        $gatewayUrl =  $this->_helper->getItemgrpdeleteApi();
                        $gatewayUrl = $gatewayUrl.$pro->getzohoproatt()."?organization_id=".$orid;
                        $this->_Api->deleteApi($gatewayUrl);
                    } elseif ($type=='virtual') {
                        $gatewayUrl =  $this->_helper->getItemdeleteApi();
                        $gatewayUrl = $gatewayUrl.$zoho."?organization_id=".$orid;
                        $this->_Api->deleteApi($gatewayUrl);
                    } elseif ($type=='downloadeble') {
                        $gatewayUrl =  $this->_helper->getItemdeleteApi();
                        $gatewayUrl = $gatewayUrl.$zoho."?organization_id=".$orid;
                        $this->_Api->deleteApi($gatewayUrl);
                    }
                }
            }
        }
    }
}
