<?php


namespace Mindbox\DTO\V2\Responses;

use Mindbox\DTO\DTO;

/**
 * Class DiscountInfoResponseDTO
 *
 * @package Mindbox\DTO\V2\Responses
 * @property string $type
 * @property string $availableAmountForCurrentOrder
 **/
class DiscountInfoResponseDTO extends DTO
{
    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'discountInfo';

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
    public function getAvailableAmountForCurrentOrder()
    {
        return $this->getField('availableAmountForCurrentOrder');
    }
}
