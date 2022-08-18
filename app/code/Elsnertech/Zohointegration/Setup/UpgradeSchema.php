<?php
namespace Elsnertech\Zohointegration\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{

    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {

        $installer = $setup;

        $installer->startSetup();

        if (version_compare($context->getVersion(), "1.9.0", "<")) {
            $installer->getConnection()->addColumn(
                $installer->getTable('sales_invoice'),
                'zohoinvoice_id',
                [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'comment' => 'zohoinvoice_id',
                        'nullable' => false,
                        'default' => false,
                    ]
            );
        }

        if (version_compare($context->getVersion(), "2.0.0", "<")) {
            $installer->getConnection()->addColumn(
                $installer->getTable('sales_invoice'),
                'payment_id',
                [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'comment' => 'payment_id',
                        'nullable' => false,
                        'default' => false,
                    ]
            );
        }

        if (version_compare($context->getVersion(), "2.1.0", "<")) {
            $installer->getConnection()->addColumn(
                $installer->getTable('sales_order'),
                'zohopayment_id',
                [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'comment' => 'zohopayment_id',
                        'nullable' => false,
                        'default' => false,
                    ]
            );
        }
        $installer->endSetup();
    }
}
