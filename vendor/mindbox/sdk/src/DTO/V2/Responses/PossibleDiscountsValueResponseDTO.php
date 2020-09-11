<?php


namespace Mindbox\DTO\V2\Responses;

use Mindbox\DTO\DTO;

/**
 * Class PossibleDiscountsValueResponseDTO
 *
 * @package Mindbox\DTO\V2\Responses
 * @property PossibleDiscountsValueDiscountResponseDTO    $discount
 * @property string                                       $itemsCount
 * @property PossibleDiscountsValueItemResponseCollection $items
 */
class PossibleDiscountsValueResponseDTO extends DTO
{
    /**
     * @var array Мэппинг преобразрования полей в объекты DTO.
     */
    protected static $DTOMap = [
        'discount' => PossibleDiscountsValueDiscountResponseDTO::class,
        'items'    => PossibleDiscountsValueItemResponseCollection::class,
    ];

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'value';

    /**
     * @return PossibleDiscountsValueDiscountResponseDTO
     */
    public function getDiscount()
    {
        return $this->getField('discount');
    }

    /**
     * @return string
     */
    public function getItemsCount()
    {
        return $this->getField('itemsCount');
    }

    /**
     * @return PossibleDiscountsValueItemResponseCollection
     */
    public function getItems()
    {
        return $this->getField('items');
    }
}
