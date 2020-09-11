<?php


namespace Mindbox\DTO\V2;

use Mindbox\DTO\DTO;

/**
 * Class AreaDTO
 *
 * @package Mindbox\DTO\V2
 * @property array  $ids
 * @property string $name
 */
abstract class AreaDTO extends DTO
{
    use IdentityDTO;

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'area';

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getField('name');
    }
}
