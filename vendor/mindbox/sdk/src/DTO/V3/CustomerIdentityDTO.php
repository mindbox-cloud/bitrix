<?php


namespace Mindbox\DTO\V3;

use Mindbox\DTO\DTO;

/**
 * Class CustomerIdentityDTO
 *
 * @package Mindbox\DTO\V3
 * @property array $ids
 */
abstract class CustomerIdentityDTO extends DTO
{
    use IdentityDTO;

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'customer';
}
