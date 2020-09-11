<?php

namespace Mindbox;

use Mindbox\Clients\AbstractMindboxClient;
use Mindbox\Clients\MindboxClientFactory;
use Mindbox\Exceptions\MindboxConfigException;
use Mindbox\Exceptions\MindboxException;
use Mindbox\Helpers\CustomerHelper;
use Mindbox\Helpers\OrderHelper;
use Mindbox\Helpers\ProductListHelper;
use Mindbox\HttpClients\HttpClientFactory;
use Psr\Log\LoggerInterface;

/**
 * Основная точка входа в приложение, отвечающая за инициализацию всех необходимых зависимостей, согласно переданной
 * конфигурации приложения.
 * Class Mindbox
 *
 * @package Mindbox
 */
class Mindbox
{
    /**
     * @var array Массив значений конфигурации SDK по умолчанию.
     */
    private $defaultConfig = [
        'endpointId' => null,
        'secretKey'  => null,
        'domain'     => null,
        'timeout'    => null,
        'httpClient' => null,
    ];

    /**
     * @var AbstractMindboxClient Экземпляр клиента для отправки запросов к Mindbox v3 API.
     */
    private $client;

    /**
     * @var AbstractMindboxClient Экземпляр клиента для отправки запросов к Mindbox v2.1 API.
     */
    private $clientV2;

    /**
     * @var CustomerHelper Экземпляр хелпера для отправки запросов связанных с потребителем.
     */
    private $customer;

    /**
     * @var OrderHelper Экземпляр хелпера для отправки запросов связанных с процессингом заказов.
     */
    private $order;

    /**
     * @var ProductListHelper Экземпляр хелпера для отправки запросов связанных с изменением списка продуктов в корзине.
     */
    private $productList;

    /**
     * @var array Массив, содержащий пользовательскую конфигурацию SDK.
     */
    private $config;

    /**
     * Конструктор Mindbox.
     *
     * @param array           $config Пользовательская конфигурация.
     * @param LoggerInterface $logger Экземпляр логгера.
     *
     * @throws MindboxConfigException
     */
    public function __construct(array $config, LoggerInterface $logger)
    {
        $this->setConfig($config);

        $httpClient = $this->getHttpClientsFactory()->createHttpClient(
            $this->config['timeout'],
            $this->config['httpClient']
        );

        $this->client   = $this->getMindboxClientFactory()->createMindboxClient(
            'v3',
            $this->config['endpointId'],
            $this->config['secretKey'],
            $this->config['domain'],
            $httpClient,
            $logger
        );
        $this->clientV2 = $this->getMindboxClientFactory()->createMindboxClient(
            'v2.1',
            $this->config['endpointId'],
            $this->config['secretKey'],
            $this->config['domain'],
            $httpClient,
            $logger
        );
    }

    /**
     * Сеттер для $config.
     *
     * @param array $config Массив, содержащий конфигурацию.
     */
    protected function setConfig(array $config)
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    /**
     * Геттер для $defaultConfig.
     *
     * @return array
     */
    private function getDefaultConfig()
    {
        return $this->defaultConfig;
    }

    /**
     * Геттер для HttpClientFactory.
     *
     * @return HttpClientFactory
     */
    protected function getHttpClientsFactory()
    {
        return new HttpClientFactory();
    }

    /**
     * Геттер для MindboxClientFactory.
     *
     * @return MindboxClientFactory
     */
    protected function getMindboxClientFactory()
    {
        return new MindboxClientFactory();
    }

    /**
     * Геттер для $client.
     *
     * @return AbstractMindboxClient
     */
    public function getClientV3()
    {
        $this->client->setResponseType(MindboxResponse::class);
        return $this->client;
    }

    /**
     * Геттер для $clientV2.
     *
     * @return AbstractMindboxClient
     */
    public function getClientV2()
    {
        $this->clientV2->setResponseType(MindboxResponse::class);
        return $this->clientV2;
    }

    /**
     * Геттер для MindboxClient по версии API.
     *
     * @param string $apiVersion Версия API.
     *
     * @return AbstractMindboxClient
     * @throws MindboxException
     */
    public function getClient($apiVersion)
    {
        switch ($apiVersion) {
            case 'v3':
                return $this->getClientV3();
            case 'v2.1':
                return $this->getClientV2();
            default:
                throw new MindboxException('Unknown Api version. Set it to "v3" or "v2.1".');
        }
    }

    /**
     * Геттер для $customer.
     *
     * @return CustomerHelper
     */
    public function customer()
    {
        if (!isset($this->customer)) {
            $this->customer = new CustomerHelper($this->client);
        }

        return $this->customer;
    }

    /**
     * Геттер для $order.
     *
     * @return OrderHelper
     */
    public function order()
    {
        if (!isset($this->order)) {
            $this->order = new OrderHelper($this->client);
        }

        return $this->order;
    }

    /**
     * Геттер для $productList.
     *
     * @return ProductListHelper
     */
    public function productList()
    {
        if (!isset($this->productList)) {
            $this->productList = new ProductListHelper($this->client);
        }

        return $this->productList;
    }
}
