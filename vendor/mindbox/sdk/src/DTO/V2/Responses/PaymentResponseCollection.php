<?php


namespace Mindbox\DTO\V2\Responses;

use Mindbox\DTO\DTOCollection;

/**
 * Class PaymentResponseCollection
 *
 * @package Mindbox\DTO\V2\Responses
 */
class PaymentResponseCollection extends DTOCollection
{
    /**
     * @var string Название элементов коллекции для корректной генерации xml.
     */
    protected static $collectionItemsName = PaymentResponseDTO::class;

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'payments';
}
