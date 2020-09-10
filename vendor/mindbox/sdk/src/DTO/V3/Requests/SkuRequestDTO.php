<?php


namespace Mindbox\DTO\V3\Requests;

use Mindbox\DTO\V3\SkuDTO;

/**
 * Class SkuRequestDTO
 *
 * @package Mindbox\DTO\V3\Requests
 **/
class SkuRequestDTO extends SkuIdentityRequestDTO
{
    use SkuDTO;

    /**
     * @param mixed $productId
     */
    public function setProductId($productId)
    {
        $this->setField('productId', $productId);
    }

    /**
     * @param mixed $basePricePerItem
     */
    public function setBasePricePerItem($basePricePerItem)
    {
        $this->setField('basePricePerItem', $basePricePerItem);
    }

    /**
     * @param mixed $skuId
     */
    public function setSkuId($skuId)
    {
        $this->setField('skuId', $skuId);
    }
}
