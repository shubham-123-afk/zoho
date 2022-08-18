<?php

namespace Elsnertech\Zohointegration\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
    private $eavSetupFactory;

    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'zoho_data',
            [
                'type' => 'text',
                'label' => 'Zoho Pid',
                'input' => 'text',
                'class' => '',
                'source' => '',
                'visible' => true,
                'required' => true,
                'default' => '12345'
            ]
        );
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'zohoproatt',
            [
                'type' => 'text',
                'label' => 'Groupe id',
                'input' => 'text',
                'class' => '',
                'source' => '',
                'visible' => true,
                'required' => true,
                'default' => '12345'
            ]
        );

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'edit',
            [
                'type' => 'text',
                'label' => 'edit',
                'input' => 'text',
                'class' => '',
                'source' => '',
                'visible' => false,
                'required' => true,
                'default' => '12345'
            ]
        );

        $eavSetup->addAttribute(
            \Magento\Customer\Model\Customer::ENTITY,
            'zoho_id',
            [
                    'type'         => 'varchar',
                    'label'        => 'zoho_id',
                    'input'        => 'text',
                    'required'     => false,
                    'visible'      => true,
                    'user_defined' => true,
                    'position'     => 999,
                ]
        );

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'rate',
            [
                    'group' => 'Product Details',
                    'type' => 'varchar',
                    'frontend' => '',
                    'label' => 'rate'  ,
                    'input' => 'text',
                    'visible' => 1,
                    'required' => true
                ]
        );

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'initial_stock',
            [
                    'group' => 'Product Details',
                    'type' => 'varchar',
                    'frontend' => '',
                    'label' => 'initial_stock',
                    'input' => 'text',
                    'visible' => 1,
                    'required' => true
                ]
        );
    }
}
