<?php


namespace Mindbox\DTO\V3;

use Mindbox\DTO\DTO;

/**
 * Class LineDTO
 *
 * @package Mindbox\DTO\V3
 * @property string $quantity
 **/
abstract class LineDTO extends DTO
{
    use CustomFieldDTO;

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'line';

    /**
     * @return string
     */
    public function getQuantity()
    {
        return $this->getField('quantity');
    }
}
