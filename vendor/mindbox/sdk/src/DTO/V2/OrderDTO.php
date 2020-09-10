<?php


namespace Mindbox\DTO\V2;

use Mindbox\DTO\DTO;

/**
 * Class OrderDTO
 *
 * @package Mindbox\DTO\V2
 * @property array  $ids
 * @property string $pointOfContact
 * @property string $area
 **/
abstract class OrderDTO extends DTO
{
    use IdentityDTO;

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'order';

    /**
     * @return string
     */
    public function getPointOfContact()
    {
        return $this->getField('pointOfContact');
    }

    /**
     * @return string
     */
    public function getArea()
    {
        return $this->getField('area');
    }
}
