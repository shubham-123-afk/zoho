<?php
namespace Elsnertech\Zohointegration\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
 
        $installer->startSetup();

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'salesorder_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'comment' =>'salesorder_id'
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'so_line_item_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 315,
                'comment' =>'so_line_item_id'
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'invoice_id',
            [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'comment' => 'data',
                    'nullable' => false,
                    'default' => false,
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'packet_id',
            [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'comment' => 'packet_id',
                    'nullable' => false,
                    'default' => false,
                ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'shipment_id',
            [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'comment' => 'shipment_id',
                    'nullable' => false,
                    'default' => false,
                ]
        );

        $installer->endSetup();
    }
}
