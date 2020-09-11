<?php


namespace Mindbox\DTO\V2;

use Mindbox\DTO\DTO;

/**
 * Class GiftCardDTO
 *
 * @package Mindbox\DTO\V2
 * @property string $id
 * @property string $getFromPool
 **/
abstract class GiftCardDTO extends DTO
{
    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'giftCard';

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
    public function getGetFromPool()
    {
        return $this->getField('getFromPool');
    }
}
