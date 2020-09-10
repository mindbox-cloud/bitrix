<?php


namespace Mindbox\DTO\V2\Responses;

use Mindbox\DTO\DTOCollection;

/**
 * Class LineResponseCollection
 *
 * @package Mindbox\DTO\V2\Responses
 */
class LineResponseCollection extends DTOCollection
{
    /**
     * @var string Название элементов коллекции для корректной генерации xml.
     */
    protected static $collectionItemsName = LineResponseDTO::class;

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'lines';
}
