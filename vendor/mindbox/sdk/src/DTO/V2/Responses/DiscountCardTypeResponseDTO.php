<?php


namespace Mindbox\DTO\V2\Responses;

use Mindbox\DTO\DTO;

/**
 * Class DiscountCardTypeResponseDTO
 *
 * @package Mindbox\DTO\V2\Responses
 * @property string $id
 **/
class DiscountCardTypeResponseDTO extends DTO
{
    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'type';

    /**
     * @return string
     */
    public function getId()
    {
        return $this->getField('id');
    }
}
