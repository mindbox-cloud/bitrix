<?php


namespace Mindbox\DTO\V3\Responses;

use Mindbox\DTO\V3\ProductListItemDTO;

/**
 * Class ProductListItemResponseDTO
 *
 * @package Mindbox\DTO\V3\Responses
 * @property ProductResponseDTO $product
 **/
class ProductListItemResponseDTO extends ProductListItemDTO
{
    /**
     * @var array Мэппинг преобразрования полей в объекты DTO.
     */
    protected static $DTOMap = [
        'product' => ProductResponseDTO::class,
    ];

    /**
     * @return ProductResponseDTO
     */
    public function getProduct()
    {
        return $this->getField('product');
    }
}
