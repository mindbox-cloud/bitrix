<?php


namespace Mindbox\DTO\V2\Requests;

use Mindbox\DTO\V2\DiscountDTO;

/**
 * Class DiscountRequestDTO
 *
 * @package Mindbox\DTO\V2\Requests
 **/
class DiscountRequestDTO extends DiscountDTO
{
    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->setField('type', $type);
    }

    /**
     * @param mixed $amount
     */
    public function setAmount($amount)
    {
        $this->setField('amount', $amount);
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->setField('id', $id);
    }
}
