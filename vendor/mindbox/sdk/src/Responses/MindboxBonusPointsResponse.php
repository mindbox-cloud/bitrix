<?php

namespace Mindbox\Responses;

use Mindbox\DTO\V3\Responses\BalanceResponseCollection;
use Mindbox\MindboxResponse;

/**
 * Класс, расширяющий стандартный класс ответа от Mindbox и используемый в стандартном запросе на получение истории
 * изменения баланса потребителя.
 * Class MindboxBonusPointsResponse
 *
 * @package Mindbox
 */
class MindboxBonusPointsResponse extends MindboxResponse
{
    /**
     * Возвращает статус потребителя, если такой есть в ответе.
     *
     * @return string|null
     */
    public function getCustomerProcessingStatus()
    {
        if (!($customer = $this->getResult()->getCustomer())) {
            return null;
        }

        return $customer->getProcessingStatus();
    }

    /**
     * Возвращает количество изменений баланса потребителя, если оно есть в ответе.
     *
     * @return string|null
     */
    public function getCustomerActionsCount()
    {
        return $this->getResult()->getCustomerActionsCount();
    }

    /**
     * Возвращает историю изменений баланса потребителя, если такая есть в ответе.
     *
     * @return BalanceResponseCollection|null
     */
    public function getBalances()
    {
        return $this->getResult()->getBalances();
    }
}
