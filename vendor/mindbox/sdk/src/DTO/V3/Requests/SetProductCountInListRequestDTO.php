<?php


namespace Mindbox\DTO\V3\Requests;

use Mindbox\DTO\DTO;

/**
 * Class SetProductCountInListRequestDTO
 *
 * @package Mindbox\DTO\V3\Requests
 * @property ProductRequestDTO $product
 * @property string            $count
 **/
class SetProductCountInListRequestDTO extends DTO
{
    /**
     * @var array Мэппинг преобразрования полей в объекты DTO.
     */
    protected static $DTOMap = [
        'product' => ProductRequestDTO::class,
    ];

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'setProductCountInList';

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

    /**
     * @return string
     */
    public function getCount()
    {
        return $this->getField('count');
    }

    /**
     * @param mixed $count
     */
    public function setCount($count)
    {
        $this->setField('count', $count);
    }
}
