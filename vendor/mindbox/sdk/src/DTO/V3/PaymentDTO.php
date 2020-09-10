<?php


namespace Mindbox\DTO\V3;

use Mindbox\DTO\DTO;

/**
 * Class PaymentDTO
 *
 * @package Mindbox\DTO\V3
 * @property string $type
 * @property string $amount
 **/
abstract class PaymentDTO extends DTO
{
    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'payment';

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
}
