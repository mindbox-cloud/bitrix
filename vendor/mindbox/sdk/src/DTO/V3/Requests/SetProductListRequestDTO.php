<?php


namespace Mindbox\DTO\V3\Requests;

use Mindbox\DTO\DTO;

/**
 * Class SetProductListRequestDTO
 *
 * @package Mindbox\DTO\V3\Requests
 * @property ProductListItemRequestCollection $products
 **/
class SetProductListRequestDTO extends DTO
{
    /**
     * @var array Мэппинг преобразрования полей в объекты DTO.
     */
    protected static $DTOMap = [
        'products' => ProductListItemRequestCollection::class,
    ];

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'productList';

    /**
     * @return ProductListItemRequestCollection
     */
    public function getProducts()
    {
        return $this->getField('products');
    }

    /**
     * @param array|ProductListItemRequestCollection $products
     */
    public function setProducts($products)
    {
        $this->setField('products', $products);
    }
}
