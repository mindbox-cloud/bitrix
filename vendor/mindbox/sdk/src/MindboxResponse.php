<?php

namespace Mindbox;

use Mindbox\DTO\ResultDTO;
use Mindbox\DTO\V3\Responses\ValidationMessageResponseCollection;

/**
 * Класс, содержащий данные ответа от Mindbox API и методы для получения этих данных в удобном пользователю виде.
 * Class MindboxResponse
 *
 * @package Mindbox
 */
class MindboxResponse
{
    /**
     * Статус Mindbox, при котором операция считается успешно выполненной.
     */
    const MINDBOX_SUCCESS_STATUS = 'Success';

    /**
     * @var int $httpCode HTTP код ответа.
     */
    protected $httpCode;

    /**
     * @var array $headers Заголовки ответа.
     */
    protected $headers;

    /**
     * @var array $body Тело ответа в виде массива.
     */
    protected $body;

    /**
     * @var string $rawBody "Сырое" тело ответа (xml|json).
     */
    protected $rawBody;

    /**
     * @var MindboxRequest $request Экземпляр связанного запроса.
     */
    protected $request;

    /**
     * Конструктор MindboxResponse.
     *
     * @param int            $httpCode HTTP код ответа.
     * @param array          $headers  Заголовки ответа.
     * @param array          $body     Тело ответа в виде массива.
     * @param string         $rawBody  "Сырое" тело ответа (xml|json).
     * @param MindboxRequest $request  Экземпляр связанного запроса.
     */
    public function __construct($httpCode, $headers, $body, $rawBody, $request)
    {
        $this->httpCode = $httpCode;
        $this->headers  = $headers;
        $this->body     = $body;
        $this->rawBody  = $rawBody;
        $this->request  = $request;
    }

    /**
     * Проверка статуса операции Mindbox.
     * Возвращает true, если в ответе есть поля errorId или errorMessage.
     * Возвращает false, если статус ответа совпадает с MINDBOX_SUCCESS_STATUS или статус отсутствует в ответе.
     * При отличии статуса от MINDBOX_SUCCESS_STATUS возвращает true.
     *
     * @return bool
     */
    public function isError()
    {
        $body = $this->getBody();

        if (empty($body)) {
            return true;
        }

        $errorId = $this->getResult()->getField('errorId');
        $errorMessage = $this->getResult()->getField('errorMessage');

        if (!is_null($errorId) || !is_null($errorMessage)) {
            return true;
        }

        $mindboxStatus = $this->getMindboxStatus();

        if (is_null($mindboxStatus)) {
            return false;
        }

        if ($mindboxStatus === static::MINDBOX_SUCCESS_STATUS) {
            return false;
        }

        return true;
    }

    /**
     * Геттер для $body.
     *
     * @return array
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Возвращает статус операции Mindbox.
     *
     * @return string|null
     */
    public function getMindboxStatus()
    {
        return $this->getResult()->getStatus();
    }

    /**
     * Возвращает тело ответа в виде экземпляра DTO.
     *
     * @return ResultDTO
     */
    public function getResult()
    {
        $body = $this->getBody();
        if (isset($body['result'])) {
            $body = $body['result'];
        }

        return new ResultDTO($body);
    }

    /**
     * Возвращает ошибки валидации в виде DTO, если такие присутствуют в ответе.
     *
     * @return ValidationMessageResponseCollection|null
     */
    public function getValidationErrors()
    {
        return $this->getResult()->getValidationMessages();
    }

    /**
     * Геттер для $httpCode.
     *
     * @return int
     */
    public function getHttpCode()
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
     * Геттер для $rawBody.
     *
     * @return string
     */
    public function getRawBody()
    {
        return $this->rawBody;
    }

    /**
     * Геттер для $request.
     *
     * @return MindboxRequest
     */
    public function getRequest()
    {
        return $this->request;
    }
}
