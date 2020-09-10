<?php


namespace Mindbox\Clients;

use Mindbox\XMLHelper\MindboxXMLSerializer;
use Mindbox\Exceptions\MindboxConfigException;
use Mindbox\HttpClients\IHttpClient;
use Psr\Log\LoggerInterface;

/**
 * Класс, отвечающий за инициализацию Mindbox API клиента, согласно пользовательской конфигурации.
 * Class MindboxClientFactory
 *
 * @package Mindbox\Clients
 */
class MindboxClientFactory
{
    /**
     * Конструктор MindboxClientFactory.
     *
     * @param string          $apiVersion Версия Mindbox API.
     * @param string          $endpointId Уникальный идентификатор сайта/мобильного приложения/и т.п.
     * @param string          $secretKey  Секретный ключ.
     * @param string          $domain     Домен.
     * @param IHttpClient     $httpClient Экземпляр HTTP клиента.
     * @param LoggerInterface $logger     Экземпляр логгера.
     *
     * @return AbstractMindboxClient
     */
    public function createMindboxClient(
        $apiVersion,
        $endpointId,
        $secretKey,
        $domain,
        IHttpClient $httpClient,
        LoggerInterface $logger
    ) {
        if (empty($secretKey)) {
            throw new MindboxConfigException('Secret key cant`t be empty');
        }
        switch ($apiVersion) {
            case 'v3':
                if (empty($endpointId)) {
                    throw new MindboxConfigException('Endpoint id cant`t be empty for v3 API');
                }

                return new MindboxClientV3($endpointId, $secretKey, $httpClient, $logger);
            case 'v2.1':
                if (empty($domain)) {
                    throw new MindboxConfigException('Domain cant`t be empty for v2.1 API');
                }

                return new MindboxClientV2(
                    $domain,
                    $secretKey,
                    $httpClient,
                    $logger,
                    new MindboxXMLSerializer()
                );
            default:
                throw new MindboxConfigException('The api version must be set to "v3", "v2.1"');
        }
    }
}
