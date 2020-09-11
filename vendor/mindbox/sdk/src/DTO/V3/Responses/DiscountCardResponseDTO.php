<?php


namespace Mindbox\DTO\V3\Responses;

use Mindbox\DTO\DTO;
use Mindbox\DTO\V3\IdentityDTO;

/**
 * Class DiscountCardResponseDTO
 *
 * @package Mindbox\DTO\V3\Responses
 * @property DiscountCardTypeResponseDTO   $type
 * @property array                         $ids
 * @property DiscountCardStatusResponseDTO $status
 **/
class DiscountCardResponseDTO extends DTO
{
    use IdentityDTO;

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'discountCard';

    /**
     * @var array Мэппинг преобразрования полей в объекты DTO.
     */
    protected static $DTOMap = [
        'type'   => DiscountCardTypeResponseDTO::class,
        'status' => DiscountCardStatusResponseDTO::class,
    ];

    /**
     * @return DiscountCardTypeResponseDTO
     */
    public function getType()
    {
        return $this->getField('type');
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->getField('status');
    }
}
