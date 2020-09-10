<?php


namespace Mindbox\DTO\V2\Requests;

/**
 * Class OrderCreateRequestDTO
 *
 * @package Mindbox\DTO\V2\Requests
 * @property string $createdDateTimeUtc
 * @property string $preOrderDiscountedTotalPrice
 **/
class OrderCreateRequestDTO extends OrderRequestDTO
{
    /**
     * @return string
     */
    public function getCreatedDateTimeUtc()
    {
        return $this->getField('createdDateTimeUtc');
    }

    /**
     * @param mixed $createdDateTimeUtc
     */
    public function setCreatedDateTimeUtc($createdDateTimeUtc)
    {
        $this->setField('createdDateTimeUtc', $createdDateTimeUtc);
    }

    /**
     * @return string
     */
    public function getPreOrderDiscountedTotalPrice()
    {
        return $this->getField('preOrderDiscountedTotalPrice');
    }

    /**
     * @param mixed $preOrderDiscountedTotalPrice
     */
    public function setPreOrderDiscountedTotalPrice($preOrderDiscountedTotalPrice)
    {
        $this->setField('preOrderDiscountedTotalPrice', $preOrderDiscountedTotalPrice);
    }
}
