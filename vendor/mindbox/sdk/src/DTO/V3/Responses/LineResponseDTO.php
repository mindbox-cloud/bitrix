<?php


namespace Mindbox\DTO\V3\Responses;

use Mindbox\DTO\V3\LineDTO;

/**
 * Class LineResponseDTO
 *
 * @package Mindbox\DTO\V3\Responses
 * @property ProductIdentityResponseDTO         $product
 * @property SkuIdentityResponseDTO             $sku
 * @property string                             $basePricePerItem
 * @property string                             $priceOfLine
 * @property LineStatusResponseDTO              $status
 * @property AppliedPromotionResponseCollection $appliedPromotions
 * @property GiftCardResponseDTO                $giftCard
 * @property string                             $lineId
 **/
class LineResponseDTO extends LineDTO
{
    /**
     * @var array Мэппинг преобразрования полей в объекты DTO.
     */
    protected static $DTOMap = [
        'product'           => ProductIdentityResponseDTO::class,
        'sku'               => SkuIdentityResponseDTO::class,
        'status'            => LineStatusResponseDTO::class,
        'appliedPromotions' => AppliedPromotionResponseCollection::class,
        'giftCard'          => GiftCardResponseDTO::class,
    ];

    /**
     * @return ProductIdentityResponseDTO
     */
    public function getProduct()
    {
        return $this->getField('product');
    }

    /**
     * @return SkuIdentityResponseDTO
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
     * @return string
     */
    public function getPriceOfLine()
    {
        return $this->getField('priceOfLine');
    }

    /**
     * @return LineStatusResponseDTO
     */
    public function getStatus()
    {
        return $this->getField('status');
    }

    /**
     * @return AppliedPromotionResponseCollection
     */
    public function getAppliedPromotions()
    {
        return $this->getField('appliedPromotions');
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
    public function getLineId()
    {
        return $this->getField('lineId');
    }
}
