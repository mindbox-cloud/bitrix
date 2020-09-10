<?php

namespace Mindbox\HttpClients;

/**
 * Абстракция, инкапсулирующая все используемые в классе методы библиотеки cURL. Позволяет создать заглушку для
 * тестирования вне зависимости от реального окружения.
 * Class MindboxCurl
 *
 * @package Mindbox\HttpClients
 * @codeCoverageIgnore
 */
class MindboxCurl
{
    /**
     * @var resource $curl Дескриптор cURL.
     */
    protected $curl;

    /**
     * Инициализация сеанса cURL.
     */
    public function init()
    {
        $this->curl = curl_init();
    }

    /**
     * Установка массива параметров для сеанса cURL.
     *
     * @param array $options Параметры.
     */
    public function setOptArray(array $options)
    {
        curl_setopt_array($this->curl, $options);
    }

    /**
     * Выполнение запроса cURL.
     *
     * @return mixed
     */
    public function exec()
    {
        return curl_exec($this->curl);
    }

    /**
     * Завершение сеанса cURL.
     */
    public function close()
    {
        curl_close($this->curl);
    }

    /**
     * Возвращает код ошибки cURL.
     */
    public function errno()
    {
        return curl_errno($this->curl);
    }

    /**
     * Возвращает текст ошибки cURL.
     */
    public function error()
    {
        return curl_error($this->curl);
    }
}
