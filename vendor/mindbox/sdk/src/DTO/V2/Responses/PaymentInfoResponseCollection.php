<?php


namespace Mindbox\DTO\V2\Responses;

use Mindbox\DTO\DTOCollection;

/**
 * Class PaymentInfoResponseCollection
 *
 * @package Mindbox\DTO\V2\Responses
 */
class PaymentInfoResponseCollection extends DTOCollection
{
    /**
     * @var string Название элементов коллекции для корректной генерации xml.
     */
    protected static $collectionItemsName = PaymentInfoResponseDTO::class;

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'paymentsInfo';
}
