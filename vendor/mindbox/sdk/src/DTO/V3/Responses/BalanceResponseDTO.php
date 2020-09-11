<?php


namespace Mindbox\DTO\V3\Responses;

use Mindbox\DTO\DTO;

/**
 * Class BalanceResponseDTO
 *
 * @package Mindbox\DTO\V3\Responses
 * @property string $totalValue
 * @property string $availableValue
 * @property string $blockedValue
 **/
class BalanceResponseDTO extends DTO
{
    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'balance';

    /**
     * @return string
     */
    public function getTotalValue()
    {
        return $this->getField('totalValue');
    }

    /**
     * @return string
     */
    public function getAvailableValue()
    {
        return $this->getField('availableValue');
    }

    /**
     * @return string
     */
    public function getBlockedValue()
    {
        return $this->getField('blockedValue');
    }
}
