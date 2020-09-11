<?php


namespace Mindbox\DTO\V2\Responses;

/**
 * Class DiscountInfoBalanceSpentResponseDTO
 *
 * @package Mindbox\DTO\V2\Responses
 * @property string $spentAmountForCurrentOrder
 **/
class DiscountInfoBalanceSpentResponseDTO extends DiscountInfoBalanceResponseDTO
{
    /**
     * @return string
     */
    public function getSpentAmountForCurrentOrder()
    {
        return $this->getField('spentAmountForCurrentOrder');
    }
}
