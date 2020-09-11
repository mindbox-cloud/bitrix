<?php


namespace Mindbox\DTO\V3\Responses;

use Mindbox\DTO\DTO;

/**
 * Class BalanceChangeKindResponseDTO
 *
 * @package Mindbox\DTO\V3\Responses
 * @property string $systemName
 */
class BalanceChangeKindResponseDTO extends DTO
{
    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'balanceChangeKind';

    /**
     * @return string
     */
    public function getSystemName()
    {
        return $this->getField('systemName');
    }
}
