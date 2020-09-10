<?php


namespace Mindbox\DTO\V2\Requests;

use Mindbox\DTO\V2\PaymentDTO;

/**
 * Class PaymentRequestDTO
 *
 * @package Mindbox\DTO\V2\Requests
 **/
class PaymentRequestDTO extends PaymentDTO
{
    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->setField('type', $type);
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->setField('id', $id);
    }

    /**
     * @param mixed $amount
     */
    public function setAmount($amount)
    {
        $this->setField('amount', $amount);
    }
}
