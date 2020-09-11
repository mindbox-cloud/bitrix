<?php


namespace Mindbox\DTO\V2\Responses;

/**
 * Class DiscountInfoPromoCodeResponseDTO
 *
 * @package Mindbox\DTO\V2\Responses
 * @property PromoCodeResponseDTO $promoCode
 **/
class DiscountInfoPromoCodeResponseDTO extends DiscountInfoResponseDTO
{
    /**
     * @var array Мэппинг преобразрования полей в объекты DTO.
     */
    protected static $DTOMap = [
        'promoCode' => PromoCodeResponseDTO::class,
    ];

    /**
     * @return PromoCodeResponseDTO
     */
    public function getPromoCode()
    {
        return $this->getField('promoCode');
    }
}
