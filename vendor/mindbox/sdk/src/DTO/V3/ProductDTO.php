<?php


namespace Mindbox\DTO\V3;

/**
 * Trait ProductDTO
 *
 * @package Mindbox\DTO\V3
 * @property string $price
 * @property string $name
 * @property string $description
 * @property string $isAvailable
 * @property string $oldPrice
 * @property string $shelfLife
 * @property string $url
 * @property string $pictureUrl
 * @property array  $customFields
 **/
trait ProductDTO
{
    /**
     * @return string
     */
    public function getPrice()
    {
        return $this->getField('price');
    }

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
    public function getDescription()
    {
        return $this->getField('description');
    }

    /**
     * @return string
     */
    public function getIsAvailable()
    {
        return $this->getField('isAvailable');
    }

    /**
     * @return string
     */
    public function getOldPrice()
    {
        return $this->getField('oldPrice');
    }

    /**
     * @return string
     */
    public function getShelfLife()
    {
        return $this->getField('shelfLife');
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->getField('url');
    }

    /**
     * @return string
     */
    public function getPictureUrl()
    {
        return $this->getField('pictureUrl');
    }
}
