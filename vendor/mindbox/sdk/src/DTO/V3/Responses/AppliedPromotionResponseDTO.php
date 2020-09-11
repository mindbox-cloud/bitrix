<?php


namespace Mindbox\DTO\V3\Responses;

use Mindbox\DTO\DTO;

/**
 * Class AppliedPromotionResponseDTO
 *
 * @package Mindbox\DTO\V3\Responses
 * @property string                  $type
 * @property CouponResponseDTO       $coupon
 * @property PromotionResponseDTO    $promotion
 * @property BalanceTypeResponseDTO  $balanceType
 * @property string                  $amount
 * @property string                  $expirationDateTimeUtc
 * @property IssuedCouponResponseDTO $issuedCoupon
 **/
class AppliedPromotionResponseDTO extends DTO
{
    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'appliedPromotion';

    /**
     * @var array Мэппинг преобразрования полей в объекты DTO.
     */
    protected static $DTOMap = [
        'coupon'       => CouponResponseDTO::class,
        'promotion'    => PromotionResponseDTO::class,
        'balanceType'  => BalanceTypeResponseDTO::class,
        'issuedCoupon' => IssuedCouponResponseDTO::class,
    ];

    /**
     * @return string
     */
    public function getType()
    {
        return $this->getField('type');
    }

    /**
     * @return CouponResponseDTO
     */
    public function getCoupon()
    {
        return $this->getField('coupon');
    }

    /**
     * @return PromotionResponseDTO
     */
    public function getPromotion()
    {
        return $this->getField('promotion');
    }

    /**
     * @return BalanceTypeResponseDTO
     */
    public function getBalanceType()
    {
        return $this->getField('balanceType');
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
    public function getExpirationDateTimeUtc()
    {
        return $this->getField('expirationDateTimeUtc');
    }

    /**
     * @return IssuedCouponResponseDTO
     */
    public function getIssuedCoupon()
    {
        return $this->getField('issuedCoupon');
    }
}
