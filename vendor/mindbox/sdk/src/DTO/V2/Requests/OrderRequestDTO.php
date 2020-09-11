<?php


namespace Mindbox\DTO\V2\Requests;

use Mindbox\DTO\V2\OrderDTO;

/**
 * Class OrderRequestDTO
 *
 * @package Mindbox\DTO\V2\Requests
 * @property CustomerRequestDTO        $customer
 * @property DiscountRequestCollection $discounts
 * @property string                    $deliveryCost
 * @property array                     $customFields
 * @property LineRequestCollection     $lines
 * @property PaymentRequestCollection  $payments
 **/
class OrderRequestDTO extends OrderDTO
{
    use IdentityRequestDTO, CustomFieldRequestDTO;
    /**
     * @var array Мэппинг преобразрования полей в объекты DTO.
     */
    protected static $DTOMap = [
        'customer'  => CustomerRequestDTO::class,
        'discounts' => DiscountRequestCollection::class,
        'lines'     => LineRequestCollection::class,
        'payments'  => PaymentRequestCollection::class,
    ];

    /**
     * @param mixed $pointOfContact
     */
    public function setPointOfContact($pointOfContact)
    {
        $this->setField('pointOfContact', $pointOfContact);
    }

    /**
     * @param mixed $area
     */
    public function setArea($area)
    {
        $this->setField('area', $area);
    }

    /**
     * @return LineRequestCollection
     */
    public function getLines()
    {
        return $this->getField('lines');
    }

    /**
     * @param array|LineRequestCollection $lines
     */
    public function setLines($lines)
    {
        $this->setField('lines', $lines);
    }

    /**
     * @return PaymentRequestCollection
     */
    public function getPayments()
    {
        return $this->getField('payments');
    }

    /**
     * @param array|PaymentRequestCollection $payments
     */
    public function setPayments($payments)
    {
        $this->setField('payments', $payments);
    }

    /**
     * @return CustomerRequestDTO
     */
    public function getCustomer()
    {
        return $this->getField('customer');
    }

    /**
     * @param array|CustomerRequestDTO $customer
     */
    public function setCustomer($customer)
    {
        $this->setField('customer', $customer);
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

    /**
     * @return string
     */
    public function getDeliveryCost()
    {
        return $this->getField('deliveryCost');
    }

    /**
     * @param mixed $deliveryCost
     */
    public function setDeliveryCost($deliveryCost)
    {
        $this->setField('deliveryCost', $deliveryCost);
    }
}
