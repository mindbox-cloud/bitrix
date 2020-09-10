<?php


namespace Mindbox\DTO\V2\Responses;

use Mindbox\DTO\DTO;
use Mindbox\DTO\V2\IdentityDTO;

/**
 * Class DiscountCardResponseDTO
 *
 * @package Mindbox\DTO\V2\Responses
 * @property DiscountCardTypeResponseDTO $type
 * @property array                       $ids
 * @property string                      $status
 **/
class DiscountCardResponseDTO extends DTO
{
    use IdentityDTO;

    /**
     * @var array Мэппинг преобразрования полей в объекты DTO.
     */
    protected static $DTOMap = [
        'type' => DiscountCardTypeResponseDTO::class,
    ];

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'discountCard';

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
