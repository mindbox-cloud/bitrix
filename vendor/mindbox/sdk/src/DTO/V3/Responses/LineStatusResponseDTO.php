<?php


namespace Mindbox\DTO\V3\Responses;

use Mindbox\DTO\DTO;
use Mindbox\DTO\V3\IdentityDTO;

/**
 * Class LineResponseDTO
 *
 * @package Mindbox\DTO\V3\Responses
 * @property array $ids
 **/
class LineStatusResponseDTO extends DTO
{
    use IdentityDTO;

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'status';
}
