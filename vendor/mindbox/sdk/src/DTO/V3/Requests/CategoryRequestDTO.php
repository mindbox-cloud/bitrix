<?php


namespace Mindbox\DTO\V3\Requests;

use Mindbox\DTO\DTO;

/**
 * Class CategoryRequestDTO
 *
 * @package Mindbox\DTO\V3\Requests
 * @property array $ids
 */
class CategoryRequestDTO extends DTO
{
    use IdentityRequestDTO;

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'category';
}
