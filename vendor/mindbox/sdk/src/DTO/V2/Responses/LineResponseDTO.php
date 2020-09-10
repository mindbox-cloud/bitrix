<?php


namespace Mindbox\DTO\V2\Responses;

use Mindbox\DTO\V2\LineDTO;

/**
 * Class LineResponseDTO
 *
 * @package Mindbox\DTO\V2\Responses
 * @property SkuResponseDTO                    $sku
 * @property string                            $basePricePerItem
 * @property AppliedDiscountResponseCollection $appliedDiscounts
 * @property PlaceholderResponseCollection     $placeHolders
 * @property GiftCardResponseDTO               $giftCard
 * @property string                            $discountedPrice
 **/
class LineResponseDTO extends LineDTO
{
    /**
     * @var array Мэппинг преобразрования полей в объекты DTO.
     */
    protected static $DTOMap = [
        'sku'              => SkuResponseDTO::class,
        'appliedDiscounts' => AppliedDiscountResponseCollection::class,
        'placeHolders'     => PlaceholderResponseCollection::class,
        'giftCard'         => GiftCardResponseDTO::class,
    ];

    /**
     * @return SkuResponseDTO
     */
    public function getSku()
    {
        return $this->getField('sku');
    }

    /**
     * @return string
     */
    public function getBasePricePerItem()
    {
        return $this->getField('basePricePerItem');
    }

    /**
     * @return AppliedDiscountResponseCollection
     */
    public function getAppliedDiscounts()
    {
        return $this->getField('appliedDiscounts');
    }

    /**
     * @return PlaceholderResponseCollection
     */
    public function getPlaceholders()
    {
        return $this->getField('placeHolders');
    }

    /**
     * @return GiftCardResponseDTO
     */
    public function getGiftCard()
    {
        return $this->getField('giftCard');
    }

    /**
     * @return string
     */
    public function getDiscountedPrice()
    {
        return $this->getField('discountedPrice');
    }
}
