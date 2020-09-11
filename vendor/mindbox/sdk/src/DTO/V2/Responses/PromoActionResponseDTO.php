<?php


namespace Mindbox\DTO\V2\Responses;

use Mindbox\DTO\DTO;
use Mindbox\DTO\V2\IdentityDTO;

/**
 * Class PromoActionResponseDTO
 *
 * @package Mindbox\DTO\V2\Responses
 * @property array  $ids
 * @property string $name
 */
class PromoActionResponseDTO extends DTO
{
    use IdentityDTO;

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'promoAction';

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getField('name');
    }
}
