<?php


namespace Mindbox\DTO\V3\Requests;

use Mindbox\DTO\V3\ProductListItemDTO;

/**
 * Class ProductListItemRequestDTO
 *
 * @package Mindbox\DTO\V3\Requests
 * @property ProductRequestDTO $product
 **/
class ProductListItemRequestDTO extends ProductListItemDTO
{
    /**
     * @var array Мэппинг преобразрования полей в объекты DTO.
     */
    protected static $DTOMap = [
        'product' => ProductRequestDTO::class,
    ];

    /**
     * @return ProductRequestDTO
     */
    public function getProduct()
    {
        return $this->getField('product');
    }

    /**
     * @param ProductRequestDTO|array $product
     */
    public function setProduct($product)
    {
        $this->setField('product', $product);
    }

    /**
     * @param mixed $count
     */
    public function setCount($count)
    {
        $this->setField('count', $count);
    }

    /**
     * @param mixed $price
     */
    public function setPrice($price)
    {
        $this->setField('price', $price);
    }
}
