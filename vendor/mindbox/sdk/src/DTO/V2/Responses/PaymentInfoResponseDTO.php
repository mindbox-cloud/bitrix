<?php


namespace Mindbox\DTO\V2\Responses;

use Mindbox\DTO\DTO;

/**
 * Class PaymentInfoResponseDTO
 *
 * @package Mindbox\DTO\V2\Responses
 * @property string              $type
 * @property string              $availableAmountForCurrentOrder
 * @property GiftCardResponseDTO $giftCard
 **/
class PaymentInfoResponseDTO extends DTO
{
    /**
     * @var array Мэппинг преобразрования полей в объекты DTO.
     */
    protected static $DTOMap = [
        'giftCard' => GiftCardResponseDTO::class,
    ];

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'paymentInfo';

    /**
     * @return string
     */
    public function getType()
    {
        return $this->getField('type');
    }

    /**
     * @return string
     */
    public function getAvailableAmountForCurrentOrder()
    {
        return $this->getField('availableAmountForCurrentOrder');
    }

    /**
     * @return GiftCardResponseDTO
     */
    public function getGiftCard()
    {
        return $this->getField('giftCard');
    }
}
