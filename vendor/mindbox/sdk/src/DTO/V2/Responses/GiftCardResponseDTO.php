<?php


namespace Mindbox\DTO\V2\Responses;

use Mindbox\DTO\V2\GiftCardDTO;

/**
 * Class GiftCardResponseDTO
 *
 * @package Mindbox\DTO\V2\Responses
 * @property string                     $status
 * @property BalanceGiftCardResponseDTO $balance
 **/
class GiftCardResponseDTO extends GiftCardDTO
{
    /**
     * @var array Мэппинг преобразрования полей в объекты DTO.
     */
    protected static $DTOMap = [
        'balance' => BalanceGiftCardResponseDTO::class,
    ];

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->getField('status');
    }

    /**
     * @return BalanceGiftCardResponseDTO
     */
    public function getBalance()
    {
        return $this->getField('balance');
    }
}
