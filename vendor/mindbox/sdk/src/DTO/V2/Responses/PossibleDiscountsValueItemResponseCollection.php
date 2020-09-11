<?php


namespace Mindbox\DTO\V2\Responses;

use Mindbox\DTO\DTOCollection;

/**
 * Class PossibleDiscountsValueItemResponseCollection
 *
 * @package Mindbox\DTO\V2\Responses
 */
class PossibleDiscountsValueItemResponseCollection extends DTOCollection
{
    /**
     * @var string Название элементов коллекции для корректной генерации xml.
     */
    protected static $collectionItemsName = PossibleDiscountsValueItemResponseDTO::class;

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'items';
}
