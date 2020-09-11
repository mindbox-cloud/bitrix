<?php

namespace Mindbox\Responses;

use Mindbox\DTO\V2\Responses\OrderResponseCollection;
use Mindbox\MindboxResponse;

/**
 * Класс, расширяющий стандартный класс ответа от Mindbox и используемый в стандартном запросе на получение заказов
 * потребителя.
 * Class MindboxOrdersResponse
 *
 * @package Mindbox
 */
class MindboxOrdersResponse extends MindboxResponse
{
    /**
     * Возвращает заказы, если такие есть в ответе.
     *
     * @return OrderResponseCollection|null
     */
    public function getOrders()
    {
        return $this->getResult()->getOrders();
    }

    /**
     * Возвращает количество заказов, если оно есть в ответе.
     *
     * @return string|null
     */
    public function getTotalCount()
    {
        return $this->getResult()->getTotalCount();
    }
}
