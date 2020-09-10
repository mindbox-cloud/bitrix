<?php

namespace Mindbox\Helpers;

use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\DTO\V3\Requests\MergeCustomersRequestDTO;
use Mindbox\DTO\V3\Requests\PageRequestDTO;
use Mindbox\DTO\V3\Requests\SmsConfirmationRequestDTO;
use Mindbox\Responses\MindboxBalanceResponse;
use Mindbox\Responses\MindboxBonusPointsResponse;
use Mindbox\Responses\MindboxCustomerIdentityResponse;
use Mindbox\Responses\MindboxCustomerProcessingStatusResponse;
use Mindbox\Responses\MindboxCustomerResponse;
use Mindbox\Responses\MindboxMergeCustomersResponse;
use Mindbox\Responses\MindboxSmsConfirmationResponse;

/**
 * Хелпер, являющий обёрткой над универсальным запросом. Содержит методы для отправки запросов, связанных с
 * действиями над потребителем.
 * Class CustomerHelper
 *
 * @package Mindbox\Helpers
 */
class CustomerHelper extends AbstractMindboxHelper
{
    /**
     * Выполняет вызов стандартной операции Website.AuthorizeCustomer:
     *
     * @see https://developers.mindbox.ru/v3.0/docs/json
     *
     * @param CustomerRequestDTO $customer      Объект, содержащий данные потребителя для запроса.
     * @param string             $operationName Название операции.
     * @param bool               $addDeviceUUID Флаг, сообщающий о необходимости передать в запросе DeviceUUID.
     *
     * @return \Mindbox\Clients\AbstractMindboxClient
     */
    public function authorize(CustomerRequestDTO $customer, $operationName, $addDeviceUUID = true)
    {
        $operation = $this->createOperation();
        $operation->setCustomer($customer);

        return $this->client->prepareRequest('POST', $operationName, $operation, '', [], false, $addDeviceUUID);
    }

    /**
     * Выполняет вызов стандартной операции Website.CheckCustomerByMobilePhone:
     *
     * @see https://developers.mindbox.ru/docs/получение-данных-потребителя
     *
     * @param CustomerRequestDTO $customer      Объект, содержащий данные потребителя для запроса.
     * @param string             $operationName Название операции.
     * @param bool               $addDeviceUUID Флаг, сообщающий о необходимости передать в запросе DeviceUUID.
     *
     * @return \Mindbox\Clients\AbstractMindboxClient
     */
    public function checkByPhone(CustomerRequestDTO $customer, $operationName, $addDeviceUUID = true)
    {
        $operation = $this->createOperation();
        $operation->setCustomer($customer);

        $this->client->setResponseType(MindboxCustomerResponse::class);

        return $this->client->prepareRequest('POST', $operationName, $operation, '', [], true, $addDeviceUUID);
    }

    /**
     * Выполняет вызов стандартной операции Website.CheckCustomerByEmail:
     *
     * @see https://developers.mindbox.ru/docs/получение-данных-потребителя
     *
     * @param CustomerRequestDTO $customer      Объект, содержащий данные потребителя для запроса.
     * @param string             $operationName Название операции.
     * @param bool               $addDeviceUUID Флаг, сообщающий о необходимости передать в запросе DeviceUUID.
     *
     * @return \Mindbox\Clients\AbstractMindboxClient
     */
    public function checkByMail(CustomerRequestDTO $customer, $operationName, $addDeviceUUID = true)
    {
        $operation = $this->createOperation();
        $operation->setCustomer($customer);

        $this->client->setResponseType(MindboxCustomerResponse::class);

        return $this->client->prepareRequest('POST', $operationName, $operation, '', [], true, $addDeviceUUID);
    }

    /**
     * Выполняет вызов стандартной операции Website.RegisterCustomer:
     *
     * @see https://developers.mindbox.ru/v3.0/docs/json
     *
     * @param CustomerRequestDTO $customer      Объект, содержащий данные потребителя для запроса.
     * @param string             $operationName Название операции.
     * @param bool               $addDeviceUUID Флаг, сообщающий о необходимости передать в запросе DeviceUUID.
     *
     * @return \Mindbox\Clients\AbstractMindboxClient
     */
    public function register(CustomerRequestDTO $customer, $operationName, $addDeviceUUID = true)
    {
        $operation = $this->createOperation();
        $operation->setCustomer($customer);

        $this->client->setResponseType(MindboxCustomerIdentityResponse::class);

        return $this->client->prepareRequest('POST', $operationName, $operation, '', [], true, $addDeviceUUID);
    }

    /**
     * Выполняет вызов стандартной операции Website.EditCustomer:
     *
     * @see https://developers.mindbox.ru/docs/userredxml
     *
     * @param CustomerRequestDTO $customer      Объект, содержащий данные потребителя для запроса.
     * @param string             $operationName Название операции.
     * @param bool               $addDeviceUUID Флаг, сообщающий о необходимости передать в запросе DeviceUUID.
     *
     * @return \Mindbox\Clients\AbstractMindboxClient
     */
    public function edit(CustomerRequestDTO $customer, $operationName, $addDeviceUUID = true)
    {
        $operation = $this->createOperation();
        $operation->setCustomer($customer);

        $this->client->setResponseType(MindboxCustomerIdentityResponse::class);

        return $this->client->prepareRequest('POST', $operationName, $operation, '', [], true, $addDeviceUUID);
    }

    /**
     * Выполняет вызов стандартной операции Website.FillCustomerProfile:
     *
     * @see https://developers.mindbox.ru/docs/userredxml
     *
     * @param CustomerRequestDTO $customer      Объект, содержащий данные потребителя для запроса.
     * @param string             $operationName Название операции.
     * @param bool               $addDeviceUUID Флаг, сообщающий о необходимости передать в запросе DeviceUUID.
     *
     * @return \Mindbox\Clients\AbstractMindboxClient
     */
    public function fill(CustomerRequestDTO $customer, $operationName, $addDeviceUUID = true)
    {
        $operation = $this->createOperation();
        $operation->setCustomer($customer);

        $this->client->setResponseType(MindboxCustomerIdentityResponse::class);

        return $this->client->prepareRequest('POST', $operationName, $operation, '', [], true, $addDeviceUUID);
    }

    /**
     * Выполняет вызов стандартной операции Website.GetCustomerDataByDiscountCard:
     *
     * @see https://developers.mindbox.ru/docs/получение-данных-потребителя
     *
     * @param CustomerRequestDTO $customer      Объект, содержащий данные потребителя для запроса.
     * @param string             $operationName Название операции.
     * @param bool               $addDeviceUUID Флаг, сообщающий о необходимости передать в запросе DeviceUUID.
     *
     * @return \Mindbox\Clients\AbstractMindboxClient
     */
    public function getDataByDiscountCard(
        CustomerRequestDTO $customer,
        $operationName,
        $addDeviceUUID = true
    ) {
        $operation = $this->createOperation();
        $operation->setCustomer($customer);

        $this->client->setResponseType(MindboxCustomerResponse::class);

        return $this->client->prepareRequest('POST', $operationName, $operation, '', [], true, $addDeviceUUID);
    }

    /**
     * Выполняет вызов стандартной операции Website.MergeCustomers:
     *
     * @see https://developers.mindbox.ru/docs/объединение-потребителей-по-запросу
     *
     * @param MergeCustomersRequestDTO $customersToMerge Объект, содержащий данные объединяемых потребителей для
     *                                                   запроса.
     * @param string                   $operationName    Название операции.
     * @param bool                     $addDeviceUUID    Флаг, сообщающий о необходимости передать в запросе
     *                                                   DeviceUUID.
     *
     * @return \Mindbox\Clients\AbstractMindboxClient
     */
    public function merge(
        MergeCustomersRequestDTO $customersToMerge,
        $operationName,
        $addDeviceUUID = true
    ) {
        $this->client->setResponseType(MindboxMergeCustomersResponse::class);

        return $this->client->prepareRequest('POST', $operationName, $customersToMerge, '', [], true, $addDeviceUUID);
    }

    /**
     * Выполняет вызов стандартной операции Website.CheckCustomerIsInLoyalityProgram:
     *
     * @see https://developers.mindbox.ru/docs/получение-сегментов-потребителя
     *
     * @param CustomerRequestDTO $customer      Объект, содержащий данные потребителя для запроса.
     * @param string             $operationName Название операции.
     * @param bool               $addDeviceUUID Флаг, сообщающий о необходимости передать в запросе DeviceUUID.
     *
     * @return \Mindbox\Clients\AbstractMindboxClient
     */
    public function checkActive(CustomerRequestDTO $customer, $operationName, $addDeviceUUID = true)
    {
        $operation = $this->createOperation();
        $operation->setCustomer($customer);

        $this->client->setResponseType(MindboxCustomerResponse::class);

        return $this->client->prepareRequest('POST', $operationName, $operation, '', [], true, $addDeviceUUID);
    }

    /**
     * Выполняет вызов стандартной операции Website.GetCustomerBonusPointsHistory:
     *
     * @see https://developers.mindbox.ru/docs/получение-истории-изменений-баланса-потребителя
     *
     * @param CustomerRequestDTO $customer      Объект, содержащий данные потребителя для запроса.
     * @param PageRequestDTO     $page          Объект, содержащий данные пагинации для запроса.
     * @param string             $operationName Название операции.
     * @param bool               $addDeviceUUID Флаг, сообщающий о необходимости передать в запросе DeviceUUID.
     *
     * @return \Mindbox\Clients\AbstractMindboxClient
     */
    public function getBonusPointsHistory(
        CustomerRequestDTO $customer,
        PageRequestDTO $page,
        $operationName,
        $addDeviceUUID = true
    ) {
        $operation = $this->createOperation();
        $operation->setCustomer($customer);
        $operation->setPage($page);

        $this->client->setResponseType(MindboxBonusPointsResponse::class);

        return $this->client->prepareRequest('POST', $operationName, $operation, '', [], true, $addDeviceUUID);
    }

    /**
     * Выполняет вызов стандартной операции Website.SendMobilePhoneAuthorizationCode:
     *
     * @see https://developers.mindbox.ru/docs/send-confirmation-code
     *
     * @param CustomerRequestDTO $customer      Объект, содержащий данные потребителя для запроса.
     * @param string             $operationName Название операции.
     * @param bool               $addDeviceUUID Флаг, сообщающий о необходимости передать в запросе DeviceUUID.
     * @param bool               $isSync        Флаг, сообщающий о необходимости выполнять запрос синхронно/асинхронно.
     *
     * @return \Mindbox\Clients\AbstractMindboxClient
     */
    public function sendAuthorizationCode(
        CustomerRequestDTO $customer,
        $operationName,
        $addDeviceUUID = true,
        $isSync = true
    ) {
        $operation = $this->createOperation();
        $operation->setCustomer($customer);

        $this->client->setResponseType(MindboxCustomerProcessingStatusResponse::class);

        return $this->client->prepareRequest('POST', $operationName, $operation, '', [], $isSync, $addDeviceUUID);
    }

    /**
     * Выполняет вызов стандартной операции Website.CheckMobilePhoneAuthorizationCode:
     *
     * @see https://developers.mindbox.ru/docs/по-секретному-коду
     *
     * @param CustomerRequestDTO $customer             Объект, содержащий данные потребителя для запроса.
     * @param string             $authentificationCode Код аутентификации.
     * @param string             $operationName        Название операции.
     * @param bool               $addDeviceUUID        Флаг, сообщающий о необходимости передать в запросе DeviceUUID.
     *
     * @return \Mindbox\Clients\AbstractMindboxClient
     */
    public function checkAuthorizationCode(
        CustomerRequestDTO $customer,
        $authentificationCode,
        $operationName,
        $addDeviceUUID = true
    ) {
        $operation = $this->createOperation();
        $operation->setCustomer($customer);
        $operation->setAuthentificationCode($authentificationCode);

        $this->client->setResponseType(MindboxCustomerIdentityResponse::class);

        return $this->client->prepareRequest('POST', $operationName, $operation, '', [], true, $addDeviceUUID);
    }

    /**
     * Выполняет вызов стандартной операции Website.ResendMobilePhoneConfirmationCode:
     *
     * @see https://developers.mindbox.ru/docs/send-confirmation-code
     *
     * @param CustomerRequestDTO $customer      Объект, содержащий данные потребителя для запроса.
     * @param string             $operationName Название операции.
     * @param bool               $addDeviceUUID Флаг, сообщающий о необходимости передать в запросе DeviceUUID.
     * @param bool               $isSync        Флаг, сообщающий о необходимости выполнять запрос синхронно/асинхронно.
     *
     * @return \Mindbox\Clients\AbstractMindboxClient
     */
    public function resendConfirmationCode(
        CustomerRequestDTO $customer,
        $operationName,
        $addDeviceUUID = true,
        $isSync = true
    ) {
        $operation = $this->createOperation();
        $operation->setCustomer($customer);

        $this->client->setResponseType(MindboxCustomerProcessingStatusResponse::class);

        return $this->client->prepareRequest('POST', $operationName, $operation, '', [], $isSync, $addDeviceUUID);
    }

    /**
     * Выполняет вызов стандартной операции Website.ConfirmMobilePhone:
     *
     * @see https://developers.mindbox.ru/docs/подтверждение-мобильного-телефона
     *
     * @param CustomerRequestDTO        $customer        Объект, содержащий данные потребителя для запроса.
     * @param SmsConfirmationRequestDTO $smsConfirmation Объект, содержащий код подтверждения.
     * @param string                    $operationName   Название операции.
     * @param bool                      $addDeviceUUID   Флаг, сообщающий о необходимости передать в запросе
     *                                                   DeviceUUID.
     * @param bool                      $isSync          Флаг, сообщающий о необходимости выполнять запрос
     *                                                   синхронно/асинхронно.
     *
     * @return \Mindbox\Clients\AbstractMindboxClient
     */
    public function confirmMobile(
        CustomerRequestDTO $customer,
        SmsConfirmationRequestDTO $smsConfirmation,
        $operationName,
        $addDeviceUUID = true,
        $isSync = true
    ) {
        $operation = $this->createOperation();
        $operation->setCustomer($customer);
        $operation->setSmsConfirmation($smsConfirmation);

        $this->client->setResponseType(MindboxSmsConfirmationResponse::class);

        return $this->client->prepareRequest('POST', $operationName, $operation, '', [], $isSync, $addDeviceUUID);
    }

    /**
     * Выполняет вызов стандартной операции Website.SubscribeCustomer:
     *
     * @see https://developers.mindbox.ru/v3.0/docs/json
     *
     * @param CustomerRequestDTO $customer      Объект, содержащий данные потребителя для запроса.
     * @param string             $operationName Название операции.
     * @param bool               $addDeviceUUID Флаг, сообщающий о необходимости передать в запросе DeviceUUID.
     * @param bool               $isSync        Флаг, сообщающий о необходимости выполнять запрос синхронно/асинхронно.
     *
     * @return \Mindbox\Clients\AbstractMindboxClient
     */
    public function subscribe(
        CustomerRequestDTO $customer,
        $operationName,
        $addDeviceUUID = false,
        $isSync = true
    ) {
        $operation = $this->createOperation();
        $operation->setCustomer($customer);

        $this->client->setResponseType(MindboxCustomerResponse::class);

        return $this->client->prepareRequest('POST', $operationName, $operation, '', [], $isSync, $addDeviceUUID);
    }

    /**
     * Выполняет вызов стандартной операции Website.AutoConfirmMobilePhone:
     *
     * @see https://developers.mindbox.ru/v3.0/docs/подтверждение-мобильного-телефона-на-стороне-клиента
     *
     * @param CustomerRequestDTO $customer      Объект, содержащий данные потребителя для запроса.
     * @param string             $operationName Название операции.
     * @param bool               $addDeviceUUID Флаг, сообщающий о необходимости передать в запросе DeviceUUID.
     *
     * @return \Mindbox\Clients\AbstractMindboxClient
     */
    public function autoConfirmMobile(CustomerRequestDTO $customer, $operationName, $addDeviceUUID = true)
    {
        $operation = $this->createOperation();
        $operation->setCustomer($customer);

        return $this->client->prepareRequest('POST', $operationName, $operation, '', [], false, $addDeviceUUID);
    }

    /**
     * Выполняет вызов стандартной операции Website.GetCustomerBalance:
     *
     * @see https://developers.mindbox.ru/v3.0/docs/получение-баланса-потребителя
     *
     * @param CustomerRequestDTO $customer      Объект, содержащий данные потребителя для запроса.
     * @param string             $operationName Название операции.
     * @param bool               $addDeviceUUID Флаг, сообщающий о необходимости передать в запросе DeviceUUID.
     *
     * @return \Mindbox\Clients\AbstractMindboxClient
     */
    public function getBalance(CustomerRequestDTO $customer, $operationName, $addDeviceUUID = true)
    {
        $operation = $this->createOperation();
        $operation->setCustomer($customer);

        $this->client->setResponseType(MindboxBalanceResponse::class);

        return $this->client->prepareRequest('POST', $operationName, $operation, '', [], true, $addDeviceUUID);
    }
}
