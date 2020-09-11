<?php


namespace Mindbox\DTO\V3\Requests;

use Mindbox\DTO\DTOCollection;

/**
 * Class CustomerIdentityRequestCollection
 *
 * @package Mindbox\DTO\V3\Requests
 */
class CustomerIdentityRequestCollection extends DTOCollection
{
    /**
     * @var string Название элементов коллекции для корректной генерации xml.
     */
    protected static $collectionItemsName = CustomerIdentityRequestDTO::class;

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'customers';
}
