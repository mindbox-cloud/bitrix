<?php


namespace Mindbox\DTO\V3\Responses;

use Mindbox\DTO\DTO;
use Mindbox\DTO\V3\IdentityDTO;

/**
 * Class DiscountCardTypeResponseDTO
 *
 * @package Mindbox\DTO\V3\Responses
 * @property array  $ids
 * @property string $name
 **/
class DiscountCardTypeResponseDTO extends DTO
{
    use IdentityDTO;

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'type';

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getField('name');
    }
}
