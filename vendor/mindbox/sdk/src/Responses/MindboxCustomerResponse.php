<?php

namespace Mindbox\Responses;

use Mindbox\DTO\V3\Responses\CustomerResponseDTO;
use Mindbox\DTO\V3\Responses\DiscountCardResponseCollection;
use Mindbox\MindboxResponse;

/**
 * Класс, расширяющий стандартный класс ответа от Mindbox и используемый в стандартных запросах на получение данных
 * потребителя.
 * Class MindboxCustomerResponse
 *
 * @package Mindbox
 */
class MindboxCustomerResponse extends MindboxResponse
{
    /**
     * Возвращает объект потребителя, если такой есть в ответе.
     *
     * @return CustomerResponseDTO|null
     */
    public function getCustomer()
    {
        return $this->getResult()->getCustomer();
    }

    /**
     * Возвращает коллекцию карт лояльности потребителя, если такие есть в ответе.
     *
     * @return DiscountCardResponseCollection|null
     */
    public function getDiscountCards()
    {
        return $this->getResult()->getDiscountCards();
    }
}
