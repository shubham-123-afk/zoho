<?php
namespace Elsnertech\Zohointegration\Setup;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Quote\Setup\QuoteSetupFactory;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Sales\Setup\SalesSetupFactory;
use Magento\Eav\Model\Entity\Attribute\Set as AttributeSet;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;

class UpgradeData implements UpgradeDataInterface
{
    private $eavSetupFactory;
    protected $quoteSetupFactory;
    private $salesSetupFactory;
    protected $customerSetupFactory;
    private $attributeSetFactory;
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        QuoteSetupFactory $quoteSetupFactory,
        SalesSetupFactory $salesSetupFactory,
        CustomerSetupFactory $customerSetupFactory,
        AttributeSetFactory $attributeSetFactory
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->quoteSetupFactory = $quoteSetupFactory;
        $this->salesSetupFactory = $salesSetupFactory;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
    }
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.4.0') < 0) {

            $eavSetup= $this->eavSetupFactory->create(['setup' => $setup]);
            $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);
            $customerEntity = $customerSetup->getEavConfig()->getEntityType('customer');
            $attributeSetId = $customerEntity->getDefaultAttributeSetId();
            /**
             * @var $attributeSet AttributeSet
            */
            $attributeSet = $this->attributeSetFactory->create();
            $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);
            $eavSetup->removeAttribute(\Magento\Customer\Model\Customer::ENTITY, 'zoho_id');
            $customerSetup->addAttribute(
                \Magento\Customer\Model\Customer::ENTITY,
                'zoho_id',
                [
                            'type' => 'varchar',
                            'label' => 'Zoho Customer',
                            'input' => 'text',
                            'required' => false,
                            'visible' => true,
                            'default' => '456',
                            'user_defined' => true,
                            'sort_order' => 1000,
                            'position' => 1000,
                            'system' => 0,
                        ]
            );
                $attribute = $customerSetup->getEavConfig()
                    ->getAttribute(\Magento\Customer\Model\Customer::ENTITY, 'zoho_id')
                    ->addData(
                        [
                        'attribute_set_id' => $attributeSetId,
                        'attribute_group_id' => $attributeGroupId,
                        'used_in_forms' => ['adminhtml_customer'],
                        ]
                    );

            $attribute->save();
                   
        }

        if (version_compare($context->getVersion(), '2.3.0') < 0) {

            $eavSetup= $this->eavSetupFactory->create(['setup' => $setup]);
            $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'rate');
            $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'initial_stock');

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

        if (version_compare($context->getVersion(), '2.4.0') < 0) {

            $eavSetup= $this->eavSetupFactory->create(['setup' => $setup]);
            $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'edit');

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
        }
    }
}
