<?php


namespace Mindbox\DTO\V3\Responses;

use Mindbox\DTO\DTOCollection;

/**
 * Class ProductListItemResponseCollection
 *
 * @package Mindbox\DTO\V3\Responses
 */
class ProductListItemResponseCollection extends DTOCollection
{
    /**
     * @var string Название элементов коллекции для корректной генерации xml.
     */
    protected static $collectionItemsName = ProductListItemResponseDTO::class;

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'productList';
}
