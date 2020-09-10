<?php


namespace Mindbox\DTO\V2\Responses;

/**
 * Class DiscountInfoBalanceResponseDTO
 *
 * @package Mindbox\DTO\V2\Responses
 * @property BalanceDiscountResponseDTO $balance
 **/
class DiscountInfoBalanceResponseDTO extends DiscountInfoResponseDTO
{
    /**
     * @var array Мэппинг преобразрования полей в объекты DTO.
     */
    protected static $DTOMap = [
        'balance' => BalanceDiscountResponseDTO::class,
    ];

    /**
     * @return BalanceDiscountResponseDTO
     */
    public function getBalance()
    {
        return $this->getField('balance');
    }
}
