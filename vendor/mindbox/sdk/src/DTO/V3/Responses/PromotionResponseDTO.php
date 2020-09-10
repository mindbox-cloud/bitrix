<?php


namespace Mindbox\DTO\V3\Responses;

use Mindbox\DTO\DTO;
use Mindbox\DTO\V3\IdentityDTO;

;

/**
 * Class PromotionResponseDTO
 *
 * @package Mindbox\DTO\V3\Responses
 * @property array  $ids
 * @property string $name
 * @property string $type
 **/
class PromotionResponseDTO extends DTO
{
    use IdentityDTO;

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'promotion';

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
    public function getType()
    {
        return $this->getField('type');
    }
}
