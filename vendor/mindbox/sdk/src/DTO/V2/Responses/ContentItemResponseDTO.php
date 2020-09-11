<?php


namespace Mindbox\DTO\V2\Responses;

use Mindbox\DTO\DTO;

/**
 * Class ContentItemResponseDTO
 *
 * @package Mindbox\DTO\V2\Responses
 * @property string $type
 * @property string $promoActionId
 * @property string $value
 **/
class ContentItemResponseDTO extends DTO
{
    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'contentItem';

    /**
     * @return string
     */
    public function getType()
    {
        return $this->getField('type');
    }

    /**
     * @return string
     */
    public function getPromoActionId()
    {
        return $this->getField('promoActionId');
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->getField('value');
    }
}
