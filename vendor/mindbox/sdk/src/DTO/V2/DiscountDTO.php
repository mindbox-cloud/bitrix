<?php


namespace Mindbox\DTO\V2;

use Mindbox\DTO\DTO;

/**
 * Class DiscountDTO
 *
 * @package Mindbox\DTO\V2
 * @property string $type
 * @property string $amount
 * @property string $id
 **/
abstract class DiscountDTO extends DTO
{
    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'discount';

    /**
     * @return string
     */
    public function getType()
    {
        return $this->getField('type');
    }

    /**
     * @return string
     */
    public function getAmount()
    {
        return $this->getField('amount');
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->getField('id');
    }
}
