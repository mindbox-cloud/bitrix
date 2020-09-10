<?php


namespace Mindbox\DTO\V2;

use Mindbox\DTO\DTO;

/**
 * Class CustomerIdentityDTO
 *
 * @package Mindbox\DTO\V2
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
