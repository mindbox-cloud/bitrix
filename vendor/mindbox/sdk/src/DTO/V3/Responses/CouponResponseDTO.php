<?php


namespace Mindbox\DTO\V3\Responses;

use Mindbox\DTO\DTO;
use Mindbox\DTO\V3\IdentityDTO;

;

/**
 * Class CouponResponseDTO
 *
 * @package Mindbox\DTO\V3\Responses
 * @property array                 $ids
 * @property CouponPoolResponseDTO $pool
 **/
class CouponResponseDTO extends DTO
{
    use IdentityDTO;

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'coupon';

    /**
     * @var array Мэппинг преобразрования полей в объекты DTO.
     */
    protected static $DTOMap = [
        'pool' => CouponPoolResponseDTO::class,
    ];

    /**
     * @return CouponPoolResponseDTO
     */
    public function getPool()
    {
        return $this->getField('pool');
    }
}
