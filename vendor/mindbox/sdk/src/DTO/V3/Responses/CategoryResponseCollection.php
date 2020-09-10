<?php


namespace Mindbox\DTO\V3\Responses;

use Mindbox\DTO\DTOCollection;

/**
 * Class CategoryResponseCollection
 *
 * @package Mindbox\DTO\V3\Responses
 */
class CategoryResponseCollection extends DTOCollection
{
    /**
     * @var string Название элементов коллекции для корректной генерации xml.
     */
    protected static $collectionItemsName = CategoryResponseDTO::class;

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'categories';
}
