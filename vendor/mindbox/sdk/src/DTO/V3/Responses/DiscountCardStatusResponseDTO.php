<?php


namespace Mindbox\DTO\V3\Responses;

use Mindbox\DTO\DTO;
use Mindbox\DTO\V3\IdentityDTO;

/**
 * Class DiscountCardStatusResponseDTO
 *
 * @package Mindbox\DTO\V3\Responses
 * @property array  $ids
 * @property string $dateTimeUtc
 **/
class DiscountCardStatusResponseDTO extends DTO
{
    use IdentityDTO;

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'status';

    /**
     * @return string
     */
    public function getDateTimeUtc()
    {
        return $this->getField('dateTimeUtc');
    }
}
