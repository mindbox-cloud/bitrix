<?php


namespace Mindbox\DTO\V2;

use Mindbox\DTO\DTO;

/**
 * Class SubscriptionDTO
 *
 * @package Mindbox\DTO\V2
 * @property string $pointOfContact
 * @property string $topic
 * @property string $isSubscribed
 * @property string $brand
 **/
abstract class SubscriptionDTO extends DTO
{
    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'subscription';

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
    public function getTopic()
    {
        return $this->getField('topic');
    }

    /**
     * @return string
     */
    public function getIsSubscribed()
    {
        return $this->getField('isSubscribed');
    }

    /**
     * @return string
     */
    public function getBrand()
    {
        return $this->getField('brand');
    }
}
