<?php

namespace Mindbox\HttpClients;

use Mindbox\Exceptions\MindboxHttpClientException;

/**
 * Абстрактный класс, содержащий реализацию общих методов для всех HTTP клиентов.
 * Реализует интерйфес IHttpClient.
 * Class AbstractHttpClient
 *
 * @package Mindbox\HttpClients
 */
abstract class AbstractHttpClient implements IHttpClient
{
    /**
     * Таймаут соединения по умолчанию.
     */
    const DEFAULT_CONNECTION_TIMEOUT = 5;

    /**
     * Допустимые HTTP методы для отправки запросов.
     */
    protected static $allowedMethods = [
        'POST',
        'GET',
    ];

    /**
     * @var int Таймаут соединения.
     */
    protected $timeout;

    /**
     * Конструктор AbstractHttpClient.
     *
     * @param int|null $timeout Таймаут соединения.
     */
    public function __construct($timeout = null)
    {
        if (!isset($timeout)) {
            $timeout = static::DEFAULT_CONNECTION_TIMEOUT;
        }
        $this->timeout = (int)$timeout;
    }

    /**
     * Валидация и отправка запроса.
     *
     * @param \Mindbox\MindboxRequest $request Экземпляр запроса.
     *
     * @return HttpClientRawResponse
     * @throws MindboxHttpClientException
     */
    public function send($request)
    {
        $this->validateRequestMethod($request->getMethod());

        return $this->handleRequest($request);
    }

    /**
     * Валидация HTTP метода отправки запроса.
     * При недопустимом методе будет выброшено исключение MindboxHttpClientException.
     *
     * @param string $method HTTP метод отправки запроса.
     *
     * @throws MindboxHttpClientException
     */
    private function validateRequestMethod($method)
    {
        if (!in_array($method, static::$allowedMethods)) {
            throw new MindboxHttpClientException('Not allowed http method. Method must be set into "POST", "GET".');
        }
    }

    /**
     * Абстрактный метод для отправки запроса, должен быть реализован в дочерних классах.
     *
     * @param \Mindbox\MindboxRequest $request Экземпляр запроса.
     *
     * @return HttpClientRawResponse
     */
    abstract protected function handleRequest($request);
}
