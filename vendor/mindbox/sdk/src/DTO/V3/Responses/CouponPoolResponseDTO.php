<?php


namespace Mindbox\DTO\V3\Responses;

use Mindbox\DTO\DTO;
use Mindbox\DTO\V3\IdentityDTO;

;

/**
 * Class CouponResponseDTO
 *
 * @package Mindbox\DTO\V3\Responses
 * @property array  $ids
 * @property string $name
 * @property string $description
 **/
class CouponPoolResponseDTO extends DTO
{
    use IdentityDTO;

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'pool';

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
