<?php


namespace Mindbox\DTO\V2\Responses;

use Mindbox\DTO\DTO;

/**
 * Class PromoCodeResponseDTO
 *
 * @package Mindbox\DTO\V2\Responses
 * @property string                   $id
 * @property string                   $status
 * @property PromoCodeTypeResponseDTO $type
 * @property string                   $availableFromDateTimeUtc
 * @property string                   $availableTillDateTimeUtc
 * @property string                   $usedDateTimeUtc
 **/
class PromoCodeResponseDTO extends DTO
{
    /**
     * @var array Мэппинг преобразрования полей в объекты DTO.
     */
    protected static $DTOMap = [
        'type' => PromoCodeTypeResponseDTO::class,
    ];

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'promoCode';

    /**
     * @return string
     */
    public function getId()
    {
        return $this->getField('id');
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->getField('status');
    }

    /**
     * @return PromoCodeTypeResponseDTO
     */
    public function getType()
    {
        return $this->getField('type');
    }

    /**
     * @return string
     */
    public function getAvailableFromDateTimeUtc()
    {
        return $this->getField('availableFromDateTimeUtc');
    }

    /**
     * @return string
     */
    public function getAvailableTillDateTimeUtc()
    {
        return $this->getField('availableTillDateTimeUtc');
    }

    /**
     * @return string
     */
    public function getUsedDateTimeUtc()
    {
        return $this->getField('usedDateTimeUtc');
    }
}
