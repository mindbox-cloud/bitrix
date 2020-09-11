<?php

namespace Mindbox\HttpClients;

use Mindbox\Exceptions\MindboxConfigException;

/**
 * Класс, отвечающий за инициализацию HTTP клиента, согласно пользовательской конфигурации.
 * Class HttpClientFactory
 *
 * @package Mindbox\HttpClients
 */
class HttpClientFactory
{
    /**
     * Метод, который инициализирует и возвращает объект HTTP клиента, согласно переданным параметрам.
     *
     * @param int|null    $timeout     Таймаут соединения.
     * @param string|null $handlerName Имя клиента ('curl'|'stream').
     *
     * @return IHttpClient
     * @throws MindboxConfigException
     */
    public function createHttpClient($timeout = null, $handlerName = null)
    {
        if (!is_null($timeout) && (int)$timeout <= 0) {
            throw new MindboxConfigException('Timeout must be an integer.');
        }

        if (!isset($handlerName)) {
            return $this->detectDefaultClient($timeout);
        }

        switch ($handlerName) {
            case 'curl':
                if (!$this->isCurlLoaded()) {
                    throw new MindboxConfigException(
                        'The cURL extension must be loaded in order to use the "curl" handler.'
                    );
                }

                return new CurlHttpClient(new MindboxCurl(), $timeout);
            case 'stream':
                return new StreamHttpClient(new MindboxStream(), $timeout);
            default:
                throw new MindboxConfigException('The http client handler must be set to "curl", "stream"');
        }
    }

    /**
     * Определение и инициализация клиента по умолчанию.
     * При наличиии расширения cURL возвращает экземпляр CurlHttpClient, иначе StreamHttpClient.
     *
     * @param int|null $timeout Таймаут соединения.
     *
     * @return IHttpClient
     */
    private function detectDefaultClient($timeout)
    {
        if ($this->isCurlLoaded()) {
            return new CurlHttpClient(new MindboxCurl(), $timeout);
        }

        return new StreamHttpClient(new MindboxStream(), $timeout);
    }

    /**
     * Метод, инкапсулирующий проверку наличия раширения cURL. Позволяет создать заглушку для тестирования вне
     * зависимости от реального окружения.
     *
     * @return bool
     */
    protected function isCurlLoaded()
    {
        return extension_loaded('curl');
    }
}
