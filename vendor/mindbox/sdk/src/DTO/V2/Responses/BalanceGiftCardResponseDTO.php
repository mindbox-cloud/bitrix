<?php


namespace Mindbox\DTO\V2\Responses;

use Mindbox\DTO\DTO;

/**
 * Class BalanceGiftCardResponseDTO
 *
 * @package Mindbox\DTO\V2\Responses
 * @property string $total
 * @property string $available
 * @property string $used
 **/
class BalanceGiftCardResponseDTO extends DTO
{
    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'balance';

    /**
     * @return string
     */
    public function getTotal()
    {
        return $this->getField('total');
    }

    /**
     * @return string
     */
    public function getAvailable()
    {
        return $this->getField('available');
    }

    /**
     * @return string
     */
    public function getUsed()
    {
        return $this->getField('used');
    }
}
