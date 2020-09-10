<?php


namespace Mindbox\DTO\V2;

use Mindbox\DTO\DTO;

/**
 * Class BalanceTypeDTO
 *
 * @package Mindbox\DTO\V2
 * @property array $ids
 */
abstract class BalanceTypeDTO extends DTO
{
    use IdentityDTO;

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'balanceType';
}
