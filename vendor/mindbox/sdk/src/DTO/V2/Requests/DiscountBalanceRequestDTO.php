<?php


namespace Mindbox\DTO\V2\Requests;

/**
 * Class DiscountBalanceRequestDTO
 *
 * @package Mindbox\DTO\V2\Requests
 * @property BalanceTypeRequestDTO $balanceType
 **/
class DiscountBalanceRequestDTO extends DiscountRequestDTO
{
    /**
     * @var array Мэппинг преобразрования полей в объекты DTO.
     */
    protected static $DTOMap = [
        'balanceType' => BalanceTypeRequestDTO::class,
    ];

    /**
     * @return BalanceTypeRequestDTO
     */
    public function getBalanceType()
    {
        return $this->getField('balanceType');
    }

    /**
     * @param array|BalanceTypeRequestDTO $balanceType
     */
    public function setBalanceType($balanceType)
    {
        $this->setField('balanceType', $balanceType);
    }
}
