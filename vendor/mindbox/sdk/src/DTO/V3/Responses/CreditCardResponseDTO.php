<?php


namespace Mindbox\DTO\V3\Responses;

use Mindbox\DTO\DTO;

/**
 * Class CreditCardResponseDTO
 *
 * @package Mindbox\DTO\V3\Responses
 * @property string $hash
 **/
class CreditCardResponseDTO extends DTO
{
    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'creditCard';

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->getField('hash');
    }
}
