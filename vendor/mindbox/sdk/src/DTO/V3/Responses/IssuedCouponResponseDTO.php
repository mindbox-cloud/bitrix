<?php


namespace Mindbox\DTO\V3\Responses;

/**
 * Class IssuedCouponResponseDTO
 *
 * @package Mindbox\DTO\V3\Responses
 **/
class IssuedCouponResponseDTO extends CouponResponseDTO
{
    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'issuedCoupon';
}
