<?php

namespace Mindbox\HttpClients;

/**
 * Абстракция, инкапсулирующая все используемые в классе стандартные методы PHP. Позволяет создать заглушку для
 * тестирования вне зависимости от реального окружения.
 * Class MindboxStream
 *
 * @package Mindbox\HttpClients
 * @codeCoverageIgnore
 */
class MindboxStream
{
    /**
     * @var resource $stream Контекст потока.
     */
    protected $stream;

    /**
     * @var array $rawHeaders "Сырые" заголовки ответа.
     */
    protected $rawHeaders;

    /**
     * Создание контекста потока.
     *
     * @var array $options Опции контекста.
     */
    public function contextCreate($options)
    {
        $this->stream = stream_context_create($options);
    }

    /**
     * Отправка запроса и установка заголовков ответа. Возвращает тело ответа.
     *
     * @param string $url URL запроса.
     *
     * @return string
     */
    public function fileGetContents($url)
    {
        $rawBody    = file_get_contents($url, false, $this->stream);
        $rawHeaders = !empty($http_response_header) ? $http_response_header : [];
        $this->setRawHeaders($rawHeaders);

        return trim((string)$rawBody);
    }

    /**
     * Сеттер для $rawHeaders.
     *
     * @param array $rawHeaders
     */
    private function setRawHeaders($rawHeaders)
    {
        $this->rawHeaders = $rawHeaders;
    }

    /**
     * Геттер для $rawHeaders.
     *
     * @return array
     */
    public function getRawHeaders()
    {
        return $this->rawHeaders;
    }
}
