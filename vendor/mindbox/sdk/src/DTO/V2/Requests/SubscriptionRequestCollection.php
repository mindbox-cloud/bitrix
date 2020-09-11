<?php


namespace Mindbox\DTO\V2\Requests;

use Mindbox\DTO\DTOCollection;

/**
 * Class SubscriptionRequestCollection
 *
 * @package Mindbox\DTO\V2\Requests
 */
class SubscriptionRequestCollection extends DTOCollection
{
    /**
     * @var string Название элементов коллекции для корректной генерации xml.
     */
    protected static $collectionItemsName = SubscriptionRequestDTO::class;

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'subscriptions';
}
