<?php

namespace Mindbox\HttpClients;

use Mindbox\Exceptions\MindboxHttpClientException;
use Mindbox\MindboxRequest;

/**
 * Интерфейс, объявляющий основные методы для отправки HTTP запросов.
 * Interface IHttpClient
 *
 * @package Mindbox\HttpClients
 */
interface IHttpClient
{
    /**
     * Базовый метод отправки HTTP запроса, который должен быть реализован в каждом HTTP клиенте.
     *
     * @param MindboxRequest $request Экземпляр запроса.
     *
     * @return HttpClientRawResponse
     * @throws MindboxHttpClientException
     */
    public function send($request);
}
