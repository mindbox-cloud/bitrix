<?php

namespace Mindbox\Responses;

use Mindbox\DTO\V3\Responses\BalanceResponseCollection;
use Mindbox\MindboxResponse;

/**
 * Класс, расширяющий стандартный класс ответа от Mindbox и используемый в стандартном запросе на получение баланса
 * потребителя.
 * Class MindboxBalanceResponse
 *
 * @package Mindbox
 */
class MindboxBalanceResponse extends MindboxResponse
{
    /**
     * Возвращает объект баланса потребителя, если такой присутствует в ответе.
     *
     * @return BalanceResponseCollection|null
     */
    public function getBalances()
    {
        return $this->getResult()->getBalances();
    }
}
