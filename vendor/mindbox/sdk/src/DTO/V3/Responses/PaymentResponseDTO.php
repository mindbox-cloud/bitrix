<?php


namespace Mindbox\DTO\V3\Responses;

use Mindbox\DTO\V3\PaymentDTO;

/**
 * Class PaymentResponseDTO
 *
 * @package Mindbox\DTO\V3\Responses
 * @property string                 $id
 * @property string                 $promoActionId
 * @property CreditCardResponseDTO  $creditCard
 * @property BalanceTypeResponseDTO $balanceType
 **/
class PaymentResponseDTO extends PaymentDTO
{
    /**
     * @var array Мэппинг преобразрования полей в объекты DTO.
     */
    protected static $DTOMap = [
        'creditCard'  => CreditCardResponseDTO::class,
        'balanceType' => BalanceTypeResponseDTO::class,
    ];

    /**
     * @return string
     */
    public function getId()
    {
        return $this->getField('id');
    }

    /**
     * @return string
     */
    public function getPromoActionId()
    {
        return $this->getField('promoActionId');
    }

    /**
     * @return CreditCardResponseDTO
     */
    public function getCreditCard()
    {
        return $this->getField('creditCard');
    }

    /**
     * @return BalanceTypeResponseDTO
     */
    public function getBalanceType()
    {
        return $this->getField('balanceType');
    }
}
