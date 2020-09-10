<?php


namespace Mindbox\DTO\V2\Responses;

use Mindbox\DTO\DTO;

/**
 * Class AppliedDiscountResponseDTO
 *
 * @package Mindbox\DTO\V2\Responses
 * @property string                 $type
 * @property string                 $id
 * @property string                 $amount
 * @property PromoActionResponseDTO $promoAction
 * @property BalanceTypeResponseDTO $balanceType
 */
class AppliedDiscountResponseDTO extends DTO
{
    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'appliedDiscount';

    /**
     * @var array Мэппинг преобразрования полей в объекты DTO.
     */
    protected static $DTOMap = [
        'promoAction' => PromoActionResponseDTO::class,
        'balanceType' => BalanceTypeResponseDTO::class,
    ];

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
    public function getId()
    {
        return $this->getField('id');
    }

    /**
     * @return string
     */
    public function getAmount()
    {
        return $this->getField('amount');
    }

    /**
     * @return PromoActionResponseDTO
     */
    public function getPromoAction()
    {
        return $this->getField('promoAction');
    }

    /**
     * @return BalanceTypeResponseDTO
     */
    public function getBalanceType()
    {
        return $this->getField('balanceType');
    }
}
