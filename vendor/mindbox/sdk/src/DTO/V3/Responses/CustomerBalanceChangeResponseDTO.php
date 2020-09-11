<?php


namespace Mindbox\DTO\V3\Responses;

use Mindbox\DTO\DTO;

/**
 * Class CustomerBalanceChangeResponseDTO
 *
 * @package Mindbox\DTO\V3\Responses
 * @property string                       $changeAmount
 * @property string                       $expirationDateTimeUtc
 * @property string                       $isAvailable
 * @property BalanceChangeKindResponseDTO $balanceChangeKind
 **/
class CustomerBalanceChangeResponseDTO extends DTO
{
    /**
     * @var array Мэппинг преобразрования полей в объекты DTO.
     */
    protected static $DTOMap = [
        'balanceChangeKind' => BalanceChangeKindResponseDTO::class,
    ];

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'customerBalanceChange';

    /**
     * @return string
     */
    public function getChangeAmount()
    {
        return $this->getField('changeAmount');
    }

    /**
     * @return string
     */
    public function getExpirationDateTimeUtc()
    {
        return $this->getField('expirationDateTimeUtc');
    }

    /**
     * @return string
     */
    public function getIsAvailable()
    {
        return $this->getField('isAvailable');
    }

    /**
     * @return BalanceChangeKindResponseDTO
     */
    public function getBalanceChangeKind()
    {
        return $this->getField('balanceChangeKind');
    }
}
