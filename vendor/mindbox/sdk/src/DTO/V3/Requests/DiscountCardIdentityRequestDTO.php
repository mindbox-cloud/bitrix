<?php


namespace Mindbox\DTO\V3\Requests;

use Mindbox\DTO\DTO;

/**
 * Class DiscountCardIdentityRequestDTO
 *
 * @package Mindbox\DTO\V3\Requests
 * @property array $ids
 **/
class DiscountCardIdentityRequestDTO extends DTO
{
    use IdentityRequestDTO;

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'discountCard';
}
