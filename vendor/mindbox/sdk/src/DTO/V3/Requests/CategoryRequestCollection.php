<?php


namespace Mindbox\DTO\V3\Requests;

use Mindbox\DTO\DTOCollection;

/**
 * Class CategoryRequestCollection
 *
 * @package Mindbox\DTO\V3\Requests
 */
class CategoryRequestCollection extends DTOCollection
{
    /**
     * @var string Название элементов коллекции для корректной генерации xml.
     */
    protected static $collectionItemsName = CategoryRequestDTO::class;

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'categories';
}
