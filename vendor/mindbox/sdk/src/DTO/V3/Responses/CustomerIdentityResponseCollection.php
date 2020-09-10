<?php


namespace Mindbox\DTO\V3\Responses;

use Mindbox\DTO\DTOCollection;

/**
 * Class CustomerIdentityResponseCollection
 *
 * @package Mindbox\DTO\V3\Responses
 */
class CustomerIdentityResponseCollection extends DTOCollection
{
    /**
     * @var string Название элементов коллекции для корректной генерации xml.
     */
    protected static $collectionItemsName = CustomerIdentityResponseDTO::class;

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'customers';
}
