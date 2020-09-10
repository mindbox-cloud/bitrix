<?php


namespace Mindbox\DTO\V3;

use Mindbox\DTO\DTO;

/**
 * Class ProductListItemDTO
 *
 * @package Mindbox\DTO\V3
 * @property string $count
 * @property string $price
 **/
abstract class ProductListItemDTO extends DTO
{
    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'productListItem';

    /**
     * @return string
     */
    public function getCount()
    {
        return $this->getField('count');
    }

    /**
     * @return string
     */
    public function getPrice()
    {
        return $this->getField('price');
    }
}
