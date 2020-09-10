<?php


namespace Mindbox;

/**
 * Класс, содержащий все данные запроса (url, заголовки, метод, тело) и методы для получения этих данных.
 * Class MindboxRequest
 *
 * @package Mindbox
 */
class MindboxRequest
{
    /**
     * @var string $apiVersion Версия Mindbox API.
     */
    private $apiVersion;

    /**
     * @var string $url URL запроса.
     */
    private $url;

    /**
     * @var string $method Метод HTTP запроса.
     */
    private $method;

    /**
     * @var string $body Тело запроса.
     */
    private $body;

    /**
     * @var array $headers Заголовки запроса.
     */
    private $headers;

    /**
     * Конструктор MindboxRequest.
     *
     * @param string $apiVersion
     * @param string $url     URL запроса.
     * @param string $method  Метод HTTP запроса.
     * @param string $body    Тело запроса.
     * @param array  $headers Заголовки запроса.
     */
    public function __construct($apiVersion, $url, $method, $body, array $headers)
    {
        $this->apiVersion = $apiVersion;
        $this->url        = $url;
        $this->method     = $method;
        $this->body       = $body;
        $this->headers    = $headers;
    }

    /**
     * Геттер для $apiVersion.
     *
     * @return string
     */
    public function getApiVersion()
    {
        return $this->apiVersion;
    }

    /**
     * Геттер для $url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Геттер для $method.
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
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
     * Геттер для $headers.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Геттер для тела запроса с зашифрованными персональными данными.
     *
     * @return string
     */
    public function getCleanBody()
    {
        return $this->cleanBody();
    }


    /**
     * @url http://php.net/manual/ru/language.oop5.magic.php#object.sleep
     *
     * Магичский метод, используемый присериализации экземпляра класса
     *
     * @return array
     */
    public function __sleep()
    {
        return [
            'apiVersion',
            'url',
            'method',
            'body',
            'headers'
        ];
    }

    /**
     * Шифрование пароля потребителя в теле ответа.
     *
     * @return string
     */
    private function cleanBody()
    {
        $res = $this->body;
        $res = preg_replace('|(<password>).+(</password>)|isU', "$1" . '*****' . "$2", $res);
        $res = preg_replace('|("password":").+(")|isU', "$1" . '*****' . "$2", (string)$res);

        return (string)$res;
    }

    /**
     * Геттер для заголовков запроса с зашифрованными персональными данными.
     *
     * @return array
     */
    public function getCleanHeaders()
    {
        return $this->cleanHeaders();
    }

    /**
     * Удаление секретного ключа из заголовков запроса.
     *
     * @return array
     */
    private function cleanHeaders()
    {
        $res = $this->headers;
        if (!empty($res['Authorization'])) {
            $res['Authorization'] = strstr($res['Authorization'], '"', true);
        }

        return $res;
    }
}
