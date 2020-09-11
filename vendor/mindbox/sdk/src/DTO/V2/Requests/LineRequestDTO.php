<?php


namespace Mindbox\DTO\V2\Requests;

use Mindbox\DTO\V2\LineDTO;

/**
 * Class LineRequestDTO
 *
 * @package Mindbox\DTO\V2\Requests
 * @property SkuRequestDTO             $sku
 * @property GiftCardRequestDTO        $giftCard
 * @property DiscountRequestCollection $discounts
 **/
class LineRequestDTO extends LineDTO
{
    use CustomFieldRequestDTO;
    /**
     * @var array Мэппинг преобразрования полей в объекты DTO.
     */
    protected static $DTOMap = [
        'sku'       => SkuRequestDTO::class,
        'giftCard'  => GiftCardRequestDTO::class,
        'discounts' => DiscountRequestCollection::class,
    ];

    /**
     * @param mixed $quantity
     */
    public function setQuantity($quantity)
    {
        $this->setField('quantity', $quantity);
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->setField('status', $status);
    }

    /**
     * @return SkuRequestDTO
     */
    public function getSku()
    {
        return $this->getField('sku');
    }

    /**
     * @param array|SkuRequestDTO $sku
     */
    public function setSku($sku)
    {
        $this->setField('sku', $sku);
    }

    /**
     * @return GiftCardRequestDTO
     */
    public function getGiftCard()
    {
        return $this->getField('giftCard');
    }

    /**
     * @param array|GiftCardRequestDTO $giftCard
     */
    public function setGiftCard($giftCard)
    {
        $this->setField('giftCard', $giftCard);
    }

    /**
     * @return DiscountRequestCollection
     */
    public function getDiscounts()
    {
        return $this->getField('discounts');
    }

    /**
     * @param array|DiscountRequestCollection $discounts
     */
    public function setDiscounts($discounts)
    {
        $this->setField('discounts', $discounts);
    }
}
