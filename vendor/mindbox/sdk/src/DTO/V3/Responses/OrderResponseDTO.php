<?php


namespace Mindbox\DTO\V3\Responses;

use Mindbox\DTO\DTO;
use Mindbox\DTO\V3\IdentityDTO;

/**
 * Class OrderResponseDTO
 *
 * @package Mindbox\DTO\V3\Responses
 * @property array                              $ids
 * @property string                             $isCurrentState
 * @property LineResponseCollection             $lines
 * @property AppliedPromotionResponseCollection $appliedPromotions
 * @property PaymentResponseCollection          $payments
 * @property string                             $totalPrice
 **/
class OrderResponseDTO extends DTO
{
    use IdentityDTO;

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'order';

    /**
     * @var array Мэппинг преобразрования полей в объекты DTO.
     */
    protected static $DTOMap = [
        'lines'             => LineResponseCollection::class,
        'appliedPromotions' => AppliedPromotionResponseCollection::class,
        'payments'          => PaymentResponseCollection::class,
    ];

    /**
     * @return string
     */
    public function getIsCurrentState()
    {
        return $this->getField('isCurrentState');
    }

    /**
     * @return LineResponseCollection
     */
    public function getLines()
    {
        return $this->getField('lines');
    }

    /**
     * @return AppliedPromotionResponseCollection
     */
    public function getAppliedPromotions()
    {
        return $this->getField('appliedPromotions');
    }

    /**
     * @return PaymentResponseCollection
     */
    public function getPayments()
    {
        return $this->getField('payments');
    }

    /**
     * @return string
     */
    public function getTotalPrice()
    {
        return $this->getField('totalPrice');
    }
}
