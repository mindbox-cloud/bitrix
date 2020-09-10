<?php


namespace Mindbox\DTO\V2\Responses;

use Mindbox\DTO\DTOCollection;

/**
 * Class DiscountInfoResponseCollection
 *
 * @package Mindbox\DTO\V2\Responses
 */
class DiscountInfoResponseCollection extends DTOCollection
{
    /**
     * @var string Название элементов коллекции для корректной генерации xml.
     */
    protected static $collectionItemsName = DiscountInfoResponseDTO::class;

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'discountsInfo';
}
