<?php


namespace Mindbox\DTO\V2\Responses;

use Mindbox\DTO\DTO;
use Mindbox\DTO\V2\IdentityDTO;

/**
 * Class PromoCodeTypeResponseDTO
 *
 * @package Mindbox\DTO\V2\Responses
 * @property array  $ids
 * @property string $name
 * @property string $description
 **/
class PromoCodeTypeResponseDTO extends DTO
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

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->getField('description');
    }
}
