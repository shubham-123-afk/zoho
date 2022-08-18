<?php
/**
 * Created By : Rohan Hapani
 */
namespace Elsnertech\Zohointegration\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResponseInterface;

class ProductCsv extends \Magento\Framework\Model\AbstractModel
{

    protected $fileFactory;
    protected $productFactory;


    public function __construct(
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Catalog\Model\ProductFactory $_productloader,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurableProductType
    ) {
        $this->_fileFactory = $fileFactory;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->_productloader = $_productloader;
        $this->configurableProductType = $configurableProductType;
    }

    public function ImpportProduct()
    {

        $productCollection = $this->_productloader->create()->getCollection();
    
        foreach ($productCollection as $product) {
            if ($product->getTypeId()=="simple"){
                $product_type[] = $product->getid();
            }   
        }

        $name = date('m_d_Y_H_i_s');
        $filepath = 'export/custom' . $name . '.csv';
        $this->directory->create('export');
        /* Open file */
        $stream = $this->directory->openFile($filepath, 'w+');
        $stream->lock();
        $columns = $this->getColumnHeader();
        foreach ($columns as $column) {
            $header[] = $column;
        }

        $stream->writeCsv($header);

        $product = $this->_productloader->create();
        foreach ($product_type as $item) {      
            $cog_product = $this->configurableProductType->getParentIdsByChild($item);
            if (!$cog_product) {
                $product->load($item);
                $itemData = [];
                $itemData[] = "kg";
                $itemData[] = "inventory";
                $itemData[] = "goods";
                $itemData[] = strip_tags(str_replace('&nbsp;', ' ', $product->getDescription()));
                $itemData[] = $product->getName();
                $itemData[] = $product->getPrice();
                $itemData[] = $product->getPrice();
                $itemData[] = 0;
                $itemData[] = 0;
                $itemData[] = $product->getSku();
                $stream->writeCsv($itemData);
            }
        }

        $content = [];
        $content['type'] = 'filename'; // must keep filename
        $content['value'] = $filepath;
        $content['rm'] = '1'; //remove csv from var folder

        $csvfilename = 'Product.csv';
        $this->_fileFactory->create($csvfilename, $content, DirectoryList::VAR_DIR);

        
    }
    public function getColumnHeader() {
        $headers = ['unit','item_type','product_type','description','name','rate','purchase_rate','initial_stock','initial_stock_rate','sku'];
        return $headers;
    }
}