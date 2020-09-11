<?php


namespace Mindbox\DTO;

use Mindbox\DTO\V2\Responses\OrderResponseCollection;
use Mindbox\DTO\V2\Responses\OrderResponseDTO;
use Mindbox\DTO\V3\Responses\BalanceResponseCollection;
use Mindbox\DTO\V3\Responses\CustomerActionResponseCollection;
use Mindbox\DTO\V3\Responses\CustomerIdentityResponseCollection;
use Mindbox\DTO\V3\Responses\CustomerIdentityResponseDTO;
use Mindbox\DTO\V3\Responses\CustomerResponseDTO;
use Mindbox\DTO\V3\Responses\CustomerSegmentationResponseCollection;
use Mindbox\DTO\V3\Responses\DiscountCardResponseCollection;
use Mindbox\DTO\V3\Responses\ProductListItemResponseCollection;
use Mindbox\DTO\V3\Responses\SmsConfirmationResponseDTO;
use Mindbox\DTO\V3\Responses\ValidationMessageResponseCollection;

/**
 * Class ResultDTO
 *
 * @package Mindbox\DTO
 * @property string                                 $status
 * @property ValidationMessageResponseCollection    $validationMessages
 * @property CustomerResponseDTO                    $customer
 * @property SmsConfirmationResponseDTO             $smsConfirmation
 * @property CustomerIdentityResponseCollection     $customersToMerge
 * @property CustomerIdentityResponseDTO            $resultingCustomer
 * @property OrderResponseCollection                $orders
 * @property CustomerActionResponseCollection       $customerActions
 * @property CustomerSegmentationResponseCollection $customerSegmentations
 * @property ProductListItemResponseCollection      $productList
 * @property BalanceResponseCollection              $balances
 * @property string                                 $totalCount
 * @property OrderResponseDTO                       $order
 * @property DiscountCardResponseCollection         $discountCards
 **/
class ResultDTO extends DTO
{
    /**
     * @var array Мэппинг преобразрования полей в объекты DTO.
     */
    protected static $DTOMap = [
        'validationMessages'    => ValidationMessageResponseCollection::class,
        'customer'              => CustomerResponseDTO::class,
        'smsConfirmation'       => SmsConfirmationResponseDTO::class,
        'customersToMerge'      => CustomerIdentityResponseCollection::class,
        'resultingCustomer'     => CustomerIdentityResponseDTO::class,
        'orders'                => OrderResponseCollection::class,
        'customerActions'       => CustomerActionResponseCollection::class,
        'customerSegmentations' => CustomerSegmentationResponseCollection::class,
        'productList'           => ProductListItemResponseCollection::class,
        'balances'              => BalanceResponseCollection::class,
        'order'                 => OrderResponseDTO::class,
        'discountCards'         => DiscountCardResponseCollection::class,
    ];

    /**
     * @var string Название элемента для корректной генерации xml.
     */
    protected static $xmlName = 'result';

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->getField('status');
    }

    /**
     * @return ValidationMessageResponseCollection
     */
    public function getValidationMessages()
    {
        return $this->getField('validationMessages');
    }

    /**
     * @return CustomerResponseDTO
     */
    public function getCustomer()
    {
        return $this->getField('customer');
    }

    /**
     * @return SmsConfirmationResponseDTO
     */
    public function getSmsConfirmation()
    {
        return $this->getField('smsConfirmation');
    }

    /**
     * @return CustomerIdentityResponseCollection
     */
    public function getCustomersToMerge()
    {
        return $this->getField('customersToMerge');
    }

    /**
     * @return CustomerIdentityResponseDTO
     */
    public function getResultingCustomer()
    {
        return $this->getField('resultingCustomer');
    }

    /**
     * @return OrderResponseCollection
     */
    public function getOrders()
    {
        return $this->getField('orders');
    }

    /**
     * @return OrderResponseDTO
     */
    public function getOrder()
    {
        return $this->getField('order');
    }

    /**
     * @return CustomerActionResponseCollection
     */
    public function getCustomerActions()
    {
        return $this->getField('customerActions');
    }

    /**
     * @return string
     */
    public function getCustomerActionsCount()
    {
        return $this->getField('customerActionsCount');
    }

    /**
     * @return CustomerSegmentationResponseCollection
     */
    public function getCustomerSegmentations()
    {
        return $this->getField('customerSegmentations');
    }

    /**
     * @return ProductListItemResponseCollection
     */
    public function getProductList()
    {
        return $this->getField('productList');
    }

    /**
     * @return BalanceResponseCollection
     */
    public function getBalances()
    {
        return $this->getField('balances');
    }

    /**
     * @return string
     */
    public function getTotalCount()
    {
        return $this->getField('totalCount');
    }

    /**
     * @return DiscountCardResponseCollection
     */
    public function getDiscountCards()
    {
        return $this->getField('discountCards');
    }
}
