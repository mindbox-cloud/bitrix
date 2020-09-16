<?php


namespace Mindbox\DTO\V2\Requests;

use Mindbox\DTO\V2\ProductDTO;

/**
 * Class ProductRequestDTO
 *
 * @package Mindbox\DTO\V2\Requests
 **/
class ProductRequestDTO extends ProductIdentityRequestDTO
{
    use ProductDTO;

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
