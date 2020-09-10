<?php


namespace Mindbox\DTO\V2\Responses;

/**
 * Class ContentItemPossibleDiscountsResponseDTO
 *
 * @package Mindbox\DTO\V2\Responses
 * @property PossibleDiscountsValueResponseDTO $value
 **/
class ContentItemPossibleDiscountsResponseDTO extends ContentItemResponseDTO
{
    /**
     * @var array Мэппинг преобразрования полей в объекты DTO.
     */
    protected static $DTOMap = [
        'value' => PossibleDiscountsValueResponseDTO::class,
    ];

    /**
     * @return PossibleDiscountsValueResponseDTO
     */
    public function getValue()
    {
        return $this->getField('value');
    }
}
