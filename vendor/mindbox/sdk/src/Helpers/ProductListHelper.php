<?php

namespace Mindbox\Helpers;

use Mindbox\DTO\V3\Requests\AddProductToListRequestDTO;
use Mindbox\DTO\V3\Requests\CustomerIdentityRequestDTO;
use Mindbox\DTO\V3\Requests\ProductListItemRequestCollection;
use Mindbox\DTO\V3\Requests\RemoveProductFromListRequestDTO;
use Mindbox\DTO\V3\Requests\SetProductCountInListRequestDTO;

/**
 * Хелпер, являющий обёрткой над универсальным запросом. Содержит методы для отправки запросов, связанных с изменением
 * списка продуктов в корзине.
 * Class CartHelper
 *
 * @package Mindbox\Helpers
 */
class ProductListHelper extends AbstractMindboxHelper
{
    /**
     * Выполняет вызов стандартной операции Website.AddToCart:
     *
     * @see https://developers.mindbox.ru/docs/prodlistactionxml
     *
     * @param AddProductToListRequestDTO $product       Объект, содержащий данные продукта для запроса.
     * @param string                     $operationName Название операции.
     * @param bool                       $addDeviceUUID Флаг, сообщающий о необходимости передать в запросе DeviceUUID.
     *
     * @return \Mindbox\Clients\AbstractMindboxClient
     */
    public function addToCart(AddProductToListRequestDTO $product, $operationName, $addDeviceUUID = true)
    {
        $operation = $this->createOperation();
        $operation->setAddProductToList($product);

        return $this->client->prepareRequest('POST', $operationName, $operation, '', [], false, $addDeviceUUID);
    }

    /**
     * Выполняет вызов стандартной операции Website.RemoveFromCart:
     *
     * @see https://developers.mindbox.ru/docs/prodlistactionxml
     *
     * @param RemoveProductFromListRequestDTO $product       Объект, содержащий данные продукта для запроса.
     * @param string                          $operationName Название операции.
     * @param bool                            $addDeviceUUID Флаг, сообщающий о необходимости передать в запросе
     *                                                       DeviceUUID.
     *
     * @return \Mindbox\Clients\AbstractMindboxClient
     */
    public function removeFromCart(
        RemoveProductFromListRequestDTO $product,
        $operationName,
        $addDeviceUUID = true
    ) {
        $operation = $this->createOperation();
        $operation->setRemoveProductFromList($product);

        return $this->client->prepareRequest('POST', $operationName, $operation, '', [], false, $addDeviceUUID);
    }

    /**
     * Выполняет вызов стандартной операции Website.SetProductCount:
     *
     * @see https://developers.mindbox.ru/docs/prodlistactionxml
     *
     * @param SetProductCountInListRequestDTO $product       Объект, содержащий данные продукта для запроса.
     * @param string                          $operationName Название операции.
     * @param bool                            $addDeviceUUID Флаг, сообщающий о необходимости передать в запросе
     *                                                       DeviceUUID.
     *
     * @return \Mindbox\Clients\AbstractMindboxClient
     */
    public function setProductCount(
        SetProductCountInListRequestDTO $product,
        $operationName,
        $addDeviceUUID = true
    ) {
        $operation = $this->createOperation();
        $operation->setSetProductCountInList($product);

        return $this->client->prepareRequest('POST', $operationName, $operation, '', [], false, $addDeviceUUID);
    }

    /**
     * Выполняет вызов стандартной операции Website.SetProductList:
     *
     * @see https://developers.mindbox.ru/docs/prodlistactionxml
     *
     * @param ProductListItemRequestCollection $products         Объект, содержащий данные списка продуктов для
     *                                                           запроса.
     * @param string                           $operationName    Название операции.
     * @param CustomerIdentityRequestDTO|null  $customerIdentity Объект, содержащий данные потребителя для запроса.
     * @param bool                             $addDeviceUUID    Флаг, сообщающий о необходимости передать в запросе
     *                                                           DeviceUUID.
     *
     * @return \Mindbox\Clients\AbstractMindboxClient
     */
    public function setProductList(
        ProductListItemRequestCollection $products,
        $operationName,
        CustomerIdentityRequestDTO $customerIdentity = null,
        $addDeviceUUID = true
    ) {
        $operation = $this->createOperation();
        $operation->setProductList($products);
        if (isset($customerIdentity)) {
            $operation->setCustomer($customerIdentity);
        }

        return $this->client->prepareRequest('POST', $operationName, $operation, '', [], false, $addDeviceUUID);
    }
}
