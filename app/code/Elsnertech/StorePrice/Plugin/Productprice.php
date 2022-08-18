<?php

namespace Elsnertech\StorePrice\Plugin;

class Productprice
{
    public function afterGetPrice(\Magento\Catalog\Model\Product $subject, $result) {
        $result += 150;
        return $result;
    }
}