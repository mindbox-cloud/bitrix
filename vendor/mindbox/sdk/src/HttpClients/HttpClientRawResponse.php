<?php

namespace Mindbox\HttpClients;

/**
 * Класс, содержащий "сырые" данные ответа от Mindbox API.
 * Class HttpClientRawResponse
 *
 * @package Mindbox\HttpClients
 */
class HttpClientRawResponse
{
    /**
     * @var int HTTP код ответа.
     */
    private $httpCode;

    /**
     * @var array Массив заголовков ответа.
     */
    private $headers;

    /**
     * @var string Тело ответа.
     */
    private $body;

    /**
     * Конструктор HttpClientRawResponse.
     *
     * @param array  $headers Заголовки ответа.
     * @param string $body    Тело ответа.
     */
    public function __construct($headers, $body)
    {
        $this->setHeadersAndCode($headers);
        $this->body = $body;
    }

    /**
     * Геттер для $httpCode.
     *
     * @return int
     */
    public function getStatusCode()
    {
        return (int)$this->httpCode;
    }

    /**
     * Геттер для $headers.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Парсинг "сырых" заголовков ответа в удобрчитаемый массив.
     *
     * @param array $rawHeaders "Сырые" заголовки ответа.
     */
    private function setHeadersAndCode($rawHeaders)
    {
        if (empty($rawHeaders)) {
            $this->headers = $rawHeaders;
        }
        $headers    = [];
        $httpCode   = 0;
        $rawHeaders = implode("\r\n", $rawHeaders);
        // Normalize line breaks
        $rawHeaders = str_replace("\r\n", "\n", $rawHeaders);
        // There will be multiple headers if a 301 was followed
        // or a proxy was followed, etc
        $headerCollection = explode("\n\n", trim($rawHeaders));
        // We just want the last response (at the end)
        $rawHeader        = array_pop($headerCollection);
        $headerComponents = explode("\n", (string)$rawHeader);
        foreach ($headerComponents as $line) {
            if (strpos($line, ': ') === false) {
                $code = $this->getHttpResponseCodeFromHeader($line);
                if ($httpCode === 0) {
                    $httpCode = $code;
                } elseif ($httpCode > 0 && $code > 0) {
                    $httpCode = $code;
                }
            } else {
                list($key, $value) = explode(': ', $line, 2);
                $headers[$key] = $value;
            }
        }

        $this->httpCode = $httpCode;
        $this->headers  = $headers;
    }

    /**
     * Геттер для $body.
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Получение HTTP кода ответа из "сырых" заголовков.
     * @see https://tools.ietf.org/html/rfc7230#section-3.1.2
     *
     * @param string $rawResponseHeader "Сырые" заголовки ответа.
     *
     * @return int
     */
    private function getHttpResponseCodeFromHeader($rawResponseHeader)
    {
        list($version, $status, $reason) = array_pad(explode(' ', $rawResponseHeader, 3), 3, null);

        return (int)$status;
    }
}
