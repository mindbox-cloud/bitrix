<?php

namespace Mindbox\Responses;

use Mindbox\MindboxResponse;

/**
 * Класс, расширяющий стандартный класс ответа от Mindbox и используемый в стандартных запросах на отправку кода
 * подтверждения.
 * Class MindboxCustomerProcessingStatusResponse
 *
 * @package Mindbox
 */
class MindboxCustomerProcessingStatusResponse extends MindboxResponse
{
    /**
     * Возвращает статус потребителя, если он присутствует в ответе.
     *
     * @return string|null
     */
    public function getProcessingStatus()
    {
        if (is_null($customer = $this->getResult()->getCustomer())) {
            return null;
        }

        return $customer->getProcessingStatus();
    }
}
