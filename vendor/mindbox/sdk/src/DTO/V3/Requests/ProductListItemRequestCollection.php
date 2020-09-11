<?php


namespace Mindbox\DTO\V3\Requests;

use Mindbox\DTO\DTOCollection;

/**
 * Class ProductListRequestCollection
 *
 * @package Mindbox\DTO\V3\Requests
 */
class ProductListItemRequestCollection extends DTOCollection
{
    /**
     * @var string Название элементов коллекции для корректной генерации xml.
     */
    protected static $collectionItemsName = ProductListItemRequestDTO::class;

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'productList';
}
