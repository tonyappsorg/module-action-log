<?php
namespace Tonyapps\ActionLog\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class WatchedEntity implements ArrayInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => \Magento\Catalog\Model\Product::class, 'label' => __('Product')],
            ['value' => \Magento\Catalog\Model\Category::class, 'label' => __('Category')],
            ['value' => \Magento\Cms\Model\Page::class, 'label' => __('CMS Page')],
            ['value' => \Magento\Cms\Model\Block::class, 'label' => __('CMS Block')],
            ['value' => \Magento\Customer\Model\Customer::class, 'label' => __('Customer')],
        ];
    }
}
