<?php


namespace Mindbox\Clients;

use Mindbox\DTO\DTO;
use Mindbox\XMLHelper\MindboxXMLSerializer;
use Mindbox\HttpClients\IHttpClient;
use Psr\Log\LoggerInterface;

/**
 * Клиент для отправки запросов к v2.1 API Mindbox.
 * Class MindboxClientV2Api
 *
 * @package Mindbox\Clients
 */
class MindboxClientV2 extends AbstractMindboxClient
{
    /**
     * Версия API Mindbox с которой работает клиент.
     */
    const API_VERSION = 'v2.1';

    /**
     * Базовый URL на который будут отправляться запросы.
     */
    const BASE_V2_URL = 'https://{{domain}}/v2.1/orders/';

    /**
     * Секретный ключ.
     */
    const SECRET_KEY_NAME = 'DirectCrm key';

    /**
     * @var string Домен клиента.
     */
    private $domain;

    /**
     * @var MindboxXMLSerializer Экземпляр класса, отвечающего за сериализацию массива в XML.
     */
    private $xmlSerializer;

    /**
     * Конструктор MindboxRequest.
     *
     * @param string               $domain        Домен клиента.
     * @param string               $secretKey     Секретный ключ.
     * @param IHttpClient          $httpClient    Экземпляр HTTP клиента.
     * @param LoggerInterface      $logger        Экземпляр логгера.
     * @param MindboxXMLSerializer $xmlSerializer Экземпляр класса, отвечающего за сериализацию массива в XML.
     */
    public function __construct(
        $domain,
        $secretKey,
        IHttpClient $httpClient,
        LoggerInterface $logger,
        MindboxXMLSerializer $xmlSerializer
    ) {
        parent::__construct($secretKey, $httpClient, $logger);
        $this->domain        = $domain;
        $this->xmlSerializer = $xmlSerializer;
    }

    /**
     * Подготовка массива заголовков запроса.
     *
     * @param bool $addDeviceUUID Флаг: добявлять ли в запрос заголовок X-Customer-IP. (Не используется для второй
     *                            версии API).
     *
     * @return array
     */
    protected function prepareHeaders($addDeviceUUID = false)
    {
        return array_merge(parent::prepareHeaders(), [
            'Accept'       => 'application/xml',
            'Content-Type' => 'application/xml',
        ]);
    }

    /**
     * Подготовка полного URL запроса.
     *
     * @param string $url         Дополнительный URL.
     * @param array  $queryParams GET параметры запроса.
     * @param bool   $isSync      Синхронный/асинхронный запрос. (Не влияет на вторую версию API).
     *
     * @return string
     */
    protected function prepareUrl($url, array $queryParams, $isSync = true)
    {
        return str_replace('{{domain}}', $this->domain, static::BASE_V2_URL) .
            $url . '?' . http_build_query($queryParams);
    }

    /**
     * Подготовка массива GET параметров запроса.
     *
     * @param string $operation     Название операции.
     * @param array  $queryParams   GET параметры, переданные пользователем.
     * @param bool   $addDeviceUUID Флаг: добавлять ли параметр DeviceUUID. (Не используется для второй версии API).
     *
     * @return array
     */
    protected function prepareQueryParams($operation, array $queryParams, $addDeviceUUID = false)
    {
        return array_merge($queryParams, [
            'operation' => $operation,
        ]);
    }

    /**
     * Подготовка заголовка Authorization.
     *
     * @return string
     */
    protected function prepareAuthorizationHeader()
    {
        return static::SECRET_KEY_NAME . '="' . $this->secretKey . '"';
    }

    /**
     * Конвертация объекта DTO в XML.
     *
     * @param DTO|null $body Тело запроса в формате DTO.
     *
     * @return string
     */
    protected function prepareBody(DTO $body = null)
    {
        return $body ? $body->toXML() : '';
    }

    /**
     * Конвертация сырого тела ответа из XML в массив.
     *
     * @param string $rawBody Сырое тело ответа.
     *
     * @return array
     */
    protected function prepareResponseBody($rawBody)
    {
        return $rawBody ? $this->xmlSerializer->fromXMLToArray($rawBody) : [];
    }
}
