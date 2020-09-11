<?php


namespace Mindbox\DTO\V3\Responses;

use Mindbox\DTO\DTO;

/**
 * Class GiftCardResponseDTO
 *
 * @package Mindbox\DTO\V3\Responses
 * @property ProductResponseDTO        $product
 * @property string                    $amount
 * @property GiftCardStatusResponseDTO $status
 * @property string                    $basePricePerItem
 **/
class GiftCardResponseDTO extends DTO
{
    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'giftCard';

    /**
     * @var array Мэппинг преобразрования полей в объекты DTO.
     */
    protected static $DTOMap = [
        'product' => ProductResponseDTO::class,
        'status'  => GiftCardStatusResponseDTO::class,
    ];

    /**
     * @return ProductResponseDTO
     */
    public function getProduct()
    {
        return $this->getField('product');
    }

    /**
     * @return string
     */
    public function getAmount()
    {
        return $this->getField('amount');
    }

    /**
     * @return GiftCardStatusResponseDTO
     */
    public function getStatus()
    {
        return $this->getField('status');
    }

    /**
     * @return string
     */
    public function getBasePricePerItem()
    {
        return $this->getField('basePricePerItem');
    }
}
