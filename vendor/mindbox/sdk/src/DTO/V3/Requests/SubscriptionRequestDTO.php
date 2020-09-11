<?php


namespace Mindbox\DTO\V3\Requests;

use Mindbox\DTO\V3\SubscriptionDTO;

/**
 * Class SubscriptionRequestDTO
 *
 * @package Mindbox\DTO\V3\Requests
 * @property string $valueByDefault
 **/
class SubscriptionRequestDTO extends SubscriptionDTO
{
    /**
     * @param mixed $pointOfContact
     */
    public function setPointOfContact($pointOfContact)
    {
        $this->setField('pointOfContact', $pointOfContact);
    }

    /**
     * @param mixed $topic
     */
    public function setTopic($topic)
    {
        $this->setField('topic', $topic);
    }

    /**
     * @param mixed $isSubscribed
     */
    public function setIsSubscribed($isSubscribed)
    {
        $this->setField('isSubscribed', $isSubscribed);
    }

    /**
     * @param mixed $brand
     */
    public function setBrand($brand)
    {
        $this->setField('brand', $brand);
    }

    /**
     * @return string
     */
    public function getValueByDefault()
    {
        return $this->getField('valueByDefault');
    }

    /**
     * @param mixed $valueByDefault
     */
    public function setValueByDefault($valueByDefault)
    {
        $this->setField('valueByDefault', $valueByDefault);
    }
}
