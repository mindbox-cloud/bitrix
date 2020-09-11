<?php

namespace Mindbox\Responses;

use Mindbox\DTO\V3\Responses\CustomerResponseDTO;
use Mindbox\MindboxResponse;

/**
 * Класс, расширяющий стандартный класс ответа от Mindbox и используемый в стандартных запросах на изменение
 * потребителя.
 * Class MindboxCustomerIdentityResponse
 *
 * @package Mindbox
 */
class MindboxCustomerIdentityResponse extends MindboxResponse
{
    /**
     * Возвращает объект идинтификации потребителя, если такой есть в ответе.
     *
     * @return CustomerResponseDTO|null
     */
    public function getCustomerIdentity()
    {
        return $this->getResult()->getCustomer();
    }
}
