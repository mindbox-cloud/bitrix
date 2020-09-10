<?php

namespace Mindbox\HttpClients;

use Mindbox\Exceptions\MindboxHttpClientException;
use Mindbox\MindboxRequest;

/**
 * Класс, реализующий абстрактные методы AbstractHttpClient для отправки HTTP запросов при помощи cURL.
 * http://php.net/manual/ru/book.curl.php
 * Class CurlHttpClient
 *
 * @package Mindbox\HttpClients
 */
class CurlHttpClient extends AbstractHttpClient
{
    /**
     * @var MindboxCurl Абстракция, инкапсулирующая все используемые в классе методы библиотеки cURL.
     *                  Позволяет создать заглушку для тестирования вне зависимости от реального окружения.
     */
    protected $curl;

    /**
     * Конструктор CurlHttpClient.
     *
     * @param MindboxCurl $curl    Экземпляр MindboxCurl.
     * @param int|null    $timeout Таймаут соединения.
     */
    public function __construct(MindboxCurl $curl, $timeout = null)
    {
        $this->curl = $curl;
        parent::__construct($timeout);
    }

    /**
     * Формирование и отправка запроса.
     *
     * @param MindboxRequest $request Экземпляр запроса.
     *
     * @return HttpClientRawResponse
     * @throws MindboxHttpClientException
     */
    public function handleRequest($request)
    {
        $options = $this->getOptions(
            $request->getUrl(),
            $request->getMethod(),
            $request->getBody(),
            $request->getHeaders()
        );

        $this->curl->init();
        $this->curl->setOptArray($options);
        $content = $this->curl->exec();

        if ($this->curl->errno()) {
            throw new MindboxHttpClientException('Curl error: ' . $this->curl->error());
        }

        $this->curl->close();

        list($rawHeaders, $rawBody) = $this->extractResponseHeadersAndBody($content);

        return new HttpClientRawResponse($rawHeaders, $rawBody);
    }

    /**
     * Компиляция массива параметров запроса для cURL.
     *
     * @param string $url     URL запроса.
     * @param string $method  HTTP метод запроса.
     * @param string $body    Тело запроса.
     * @param array  $headers Заголовки запроса.
     *
     * @return array
     */
    private function getOptions($url, $method, $body, $headers)
    {
        $options = [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $this->compileHeader($headers),
            CURLOPT_URL            => $url,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
        ];

        if ($method !== "GET") {
            $options[CURLOPT_POSTFIELDS] = $body;
        }

        return $options;
    }

    /**
     * Компиляция заголовков запроса в формат, понятный cURL.
     *
     * @param array $headers Исходный массив заголовков.
     *
     * @return array
     */
    private function compileHeader(array $headers)
    {
        $header = [];

        foreach ($headers as $k => $v) {
            $header[] = $k . ': ' . $v;
        }

        return $header;
    }

    /**
     * Извлечение заголовков и тела ответа в массив из двух частей.
     *
     * @param string $content Исходная строка ответа.
     *
     * @return array
     */
    private function extractResponseHeadersAndBody($content)
    {
        $parts      = explode("\r\n\r\n", $content);
        $rawBody    = array_pop($parts);
        $rawHeaders = $parts;

        return [$rawHeaders, trim((string)$rawBody)];
    }
}
