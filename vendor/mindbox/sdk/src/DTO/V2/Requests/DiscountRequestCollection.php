<?php


namespace Mindbox\DTO\V2\Requests;

use Mindbox\DTO\DTOCollection;

/**
 * Class DiscountRequestCollection
 *
 * @package Mindbox\DTO\V2\Requests
 */
class DiscountRequestCollection extends DTOCollection
{
    /**
     * @var string Название элементов коллекции для корректной генерации xml.
     */
    protected static $collectionItemsName = DiscountRequestDTO::class;

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'discounts';
}
