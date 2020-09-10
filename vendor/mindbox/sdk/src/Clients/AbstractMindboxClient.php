<?php

namespace Mindbox\Clients;

use Mindbox\HttpClients\HttpClientRawResponse;
use Mindbox\HttpClients\IHttpClient;
use Mindbox\MindboxRequest;
use Mindbox\MindboxResponse;
use Psr\Log\LoggerInterface;

/**
 * Абстрактный класс, содержащий реализацию общих методов обработки запросов для различных версий API.
 * Class AbstractMindboxClient
 *
 * @package Mindbox\Clients
 */
abstract class AbstractMindboxClient
{
    /**
     * Версия API Mindbox, с которой работает клиент.
     */
    const API_VERSION = '';

    /**
     * @var string $secretKey Секретный ключ.
     */
    protected $secretKey;

    /**
     * @var IHttpClient $httpClient Экземпляр HTTP клиента.
     */
    protected $httpClient;

    /**
     * @var LoggerInterface $logger Экземпляр логгера.
     */
    protected $logger;

    /**
     * @var MindboxResponse $lastResponse Экземпляр последнего полученного ответа от Mindbox.
     */
    protected $lastResponse;

    /**
     * @var MindboxRequest $preparedRequest Экземпляр запроса к Mindbox, готовый к отправке.
     */
    protected $preparedRequest;

    /**
     * @var string $responseType Имя потомка MindboxResponse для парсинга ответа от Mindbox.
     */
    protected $responseType;

    /**
     * Конструктор AbstractMindboxClient.
     *
     * @param string          $secretKey  Секретный ключ.
     * @param IHttpClient     $httpClient Экземпляр HTTP клиента.
     * @param LoggerInterface $logger     Экземпляр логгера.
     */
    public function __construct($secretKey, IHttpClient $httpClient, LoggerInterface $logger)
    {
        $this->secretKey    = $secretKey;
        $this->httpClient   = $httpClient;
        $this->logger       = $logger;
        $this->responseType = MindboxResponse::class;
    }

    /**
     * Метод формирует объект запроса и записывает в $preparedRequest.
     *
     * @param string                $method        HTTP метод.
     * @param string                $operationName Название операции.
     * @param \Mindbox\DTO\DTO|null $body          Тело запроса в виде DTO.
     * @param string                $additionalUrl Дополнительный URL запроса.
     * @param array                 $queryParams   GET параметры запроса.
     * @param bool                  $isSync        Флаг: синхронный/асинхронный запрос.
     * @param bool                  $addDeviceUUID Флаг: добавлять ли в запрос DeviceUUID.
     *
     * @return $this
     */
    public function prepareRequest(
        $method,
        $operationName,
        \Mindbox\DTO\DTO $body = null,
        $additionalUrl = '',
        $queryParams = [],
        $isSync = true,
        $addDeviceUUID = true
    ) {
        $queryParams = $this->prepareQueryParams($operationName, $queryParams, $addDeviceUUID);

        $additionalUrl = $this->prepareUrl($additionalUrl, $queryParams, $isSync);

        $headers = $this->prepareHeaders($addDeviceUUID);

        $body = $this->prepareBody($body);

        $this->setRequest($this->createRequest($additionalUrl, $method, $body, $headers));

        return $this;
    }

    /**
     * Сеттер для $preparedRequest.
     *
     * @param MindboxRequest $request
     *
     * @return $this
     */
    public function setRequest(MindboxRequest $request)
    {
        $this->preparedRequest = $request;

        return $this;
    }

    /**
     * Геттер для $preparedRequest.
     *
     * @return MindboxRequest
     */
    public function getRequest()
    {
        return $this->preparedRequest;
    }

    /**
     * Передача подготовленного запроса в HTTP клиент для отправки, обработка ответа.
     *
     * @return MindboxResponse
     * @throws \Mindbox\Exceptions\MindboxBadRequestException
     * @throws \Mindbox\Exceptions\MindboxConflictException
     * @throws \Mindbox\Exceptions\MindboxForbiddenException
     * @throws \Mindbox\Exceptions\MindboxNotFoundException
     * @throws \Mindbox\Exceptions\MindboxTooManyRequestsException
     * @throws \Mindbox\Exceptions\MindboxUnauthorizedException
     * @throws \Mindbox\Exceptions\MindboxUnavailableException
     * @throws \Mindbox\Exceptions\MindboxClientException
     */
    public function sendRequest()
    {
        if (empty($this->preparedRequest)) {
            throw new \Mindbox\Exceptions\MindboxClientException(
                'Empty request. Try to use ::prepareRequest or ::setRequest methods.'
            );
        }

        try {
            $response = $this->httpClient->send($this->preparedRequest);
        } catch (\Mindbox\Exceptions\MindboxHttpClientException $exception) {
            throw new \Mindbox\Exceptions\MindboxClientException(
                'Http client error: ' . $exception->getMessage(),
                0,
                $exception
            );
        }

        return $this->parseRawResponse($this->preparedRequest, $response);
    }

    /**
     * Абстрактный метод по формированию массива GET параметров запроса.
     *
     * @param string $operation     Название операции.
     * @param array  $queryParams   GET параметры, переданные пользователем.
     * @param bool   $addDeviceUUID Флаг: добавлять ли в запрос DeviceUUID.
     *
     * @return array
     */
    abstract protected function prepareQueryParams($operation, array $queryParams, $addDeviceUUID = true);

    /**
     * Абстрактный метод по формированию полного URL запроса.
     *
     * @param string $additionalUrl Дополнительный URL запроса.
     * @param array  $queryParams   GET параметры, переданные пользователем.
     * @param bool   $isSync        Флаг: синхронный/асинхронный запрос.
     *
     * @return string
     */
    abstract protected function prepareUrl($additionalUrl, array $queryParams, $isSync);

    /**
     * Подготовка массива заголовков запроса.
     *
     * @param bool $addDeviceUUID Флаг: добавлять ли в запрос DeviceUUID.
     *
     * @return array
     */
    protected function prepareHeaders($addDeviceUUID = true)
    {
        return [
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Authorization' => $this->prepareAuthorizationHeader(),
        ];
    }

    /**
     * Абстрактный метод по формированию содержимого заголовка Authorization.
     *
     * @return string
     */
    abstract protected function prepareAuthorizationHeader();

    /**
     * Конвертация тела запроса в формат, пригодный для HTTP клиента (json, xml).
     *
     * @param \Mindbox\DTO\DTO|null $body Тело запроса в виде DTO.
     *
     * @return string
     */
    abstract protected function prepareBody(\Mindbox\DTO\DTO $body = null);

    /**
     * Инициализация экземпляра запроса.
     *
     * @param string $url     Полный URL запроса.
     * @param string $method  Метод запроса.
     * @param string $body    Тело запроса.
     * @param array  $headers Заголовки запроса.
     *
     * @return MindboxRequest
     */
    protected function createRequest($url, $method, $body, $headers)
    {
        return new MindboxRequest(static::API_VERSION, $url, $method, $body, $headers);
    }

    /**
     * Парсинг и первичная обработка сырого ответа от HTTP клиента:
     * - запись результата запроса в лог;
     * - генерация исключения при наличии ошибки в ответе;
     * - инициализация экземпляра MindboxResponse, содержащего отформатированные данные ответа.
     *
     * @param MindboxRequest        $request     Экземпляр запроса.
     * @param HttpClientRawResponse $rawResponse Экземпляр сырого ответа.
     *
     * @return MindboxResponse
     * @throws \Mindbox\Exceptions\MindboxBadRequestException
     * @throws \Mindbox\Exceptions\MindboxConflictException
     * @throws \Mindbox\Exceptions\MindboxForbiddenException
     * @throws \Mindbox\Exceptions\MindboxNotFoundException
     * @throws \Mindbox\Exceptions\MindboxTooManyRequestsException
     * @throws \Mindbox\Exceptions\MindboxUnauthorizedException
     * @throws \Mindbox\Exceptions\MindboxUnavailableException
     * @throws \Mindbox\Exceptions\MindboxClientException
     */
    final protected function parseRawResponse(MindboxRequest $request, HttpClientRawResponse $rawResponse)
    {
        $statusCode = $rawResponse->getStatusCode();
        $body       = $rawResponse->getBody();

        $this->setLastResponse(
            new $this->responseType(
                $statusCode,
                $rawResponse->getHeaders(),
                $this->prepareResponseBody($body),
                $body,
                $request
            )
        );

        $message = date('d.m.Y H:i:s');
        $context = $this->prepareContext($this->lastResponse);

        if (empty($body)) {
            $this->logger->error($message, $context);
            throw new \Mindbox\Exceptions\MindboxClientException('Empty response body');
        }

        switch ($statusCode) {
            case 200:
                $this->logger->info($message, $context);
                break;
            case 500:
            case 503:
                $this->logger->alert($message, $context);
                throw new \Mindbox\Exceptions\MindboxUnavailableException('Service unavailable');
            case 400:
                $this->logger->error($message, $context);
                throw new \Mindbox\Exceptions\MindboxBadRequestException('Bad request');
            case 409:
                $this->logger->error($message, $context);
                throw new \Mindbox\Exceptions\MindboxConflictException('Conflict');
            case 404:
                $this->logger->error($message, $context);
                throw new \Mindbox\Exceptions\MindboxNotFoundException('Not found');
            case 403:
                $this->logger->error($message, $context);
                throw new \Mindbox\Exceptions\MindboxForbiddenException('Forbidden');
            case 401:
                $this->logger->error($message, $context);
                throw new \Mindbox\Exceptions\MindboxUnauthorizedException('Unauthorized');
            case 429:
                $this->logger->error($message, $context);
                throw new \Mindbox\Exceptions\MindboxTooManyRequestsException('Too many requests');
            default:
                $this->logger->error($message, $context);
                throw new \Mindbox\Exceptions\MindboxClientException('Unknown http response code');
        }

        return $this->lastResponse;
    }

    /**
     * Сеттер для $lastResponse.
     *
     * @param MindboxResponse $response
     */
    private function setLastResponse(MindboxResponse $response)
    {
        $this->lastResponse = $response;
    }

    /**
     * Геттер для $lastResponse.
     *
     * @return  MindboxResponse
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * Конвертация тела ответа в массив.
     *
     * @param string $rawBody Сырое тело ответа.
     *
     * @return array
     */
    abstract protected function prepareResponseBody($rawBody);

    /**
     * Подготовка контекста запроса для логгера.
     *
     * @param MindboxResponse $response Ответ от Mindbox.
     *
     * @return array
     */
    private function prepareContext($response)
    {
        $request = $response->getRequest();

        return [
            'request'  => [
                'url'     => $request->getUrl(),
                'method'  => $request->getMethod(),
                'headers' => $request->getCleanHeaders(),
                'body'    => $request->getCleanBody(),
            ],
            'response' => [
                'httpCode' => $response->getHttpCode(),
                'headers'  => $response->getHeaders(),
                'body'     => $response->getRawBody(),
            ],
        ];
    }

    /**
     * Сеттер для responseType.
     *
     * @param string $type Имя потомка MindboxResponse.
     */
    public function setResponseType($type)
    {
        if (!class_exists($type)) {
            return;
        }
        if (!is_subclass_of($type, MindboxResponse::class)) {
            return;
        }
        $this->responseType = $type;
    }
}
