<?php

namespace Mindbox\Helpers;

use Mindbox\Clients\AbstractMindboxClient;
use Mindbox\DTO\V3\OperationDTO;
use Mindbox\MindboxResponse;

/**
 * Абстрактный класс, содержащий общие для всех хелперов методы.
 * Class AbstractMindboxHelper
 *
 * @package Mindbox\Helpers
 */
abstract class AbstractMindboxHelper
{
    /**
     * @var AbstractMindboxClient $client Mindbox клиент, с помощью которого будут осуществляться запросы.
     */
    protected $client;

    /**
     * Конструктор AbstractMindboxHelper.
     *
     * @param AbstractMindboxClient $client Экземпляр клиента Mindbox.
     */
    public function __construct(AbstractMindboxClient $client)
    {
        $this->client = $client;
    }

    /**
     * Инициализация объекта OperationDTO.
     *
     * @return OperationDTO
     */
    protected function createOperation()
    {
        return new OperationDTO();
    }

    /**
     * Возвращает экземпляр последнего ответа от Mindbox.
     *
     * @return MindboxResponse
     */
    public function getLastResponse()
    {
        return $this->client->getLastResponse();
    }

    /**
     * @return MindboxResponse
     * @throws \Mindbox\Exceptions\MindboxBadRequestException
     * @throws \Mindbox\Exceptions\MindboxConflictException
     * @throws \Mindbox\Exceptions\MindboxForbiddenException
     * @throws \Mindbox\Exceptions\MindboxNotFoundException
     * @throws \Mindbox\Exceptions\MindboxTooManyRequestsException
     * @throws \Mindbox\Exceptions\MindboxUnauthorizedException
     * @throws \Mindbox\Exceptions\MindboxUnavailableException
     * @throws \Mindbox\Exceptions\MindboxClientException
     */
    public function sendRequest()
    {
        return $this->client->sendRequest();
    }

    /**
     * @return \Mindbox\MindboxRequest
     */
    public function getRequest()
    {
        return $this->client->getRequest();
    }
}
