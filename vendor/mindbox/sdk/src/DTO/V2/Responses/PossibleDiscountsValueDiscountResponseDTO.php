<?php


namespace Mindbox\DTO\V2\Responses;

use Mindbox\DTO\DTO;

/**
 * Class PossibleDiscountsValueDiscountResponseDTO
 *
 * @package Mindbox\DTO\V2\Responses
 * @property string $amount
 * @property string $amountType
 */
class PossibleDiscountsValueDiscountResponseDTO extends DTO
{
    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'discount';

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
    public function getAmountType()
    {
        return $this->getField('amountType');
    }
}
