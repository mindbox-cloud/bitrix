<?php


namespace Mindbox\DTO\V3\Requests;

use Mindbox\DTO\DTO;

/**
 * Class AddProductToListRequestDTO
 *
 * @package Mindbox\DTO\V3\Requests
 * @property ProductRequestDTO $product
 **/
class AddProductToListRequestDTO extends DTO
{
    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'addProductToList';

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
     * @param array|ProductRequestDTO $product
     */
    public function setProduct($product)
    {
        $this->setField('product', $product);
    }
}
