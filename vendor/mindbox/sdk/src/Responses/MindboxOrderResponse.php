<?php

namespace Mindbox\Responses;

use Mindbox\MindboxResponse;
use Mindbox\DTO\V2\Responses\OrderResponseDTO;

/**
 * Класс, расширяющий стандартный класс ответа от Mindbox и используемый в стандартных запросах по изменению заказа.
 * Class MindboxOrderResponse
 *
 * @package Mindbox
 */
class MindboxOrderResponse extends MindboxResponse
{
    /**
     * Возвращает объект заказа, если такой есть в ответе.
     *
     * @return OrderResponseDTO|null
     */
    public function getOrder()
    {
        return $this->getResult()->getOrder();
    }
}
