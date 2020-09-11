<?php

namespace Mindbox\HttpClients;

use Mindbox\Exceptions\MindboxHttpClientException;
use Mindbox\MindboxRequest;

/**
 * Класс, реализующий абстрактные методы AbstractHttpClient для отправки HTTP запросов при помощи стандартных функций
 * PHP.
 * Class StreamHttpClient
 *
 * @package Mindbox\HttpClients
 */
class StreamHttpClient extends AbstractHttpClient
{
    /**
     * @var MindboxStream Абстракция, инкапсулирующая все используемые в классе стандартные методы PHP.
     *                    Позволяет создать заглушку для тестирования вне зависимости от реального окружения.
     */
    protected $stream;

    /**
     * Конструктор CurlHttpClient.
     *
     * @param MindboxStream $stream  Экземпляр MindboxStream.
     * @param int|null      $timeout Таймаут соединения.
     */
    public function __construct(MindboxStream $stream, $timeout = null)
    {
        $this->stream = $stream;
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
        $options = $this->getOptions($request->getMethod(), $request->getBody(), $request->getHeaders());
        $this->stream->contextCreate($options);
        $request = $request->getUrl();

        try {
            $rawBody = $this->stream->fileGetContents($request);
        } catch (\Exception $exception) {
            throw new MindboxHttpClientException('Stream throw exception.', 0, $exception);
        }

        $rawHeaders = $this->stream->getRawHeaders();

        if (empty($rawBody) || empty($rawHeaders)) {
            throw new MindboxHttpClientException('Stream return an empty response.');
        }

        return new HttpClientRawResponse($rawHeaders, $rawBody);
    }

    /**
     * Формирование массива параметров запроса.
     *
     * @param string $method  HTTP метод запроса.
     * @param string $body    Тело запроса.
     * @param array  $headers Заголовки запроса.
     *
     * @return array
     */
    private function getOptions($method, $body, $headers)
    {
        return $options = [
            'http' => [
                'method'        => $method,
                'header'        => $this->compileHeader($headers),
                'content'       => $body,
                'timeout'       => $this->timeout,
                'ignore_errors' => true,
            ],
        ];
    }

    /**
     * Компиляция заголовков запроса в формат, понятный MindboxStream.
     *
     * @param array $headers Исходный массив заголовков.
     *
     * @return string
     */
    private function compileHeader(array $headers)
    {
        $header = [];
        foreach ($headers as $k => $v) {
            $header[] = $k . ': ' . $v;
        }

        return implode("\r\n", $header);
    }
}
