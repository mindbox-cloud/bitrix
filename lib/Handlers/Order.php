<?php

namespace Mindbox\Handlers;

use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use DateTime;
use DateTimeZone;
use Mindbox\Core;
use Mindbox\Helper;
use Mindbox\Options;
use Mindbox\Exceptions;
use Mindbox\Discount\DeliveryDiscountEntity;
use Mindbox\DTO\DTO;
use Mindbox\DTO\V3\Requests\DiscountRequestDTO;
use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\DTO\V3\Requests\LineRequestDTO;
use Mindbox\DTO\V3\Requests\OrderCreateRequestDTO;
use Mindbox\DTO\V3\Requests\OrderUpdateRequestDTO;
use Mindbox\Components\CalculateProductData;
use Mindbox\QueueTable;
use Mindbox\Transaction;

class Order
{
    use Core;
    /**
     * @param \Bitrix\Sale\Order $order
     * @param $basket
     * @return Main\EventResult|false
     * @throws Main\ArgumentException
     * @throws Main\ObjectPropertyException
     * @throws Main\SystemException
     */
    public static function finalAction($order, $basket)
    {
        if (Helper::isDeleteOrderAdminAction()) {
            return new Main\EventResult(Main\EventResult::SUCCESS);
        }

        if (Helper::isStandardMode()) {
            return new Main\EventResult(Main\EventResult::SUCCESS);
        }

        global $USER;

        if (!$USER || is_string($USER)) {
            return new Main\EventResult(Main\EventResult::SUCCESS);
        }

        $mindbox = static::mindbox();

        if (!$mindbox) {
            return new Main\EventResult(Main\EventResult::SUCCESS);
        }

        if (!$order->isNew() && !Helper::isMindboxOrder($order->getId())) {
            return new Main\EventResult(Main\EventResult::SUCCESS);
        }

        if (Helper::isInternalOrderUser($order->getUserId())) {
            return new Main\EventResult(Main\EventResult::SUCCESS);
        }

        if (Helper::isAdminSection()
                && isset($_REQUEST['action'])
                && $_REQUEST['action'] === 'refreshOrderData'
        ) {
            return new Main\EventResult(Main\EventResult::SUCCESS);
        }

        if ($_REQUEST['soa-action'] === 'saveOrderAjax' &&
                $_REQUEST['save'] === 'Y'
        ) {
            return new Main\EventResult(Main\EventResult::SUCCESS);
        }

        // @todo тут пытаемся сделать хак с сессией

        if (Helper::isAdminSection()) {
            $orderPersonType = $order->getPersonTypeId();
            $propertyCollection = $order->getPropertyCollection();

            $setOrderPromoCode = Helper::getOrderPropertyValueByCode(
                    $propertyCollection,
                    'MINDBOX_PROMO_CODE',
                    $orderPersonType
            );
            $setBonus =  Helper::getOrderPropertyValueByCode(
                    $propertyCollection,
                    'MINDBOX_BONUS',
                    $orderPersonType
            );

            if (!empty($setOrderPromoCode)) {
                $_SESSION['PROMO_CODE'] = $setOrderPromoCode;
            }

            if (!empty($setBonus)) {
                $_SESSION['PAY_BONUSES'] = $setBonus;
            }
            // @info сохраним корзину, чтобы получить корректный lineId
            $basket->save();
            //$basket->refresh();
        }

        $mindboxOrderStatus = 'CheckedOut';

        if (is_object($order)) {
            $orderStatusId = $order->getField('STATUS_ID');
            $getMindboxStatus = self::getMindboxStatusByShopStatus($orderStatusId);

            if (!empty($getMindboxStatus)) {
                $mindboxOrderStatus = $getMindboxStatus;
            }
        }

        $basketItems = $basket->getBasketItems();
        $lines = [];
        $bitrixBasket = [];
        $preorder = new \Mindbox\DTO\V3\Requests\PreorderRequestDTO();

        foreach ($basketItems as $basketItem) {
            if (!$basketItem->getId()) {
                continue;
            }

            if ($basketItem->getQuantity() < 1) {
                continue;
            }

            $requestedPromotions = Helper::getRequestedPromotions($basketItem, $order);
            $bitrixBasket[$basketItem->getId()] = $basketItem;
            $catalogPrice = Helper::getBasePrice($basketItem);

            $arLine = [
                    'basePricePerItem' => $catalogPrice,
                    'quantity'         => $basketItem->getQuantity(),
                    'lineId'           => $basketItem->getId(),
                    'product'          => [
                            'ids' => [
                                    Options::getModuleOption('EXTERNAL_SYSTEM') => Helper::getElementCode($basketItem->getProductId())
                            ]
                    ],
                    'status'           => [
                            'ids' => [
                                    'externalId' => $mindboxOrderStatus
                            ]
                    ]
            ];

            if (!empty($requestedPromotions)) {
                $arLine['requestedPromotions'] = $requestedPromotions;
            }

            $lines[] = $arLine;
        }

        if (empty($lines)) {
            return false;
        }

        $arCoupons = [];

        if ($_SESSION['PROMO_CODE'] && !empty($_SESSION['PROMO_CODE'])) {
            if (strpos($_SESSION['PROMO_CODE'], ',') !== false) {
                $applyCouponsList = explode(',', $_SESSION['PROMO_CODE']);

                if (is_array($applyCouponsList) && !empty($applyCouponsList)) {
                    foreach ($applyCouponsList as $couponItem) {
                        $arCoupons[]['ids']['code'] = trim($couponItem);
                    }
                }
            } else {
                $arCoupons[]['ids']['code'] = $_SESSION['PROMO_CODE'];
            }
        }

        $orderId = (Helper::isAdminSection() && !empty($order->getId())) ? $order->getId() : '';

        $payments = [];
        $paymentCollection = $order->getPaymentCollection();
        foreach ($paymentCollection as $payment) {
            $payments[] = [
                    'type'   => $payment->getPaymentSystemId()
            ];
        }

        $arOrder = [
                'ids'   => [
                        Options::getModuleOption('TRANSACTION_ID') => $orderId,
                ],
                'lines' => $lines
        ];

        if (!empty($payments)) {
            $arOrder['payments'] = $payments;
        }

        if (!empty($arCoupons)) {
            $arOrder['coupons'] = $arCoupons;
        }

        $bonuses = $_SESSION['PAY_BONUSES'] ?: 0;
        if ($bonuses && $USER->IsAuthorized()) {
            $bonusPoints = [
                    'amount' => $bonuses
            ];
            $arOrder['bonusPoints'] = [
                    $bonusPoints
            ];
        }

        $deliveryId = 0;
        /** @var \Bitrix\Sale\Shipment $shipment */
        foreach ($order->getShipmentCollection() as $shipment) {
            if ($shipment->isSystem()) {
                continue;
            }
            $deliveryPrice = $shipment->getField('BASE_PRICE_DELIVERY');
            $deliveryId = $shipment->getDeliveryId();
            break;
        }

        if ($deliveryId > 0) {
            if ($deliveryPrice) {
                $arOrder['deliveryCost']  = $deliveryPrice;
            }

            $arDelivery = \Bitrix\Sale\Delivery\Services\Table::getById($deliveryId)->fetch();

            if (is_array($arDelivery) && !empty($arDelivery['NAME'])) {
                $arOrder['customFields'] = [
                        'deliveryType'  =>  $arDelivery['NAME']
                ];
            }
        }

        $preorder->setField('order', $arOrder);

        $customer = new CustomerRequestDTO();
        if ($USER->IsAuthorized()) {
            // @info тут берем пользователя из заказа, если прецессинг админки
            $orderUserId = (Helper::isAdminSection() && !empty($order->getUserId())) ? $order->getUserId() :  $USER->GetID();
            $mindboxId = Helper::getMindboxId($orderUserId);

            if (!$mindboxId) {
                return new Main\EventResult(Main\EventResult::SUCCESS);
            }

            $customer->setId('mindboxId', $mindboxId);
            $preorder->setCustomer($customer);
        }

        try {
            if ($USER->IsAuthorized()) {
                $preorderInfo = $mindbox->order()->calculateAuthorizedCart(
                        $preorder,
                        Options::getOperationName('calculateAuthorizedCart' . (Helper::isAdminSection()? 'Admin':''))
                )->sendRequest()->getResult()->getField('order');
            } else {
                $preorderInfo = $mindbox->order()->calculateUnauthorizedCart(
                        $preorder,
                        Options::getOperationName('calculateUnauthorizedCart')
                )->sendRequest()->getResult()->getField('order');
            }

            if (!$preorderInfo) {
                return new Main\EventResult(Main\EventResult::SUCCESS);
            }

            if (Helper::isAdminSection()) {
                // @info функционал для процессинга в админке. Передаем OnBeforeOrderSaved ошибку применения купона
                $couponsInfo = reset($preorderInfo->getField('couponsInfo'));
                $setCouponError = false;

                if ($couponsInfo['coupon']['status'] == 'NotFound') {
                    $setCouponError = Loc::getMessage('MB_CART_PROMOCODE_NOT_FOUND');
                } elseif ($couponsInfo['coupon']['status'] == 'CanNotBeUsedForCurrentOrder') {
                    $setCouponError = Loc::getMessage('MB_CART_PROMOCODE_ERR');
                } elseif ($couponsInfo['coupon']['status'] == 'Used') {
                    $setCouponError = Loc::getMessage('MB_CART_PROMO_USED');
                }

                if (!empty($setCouponError)) {
                    $_SESSION['SET_COUPON_ERROR'] = $setCouponError;
                }
            }

            $_SESSION['TOTAL_PRICE'] = $preorderInfo->getField('totalPrice');

            $totalBonusPointInfo = $preorderInfo->getField('totalBonusPointsInfo');

            if (!Helper::isAdminSection()) {
                if (!empty($totalBonusPointInfo) && $totalBonusPointInfo['availableAmountForCurrentOrder'] < $_SESSION['PAY_BONUSES']) {
                    $_SESSION['PAY_BONUSES'] = $totalBonusPointInfo['availableAmountForCurrentOrder'];
                }
            }

            /** функционал применения скидки на доставку Mindbox  */
            $mindboxDeliveryPrice = $preorderInfo->getField('deliveryCost');

            if ($USER->IsAuthorized() && (int)$order->getField('USER_ID') > 0) {
                $fUserId = \Bitrix\Sale\Fuser::getIdByUserId((int)$order->getField('USER_ID'));
            } elseif (!$USER->IsAuthorized()) {
                $fUserId = \Bitrix\Sale\Fuser::getId();
            }

            $mindboxDiscountParams = [];
            $mindboxDiscountParams['UF_FUSER_ID'] = $fUserId ? $fUserId : null;
            $mindboxDiscountParams['UF_DELIVERY_ID'] = $deliveryId ? $deliveryId : null;
            $mindboxDiscountParams['UF_ORDER_ID'] = $order->getId() > 0 ? $order->getId() : null;

            $deliveryDiscountEntity = new DeliveryDiscountEntity();

            if (isset($mindboxDeliveryPrice)
                    && ($findRow = $deliveryDiscountEntity->getRowByFilter($mindboxDiscountParams))
            ) {
                $deliveryDiscountEntity->update((int)$findRow['ID'], [
                        'UF_DISCOUNTED_PRICE' => (float)$mindboxDeliveryPrice
                ]);
            } elseif (isset($mindboxDeliveryPrice)) {
                $deliveryDiscountEntity->add(array_merge([
                        'UF_DISCOUNTED_PRICE' => (float)$mindboxDeliveryPrice
                ], $mindboxDiscountParams));
            } else {
                $deliveryDiscountEntity->deleteByFilter($mindboxDiscountParams);
            }

            $discounts = $preorderInfo->getDiscountsInfo();

            foreach ($discounts as $discount) {
                if ($discount->getType() === 'balance') {
                    $balance = $discount->getField('balance');
                    if ($balance['balanceType']['ids']['systemName'] === 'Main') {
                        $_SESSION['ORDER_AVAILABLE_BONUSES'] = $discount->getField('availableAmountForCurrentOrder');
                    }
                }

                if ($discount->getType() === 'promoCode') {
                    $_SESSION['PROMO_CODE_AMOUNT'] = $discount['availableAmountForCurrentOrder'];
                }
            }

            $lines = $preorderInfo->getLines();

            $mindboxBasket = [];
            $mindboxAdditional = [];
            $context = $basket->getContext();

            foreach ($lines as $line) {
                $lineId = $line->getField('lineId');
                $bitrixProduct = $bitrixBasket[$lineId];

                if (isset($mindboxBasket[$lineId])) {
                    $mindboxAdditional[] = [
                            'PRODUCT_ID'             => $bitrixProduct->getProductId(),
                            'PRICE'                  => floatval($line->getDiscountedPrice()) / floatval($line->getQuantity()),
                            'CUSTOM_PRICE'           => 'Y',
                            'QUANTITY'               => $line->getQuantity(),
                            'CURRENCY'               => $context['CURRENCY'],
                            'NAME'                   => $bitrixProduct->getField('NAME'),
                            'LID'                    => SITE_ID,
                            'DETAIL_PAGE_URL'        => $bitrixProduct->getField('DETAIL_PAGE_URL'),
                            'CATALOG_XML_ID'         => $bitrixProduct->getField('CATALOG_XML_ID'),
                            'PRODUCT_XML_ID'         => $bitrixProduct->getField('PRODUCT_XML_ID'),
                            'PRODUCT_PROVIDER_CLASS' => $bitrixProduct->getProviderName(),
                            'CAN_BUY'                => 'Y'
                    ];

                    foreach ($mindboxAdditional as $product) {
                        $item = $basket->createItem("catalog", $product["PRODUCT_ID"]);
                        unset($product["PRODUCT_ID"]);
                        $item->setFields($product);
                    }
                } else {
                    $mindboxPrice = floatval($line->getDiscountedPrice()) / floatval($line->getQuantity());
                    $mindboxBasket[$lineId] = $bitrixProduct;
                    Helper::processHlbBasketRule($lineId, $mindboxPrice);
                }
            }
        } catch (Exceptions\MindboxClientException $e) {
            return new Main\EventResult(Main\EventResult::SUCCESS);
        }

        return new Main\EventResult(Main\EventResult::SUCCESS);
    }

    /**
     * @param Main\Event $event
     * @return Main\EventResult
     * @throws Main\ArgumentException
     * @throws Main\InvalidOperationException
     * @throws Main\ObjectPropertyException
     * @throws Main\SystemException
     */
    public static function onSaleOrderBeforeSaved(\Bitrix\Main\Event $event)
    {
        global $USER;

        $order = $event->getParameter("ENTITY");

        $standardMode = \COption::GetOptionString('mindbox.marketing', 'MODE') === 'standard';
        $mindbox = static::mindbox();

        if (Helper::isDeleteOrderAdminAction()) {
            return new Main\EventResult(Main\EventResult::SUCCESS);
        }

        if ($standardMode) {
            return new Main\EventResult(Main\EventResult::SUCCESS);
        }

        if (!$USER || is_string($USER)) {
            return new Main\EventResult(Main\EventResult::SUCCESS);
        }

        if (!$mindbox) {
            return new Main\EventResult(Main\EventResult::SUCCESS);
        }

        if (Helper::isInternalOrderUser($order->getUserId())) {
            return new Main\EventResult(Main\EventResult::SUCCESS);
        }

        if (Helper::isAdminSection()) {
            // @todo: временно убрал ограничение оплаченного заказа
            /*if ($order->isPaid() && strtotime($order->getField('DATE_PAYED')) < time()) {
                Transaction::getInstance()->clear();

                return new \Bitrix\Main\EventResult(
                    \Bitrix\Main\EventResult::ERROR,
                    new \Bitrix\Sale\ResultError(Loc::getMessage("MB_ORDER_CANNOT_BE_CHANGED"), 'SALE_EVENT_WRONG_ORDER'),
                    'sale'
                );
            }*/

            if (!empty($_SESSION['SET_COUPON_ERROR'])) {
                $setPromoCodeError = $_SESSION['SET_COUPON_ERROR'];
                unset($_SESSION['SET_COUPON_ERROR']);

                Transaction::getInstance()->clear();

                unset($_SESSION['PROMO_CODE_AMOUNT']);
                unset($_SESSION['PROMO_CODE']);
                unset($_SESSION['PAY_BONUSES']);
                unset($_SESSION['TOTAL_PRICE']);

                return new \Bitrix\Main\EventResult(
                        \Bitrix\Main\EventResult::ERROR,
                        new \Bitrix\Sale\ResultError($setPromoCodeError, 'SALE_EVENT_WRONG_ORDER'),
                        'sale'
                );
            }
        }

        $isNewOrder = empty($order->getId());

        if (!$isNewOrder && !Helper::isAdminSection()) {
            return new Main\EventResult(Main\EventResult::SUCCESS);
        }

        if (!$isNewOrder) {
            $existTransaction = Transaction::existOpenTransaction($order->getId());

            if (!empty($existTransaction)) {
                try {
                    $orderDTO = new OrderCreateRequestDTO();
                    $orderDTO->setField('order', [
                            'transaction' => [
                                    'ids' => [
                                            'externalId' => $existTransaction['transaction']
                                    ]
                            ]
                    ]);

                    $mindbox->order()->rollbackOrderTransaction(
                            $orderDTO,
                            Options::getOperationName('rollbackOrderTransactionAdmin')
                    )->sendRequest();

                    $request = $mindbox->order()->getRequest();
                    // закрываем транзакцию
                    Transaction::closeTransaction($existTransaction['id']);
                } catch (\Exception $exception) {
                }
            }
        }

        if (!$isNewOrder && !Helper::isMindboxOrder($order->getId())) {
            return new Main\EventResult(Main\EventResult::SUCCESS);
        }

        $basket = $order->getBasket();
        global $USER;

        if (Helper::isAdminSection()) {
            self::finalAction($order, $basket);
        }

        $delivery = $order->getDeliverySystemId();
        $delivery = current($delivery);

        $payments = [];
        $paymentCollection = $order->getPaymentCollection();
        foreach ($paymentCollection as $payment) {
            $payments[] = [
                    'type'   => $payment->getPaymentSystemId(),
                    'amount' => $payment->getSum()
            ];
        }

        $mindboxOrderStatus = 'CheckedOut';

        if (is_object($order)) {
            $orderStatusId = $order->getField('STATUS_ID');
            $getMindboxStatus = self::getMindboxStatusByShopStatus($orderStatusId);

            if (!empty($getMindboxStatus)) {
                $mindboxOrderStatus = $getMindboxStatus;
            }
        }

        $rsUser = \CUser::GetByID($order->getUserId());
        $arUser = $rsUser->Fetch();

        $orderDTO = new \Mindbox\DTO\V3\Requests\OrderCreateRequestDTO();
        $basketItems = $basket->getBasketItems();
        $lines = [];
        $i = 1;

        foreach ($basketItems as $basketItem) {
            $propertyCollection = $order->getPropertyCollection();
            $ar = $propertyCollection->getArray();
            foreach ($ar['properties'] as $arProperty) {
                $arProperty['CODE'] = Helper::sanitizeNamesForMindbox($arProperty['CODE']);
                $arOrderProperty[$arProperty['CODE']] = current($arProperty['VALUE']);
            }
            $productBasePrice = Helper::getBasePrice($basketItem);
            $requestedPromotions = Helper::getRequestedPromotions($basketItem, $order);

            $lineId = $basketItem->getId();

            $arLine = [
                    'lineNumber'       => $i++,
                    'basePricePerItem' => $productBasePrice,
                    'quantity'         => $basketItem->getQuantity(),
                    'lineId'           => $lineId,
                    'product'          => [
                            'ids' => [
                                    Options::getModuleOption('EXTERNAL_SYSTEM') => Helper::getElementCode($basketItem->getProductId())
                            ]
                    ],
                    'status'           => [
                            'ids' => [
                                    'externalId' => $mindboxOrderStatus
                            ]
                    ]
            ];

            if (!empty($requestedPromotions)) {
                $arLine['requestedPromotions'] = $requestedPromotions;
            }

            $lines[] = $arLine;
        }

        if (empty($lines)) {
            Transaction::getInstance()->clear();
            return new Main\EventResult(Main\EventResult::SUCCESS);
        }

        $arCoupons = [];

        if ($_SESSION['PROMO_CODE'] && !empty($_SESSION['PROMO_CODE'])) {
            if (strpos($_SESSION['PROMO_CODE'], ',') !== false) {
                $applyCouponsList = explode(',', $_SESSION['PROMO_CODE']);

                if (is_array($applyCouponsList) && !empty($applyCouponsList)) {
                    foreach ($applyCouponsList as $couponItem) {
                        $arCoupons[]['ids']['code'] = trim($couponItem);
                    }
                }
            } else {
                $arCoupons[]['ids']['code'] = $_SESSION['PROMO_CODE'];
            }
        }

        $shopOrderId = $order->getId();

        $arOrder = [
                'ids'          => [
                        Options::getModuleOption('TRANSACTION_ID') => ($shopOrderId > 0) ? $shopOrderId : '',
                ],
                'lines'        => $lines,
                'transaction'  => [
                        'ids' => [
                                'externalId' => Helper::getTransactionId($shopOrderId)
                        ]
                ],
                'deliveryCost' => $order->getDeliveryPrice(),
                'totalPrice'   => $_SESSION['TOTAL_PRICE']
        ];

        if ($mindboxOrderStatus !== 'Cancelled') {
            $arOrder['payments'] = $payments;
        }

        if (!empty($arCoupons)) {
            $arOrder['coupons'] = $arCoupons;
        }

        $bonuses = $_SESSION['PAY_BONUSES'] ?: 0;

        if ($bonuses && is_object($USER) && $USER->IsAuthorized()) {
            $bonusPoints = [
                    'amount' => $bonuses
            ];
            $arOrder['bonusPoints'] = [
                    $bonusPoints
            ];
        }

        $customer = new CustomerRequestDTO();

        if (is_object($USER) && $USER->IsAuthorized()) {
            $orderUserId = (Helper::isAdminSection()) ? $order->getUserId() : $USER->GetID();
            $mindboxId = Helper::getMindboxId($orderUserId);
        }

        $customFields = [];
        $propertyCollection = $order->getPropertyCollection();
        $ar = $propertyCollection->getArray();

        foreach ($ar['properties'] as $arProperty) {
            $arProperty['CODE'] = Helper::sanitizeNamesForMindbox($arProperty['CODE']);

            if (count($arProperty['VALUE']) === 1) {
                $value = current($arProperty['VALUE']);
            } else {
                $value = $arProperty['VALUE'];
            }
            $arOrderProperty[$arProperty['CODE']] = array_pop($arProperty['VALUE']);

            if (!empty($customName = Helper::getMatchByCode($arProperty['CODE']))) {
                $customFields[$customName] = $value;
            }
        }

        $customFields['deliveryType'] = $delivery;
        $arOrder['customFields'] = $customFields;

        if (!empty($arOrderProperty['EMAIL'])) {
            $customer->setEmail($arOrderProperty['EMAIL']);
            $arOrder['email'] = $arOrderProperty['EMAIL'];
        }

        if (!empty($arOrderProperty['PHONE'])) {
            $customer->setMobilePhone($arOrderProperty['PHONE']);
            $arOrder['mobilePhone'] = $arOrderProperty['PHONE'];
        }

        $orderDTO->setField('order', $arOrder);

        if (!(Helper::isUnAuthorizedOrder($arUser) || (is_object($USER) && !$USER->IsAuthorized())) || Helper::isAdminSection()) {
            $customer->setId('mindboxId', $mindboxId);
        }

        if (is_object($USER) && $USER->IsAuthorized() && Helper::isUnAuthorizedOrder($arUser) && !Helper::isAdminSection()) {
            $customer->setId(Options::getModuleOption('WEBSITE_ID'), $USER->GetID());
        }

        $orderDTO->setCustomer($customer);

        try {
            if ((Helper::isUnAuthorizedOrder($arUser) && !Helper::isAdminSection()) || (is_object($USER) && !$USER->IsAuthorized())) {
                $createOrderResult = $mindbox->order()->beginUnauthorizedOrderTransaction(
                        $orderDTO,
                        Options::getOperationName('beginUnauthorizedOrderTransaction')
                )->sendRequest();
            } else {
                $createOrderResult = $mindbox->order()->beginAuthorizedOrderTransaction(
                        $orderDTO,
                        Options::getOperationName('beginAuthorizedOrderTransaction' . (Helper::isAdminSection()? 'Admin':''))
                )->sendRequest();
            }

            if ($createOrderResult->getValidationErrors()) {
                $strValidationError = '';
                $validationErrors = $createOrderResult->getValidationErrors();
                $arValidationError = $validationErrors->getFieldsAsArray();

                foreach ($arValidationError as $validationError) {
                    $strValidationError .= $validationError['message'];
                }

                try {
                    $orderDTO = new OrderCreateRequestDTO();
                    $orderDTO->setField('order', [
                            'transaction' => [
                                    'ids' => [
                                            'externalId' => Helper::getTransactionId($shopOrderId)
                                    ]
                            ]
                    ]);
                    $createOrderResult = $mindbox->order()->rollbackOrderTransaction(
                            $orderDTO,
                            Options::getOperationName('rollbackOrderTransaction' . (Helper::isAdminSection()? 'Admin':''))
                    )->sendRequest();

                    unset($_SESSION['TOTAL_PRICE']);

                    return new \Bitrix\Main\EventResult(
                            \Bitrix\Main\EventResult::ERROR,
                            new \Bitrix\Sale\ResultError($strValidationError, 'SALE_EVENT_WRONG_ORDER'),
                            'sale'
                    );
                } catch (Exceptions\MindboxClientErrorException $e) {
                    Transaction::getInstance()->clear();
                    return new Main\EventResult(Main\EventResult::ERROR);
                } catch (Exceptions\MindboxUnavailableException $e) {
                    Transaction::getInstance()->clear();
                    return new Main\EventResult(Main\EventResult::SUCCESS);
                } catch (Exceptions\MindboxClientException $e) {
                    Transaction::getInstance()->clear();
                    $request = $mindbox->order()->getRequest();
                    if ($request) {
                        QueueTable::push($request);
                    }
                }
            } elseif ($createOrderResult->getResult()->getOrder()->getField('processingStatus') === 'PriceHasBeenChanged') {
                if (Helper::isAdminSection()) {
                    $errorMessage = $createOrderResult->getResult()->getOrder()->getField('statusDescription');
                } else {
                    $errorMessage = Loc::getMessage("MB_ORDER_PROCESSING_STATUS_CHANGED");
                }

                Transaction::getInstance()->clear();

                unset($_SESSION['PROMO_CODE_AMOUNT']);
                unset($_SESSION['PROMO_CODE']);
                unset($_SESSION['PAY_BONUSES']);
                unset($_SESSION['TOTAL_PRICE']);


                return new \Bitrix\Main\EventResult(
                        \Bitrix\Main\EventResult::ERROR,
                        new \Bitrix\Sale\ResultError($errorMessage, 'SALE_EVENT_WRONG_ORDER'),
                        'sale'
                );
            } else {
                $createOrderResult = $createOrderResult->getResult()->getField('order');
                $_SESSION['MINDBOX_ORDER'] = $createOrderResult ? $createOrderResult->getId('mindboxId') : false;

                return new Main\EventResult(Main\EventResult::SUCCESS);
            }
            $createOrderResult = $createOrderResult->getResult()->getField('order');
            $_SESSION['MINDBOX_ORDER'] = $createOrderResult ? $createOrderResult->getId('mindboxId') : false;
        } catch (Exceptions\MindboxClientErrorException $e) {
            try {
                $orderDTO = new OrderCreateRequestDTO();
                $orderDTO->setField('order', [
                        'transaction' => [
                                'ids' => [
                                        'externalId' => Helper::getTransactionId($shopOrderId)
                                ]
                        ]
                ]);

                $mindbox->order()->rollbackOrderTransaction(
                        $orderDTO,
                        Options::getOperationName('rollbackOrderTransaction' . (Helper::isAdminSection()? 'Admin':''))
                )->sendRequest();
            } catch (Exceptions\MindboxClientException $e) {
                $request = $mindbox->order()->getRequest();

                if ($request) {
                    QueueTable::push($request);
                }
            }

            unset($_SESSION['TOTAL_PRICE']);

            Transaction::getInstance()->clear();

            return new \Bitrix\Main\EventResult(
                    \Bitrix\Main\EventResult::ERROR,
                    new \Bitrix\Sale\ResultError($e->getMessage(), 'SALE_EVENT_WRONG_ORDER'),
                    'sale'
            );
        } catch (Exceptions\MindboxUnavailableException $e) {
            Transaction::getInstance()->clear();
            return new Main\EventResult(Main\EventResult::SUCCESS);
        } catch (Exceptions\MindboxClientException $e) {
            Transaction::getInstance()->clear();
            return new Main\EventResult(Main\EventResult::SUCCESS);
        }

        return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);
    }

    /**
     * @param Main\Event $event
     * @return Main\EventResult|void
     * @throws Main\ArgumentException
     * @throws Main\InvalidOperationException
     */
    public static function onSaleOrderSavedLoyalty(\Bitrix\Main\Event $event)
    {
        $order = $event->getParameter('ENTITY');
        $isNew = $event->getParameter('IS_NEW');

        $mindbox = static::mindbox();

        if (Helper::isDeleteOrderAdminAction()) {
            return new Main\EventResult(Main\EventResult::SUCCESS);
        }

        if (!$isNew && !Helper::isAdminSection()) {
            return new Main\EventResult(Main\EventResult::SUCCESS);
        }

        if (!$mindbox) {
            return new Main\EventResult(Main\EventResult::SUCCESS);
        }

        if (Helper::isInternalOrderUser($order->getUserId())) {
            return new Main\EventResult(Main\EventResult::SUCCESS);
        }

        if (!$isNew && !Helper::isMindboxOrder($order->getId())) {
            return new Main\EventResult(Main\EventResult::SUCCESS);
        }

        // data update in HL for shipping discount
        if ($isNew) {
            $deliveryId = 0;
            /** @var \Bitrix\Sale\Shipment $shipment */
            foreach ($order->getShipmentCollection() as $shipment) {
                if ($shipment->isSystem()) {
                    continue;
                }

                $deliveryId = $shipment->getDeliveryId();
                break;
            }

            $fUserId = \Bitrix\Sale\Fuser::getIdByUserId((int)$order->getField('USER_ID'));

            $mindboxDiscountParams = [];
            $mindboxDiscountParams['UF_DELIVERY_ID'] = $deliveryId;
            $mindboxDiscountParams['UF_ORDER_ID'] = null;

            $deliveryDiscountEntity = new DeliveryDiscountEntity();

            if ($findRow = $deliveryDiscountEntity->getRowByFilter($mindboxDiscountParams)) {
                $deliveryDiscountEntity->update((int)$findRow['ID'], [
                        'UF_ORDER_ID' => $order->getId()
                ]);
            }

            $deliveryDiscountEntity->deleteByFilter(array_merge($mindboxDiscountParams, ['UF_FUSER_ID' => $fUserId]));
        }

        /** @var \Bitrix\Sale\Basket $basket */
        $basket = $order->getBasket();
        global $USER;

        $mindboxOrderStatus = 'CheckedOut';

        if (is_object($order)) {
            $orderStatusId = $order->getField('STATUS_ID');
            $getMindboxStatus = self::getMindboxStatusByShopStatus($orderStatusId);

            if (!empty($getMindboxStatus)) {
                $mindboxOrderStatus = $getMindboxStatus;
            }
        }

        $offlineOrderDTO = new \Mindbox\DTO\V3\Requests\OrderCreateRequestDTO();
        $basketItems = $basket->getBasketItems();
        $lines = [];

        $i = 1;
        foreach ($basketItems as $basketItem) {
            $productBasePrice = $basketItem->getBasePrice();
            $requestedPromotions = Helper::getRequestedPromotions($basketItem, $order);

            $propertyCollection = $order->getPropertyCollection();
            $ar = $propertyCollection->getArray();

            foreach ($ar['properties'] as $arProperty) {
                $arProperty['CODE'] = Helper::sanitizeNamesForMindbox($arProperty['CODE']);
                $arOrderProperty[$arProperty['CODE']] = current($arProperty['VALUE']);
            }

            $arLine = [
                    'lineNumber'       => $i++,
                    'basePricePerItem' => $productBasePrice,
                    'quantity'         => $basketItem->getQuantity(),
                    'lineId'           => $basketItem->getId(),
                    'product'          => [
                            'ids' => [
                                    Options::getModuleOption('EXTERNAL_SYSTEM') => Helper::getElementCode($basketItem->getProductId())
                            ]
                    ],
                    'status'           => [
                            'ids' => [
                                    'externalId' => $mindboxOrderStatus
                            ]
                    ]
            ];

            if (!empty($requestedPromotions)) {
                $arLine['requestedPromotions'] = $requestedPromotions;
            }

            $lines[] = $arLine;
        }

        if (empty($lines)) {
            if (Helper::isAdminSection()) {
                try {
                    $orderDTO = new OrderCreateRequestDTO();
                    $orderDTO->setField('order', [
                            'transaction' => [
                                    'ids' => [
                                            'externalId' => Helper::getTransactionId($order->getId())
                                    ]
                            ]
                    ]);

                    $mindbox->order()->rollbackOrderTransaction(
                            $orderDTO,
                            Options::getOperationName('rollbackOrderTransaction' . (Helper::isAdminSection()? 'Admin':''))
                    )->sendRequest();
                } catch (Exceptions\MindboxClientException $e) {
                    $request = $mindbox->order()->getRequest();
                    if ($request) {
                        QueueTable::push($request);
                    }
                }
            }

            Transaction::getInstance()->clear();

            return new Main\EventResult(Main\EventResult::SUCCESS);
        }

        $arCoupons = [];

        if ($_SESSION['PROMO_CODE'] && !empty($_SESSION['PROMO_CODE'])) {
            if (strpos($_SESSION['PROMO_CODE'], ',') !== false) {
                $applyCouponsList = explode(',', $_SESSION['PROMO_CODE']);

                if (is_array($applyCouponsList) && !empty($applyCouponsList)) {
                    foreach ($applyCouponsList as $couponItem) {
                        $arCoupons[]['ids']['code'] = trim($couponItem);
                    }
                }
            } else {
                $arCoupons[]['ids']['code'] = $_SESSION['PROMO_CODE'];
            }
        }

        $arOrder = [
                'ids'          => [
                        Options::getModuleOption('TRANSACTION_ID') => $order->getId(),
                    //'mindboxId' =>  $_SESSION['MINDBOX_ORDER']
                ],
                'lines'        => $lines,
                'deliveryCost' => $order->getDeliveryPrice()
        ];

        if (!empty($arCoupons)) {
            $arOrder['coupons'] = $arCoupons;
        }

        $bonuses = $_SESSION['PAY_BONUSES'] ?: 0;
        if ($bonuses && is_object($USER) && $USER->IsAuthorized()) {
            $bonusPoints = [
                    'amount' => $bonuses
            ];
            $arOrder['bonusPoints'] = [
                    $bonusPoints
            ];
        }

        $customer = new CustomerRequestDTO();

        $propertyCollection = $order->getPropertyCollection();
        $ar = $propertyCollection->getArray();

        foreach ($ar['properties'] as $arProperty) {
            $arProperty['CODE'] = Helper::sanitizeNamesForMindbox($arProperty['CODE']);
            $arOrderProperty[$arProperty['CODE']] = current($arProperty['VALUE']);
        }

        if (!empty($arOrderProperty['EMAIL'])) {
            $customer->setEmail($arOrderProperty['EMAIL']);
            $arOrder['email'] = $arOrderProperty['EMAIL'];
        }

        if (!empty($arOrderProperty['PHONE'])) {
            $customer->setMobilePhone($arOrderProperty['PHONE']);
            $arOrder['mobilePhone'] = $arOrderProperty['PHONE'];
        }

        $offlineOrderDTO->setField('order', $arOrder);
        $offlineOrderDTO->setCustomer($customer);

        try {
            $orderDTO = new OrderCreateRequestDTO();
            $orderDTO->setField('order', [
                    'ids'         => [
                            Options::getModuleOption('TRANSACTION_ID') => $order->getId(),
                            'mindboxId' => $_SESSION['MINDBOX_ORDER']
                    ],
                    'transaction' => [
                            'ids' => [
                                    'externalId' => Helper::getTransactionId($order->getId())
                            ]
                    ]
            ]);

            $createOrderResult = $mindbox->order()->commitOrderTransaction(
                    $orderDTO,
                    Options::getOperationName('commitOrderTransaction' . (Helper::isAdminSection()? 'Admin':''))
            )->sendRequest();

            $setPropertiesList = [];

            if (isset($_SESSION['PROMO_CODE']) && !empty($_SESSION['PROMO_CODE'])) {
                $setPropertiesList['MINDBOX_PROMO_CODE'] = $_SESSION['PROMO_CODE'];
            }

            if (isset($_SESSION['PAY_BONUSES']) && !empty($_SESSION['PAY_BONUSES'])) {
                $setPropertiesList['MINDBOX_BONUS'] = $_SESSION['PAY_BONUSES'];
            }

            if (!empty($setPropertiesList)) {
                $orderPropertyCollection = $order->getPropertyCollection();
                $orderPersonType = $order->getPersonTypeId();

                foreach ($setPropertiesList as $propCode => $propValue) {
                    $orderPropertyData = Helper::getOrderPropertyByCode($propCode, $orderPersonType);

                    if (!empty($orderPropertyData)) {
                        $propertyItemObj = $orderPropertyCollection->getItemByOrderPropertyId($orderPropertyData['ID']);

                        if (!empty($propertyItemObj) && is_object($propertyItemObj)) {
                            $propertyItemObj->setValue($propValue);
                            $propertyItemObj->save();
                        }
                    }
                }
            }

            Transaction::getInstance()->close();

            unset($_SESSION['PROMO_CODE_AMOUNT']);
            unset($_SESSION['PROMO_CODE']);
            unset($_SESSION['PAY_BONUSES']);
            unset($_SESSION['TOTAL_PRICE']);

            if (isset($_SESSION['MINDBOX_TASK_ID']) && $_SESSION['MINDBOX_TASK_ID'] > 0) {
                QueueTable::update($_SESSION['MINDBOX_TASK_ID'], [
                        'STATUS_EXEC' => 'Y',
                        'DATE_EXEC'   => \Bitrix\Main\Type\DateTime::createFromTimestamp(time())
                ]);
                unset($_SESSION['MINDBOX_TASK_ID']);
            }
        } catch (Exceptions\MindboxClientErrorException $e) {
            unset($_SESSION['PAY_BONUSES']);
            unset($_SESSION['TOTAL_PRICE']);
            Transaction::getInstance()->clear();
            return new Main\EventResult(Main\EventResult::ERROR);
        } catch (Exceptions\MindboxUnavailableException $e) {
            try {
                $mindbox->order()->saveOfflineOrder(
                        $offlineOrderDTO,
                        Options::getOperationName('saveOfflineOrder')
                )->sendRequest();
            } catch (Exceptions\MindboxUnavailableException $e) {
                $lastResponse = $mindbox->order()->getLastResponse();

                if ($lastResponse) {
                    $request = $lastResponse->getRequest();
                    QueueTable::push($request);
                }

                Transaction::getInstance()->clear();

                return new Main\EventResult(Main\EventResult::SUCCESS);
            } catch (Exceptions\MindboxClientException $e) {
                $request = $mindbox->order()->getRequest();
                if ($request) {
                    QueueTable::push($request);
                }
            }
            unset($_SESSION['PAY_BONUSES']);
            unset($_SESSION['TOTAL_PRICE']);
            Transaction::getInstance()->clear();

            return new Main\EventResult(Main\EventResult::SUCCESS);
        } catch (Exceptions\MindboxClientException $e) {
            try {
                $mindbox->order()->saveOfflineOrder(
                        $offlineOrderDTO,
                        Options::getOperationName('saveOfflineOrder')
                )->sendRequest();
            } catch (Exceptions\MindboxUnavailableException $e) {
                $lastResponse = $mindbox->order()->getLastResponse();

                if ($lastResponse) {
                    $request = $lastResponse->getRequest();
                    QueueTable::push($request);
                }

                Transaction::getInstance()->clear();

                return new Main\EventResult(Main\EventResult::SUCCESS);
            } catch (Exceptions\MindboxClientException $e) {
                $request = $mindbox->order()->getRequest();
                if ($request) {
                    QueueTable::push($request);
                }
            }
            unset($_SESSION['PAY_BONUSES']);
            unset($_SESSION['TOTAL_PRICE']);
            Transaction::getInstance()->clear();

            return new Main\EventResult(Main\EventResult::SUCCESS);
        }
    }

    /**
     * @param Main\Event $event
     * @return Main\EventResult
     * @throws Main\ArgumentNullException
     */
    public static function onSaleOrderSavedStandart(\Bitrix\Main\Event $event)
    {
        $order = $event->getParameter('ENTITY');
        $isNew = $event->getParameter('IS_NEW');

        $mindbox = static::mindbox();

        if (Helper::isDeleteOrderAdminAction()) {
            return new Main\EventResult(Main\EventResult::SUCCESS);
        }

        if (!$isNew && !Helper::isAdminSection()) {
            return new Main\EventResult(Main\EventResult::SUCCESS);
        }

        if (!$mindbox) {
            return new Main\EventResult(Main\EventResult::SUCCESS);
        }

        if (Helper::isInternalOrderUser($order->getUserId())) {
            return new Main\EventResult(Main\EventResult::SUCCESS);
        }

        $payments = [];
        $paymentCollection = $order->getPaymentCollection();
        foreach ($paymentCollection as $payment) {
            $payments[] = [
                    'type'   => $payment->getPaymentSystemId(),
                    'amount' => $payment->getSum()
            ];
        }

        /** @var \Bitrix\Sale\Basket $basket */
        $basket = $order->getBasket();
        $delivery = $order->getDeliverySystemId();
        $delivery = current($delivery);
        global $USER;

        if (!$USER || is_string($USER)) {
            return new Main\EventResult(Main\EventResult::SUCCESS);
        }

        $rsUser = \CUser::GetByID($order->getUserId());
        $arUser = $rsUser->Fetch();


        $orderDTO = new OrderCreateRequestDTO();
        $basketItems = $basket->getBasketItems();
        $lines = [];

        foreach ($basketItems as $basketItem) {
            $catalogPrice = \CPrice::GetBasePrice($basketItem->getProductId());
            $catalogPrice = $catalogPrice['PRICE'] ?: 0;
            $lines[] = [
                    'basePricePerItem' => $catalogPrice,
                    'quantity'         => $basketItem->getQuantity(),
                    'lineId'           => $basketItem->getId(),
                    'product'          => [
                            'ids' => [
                                    Options::getModuleOption('EXTERNAL_SYSTEM') => Helper::getElementCode($basketItem->getProductId())
                            ]
                    ]
            ];
        }

        if (empty($lines)) {
            return new Main\EventResult(Main\EventResult::SUCCESS);
        }

        $customer = new CustomerRequestDTO();

        $customFields = [];
        $propertyCollection = $order->getPropertyCollection();
        $ar = $propertyCollection->getArray();

        foreach ($ar['properties'] as $arProperty) {
            $arProperty['CODE'] = Helper::sanitizeNamesForMindbox($arProperty['CODE']);
            if (count($arProperty['VALUE']) === 1) {
                $value = current($arProperty['VALUE']);
            } else {
                $value = $arProperty['VALUE'];
            }
            $arOrderProperty[$arProperty['CODE']] = current($arProperty['VALUE']);
            if (!empty($customName = Helper::getMatchByCode($arProperty['CODE']))) {
                $customFields[$customName] = $value;
            }
        }

        $customFields['deliveryType'] = $delivery;

        $orderDTO->setField('order', [
                'ids'          => [
                        Options::getModuleOption('TRANSACTION_ID') => $order->getId()
                ],
                'lines'        => $lines,
                'email'        => $arOrderProperty['EMAIL'],
                'mobilePhone'  => $arOrderProperty['PHONE'],
                'payments'     => $payments,
                'customFields' => $customFields
        ]);

        $customer->setEmail($arOrderProperty['EMAIL']);
        $customer->setLastName($arOrderProperty['FIO']);
        $customer->setFirstName($arOrderProperty['NAME']);
        $customer->setMobilePhone($arOrderProperty['PHONE']);
        $customer->setId(Options::getModuleOption('WEBSITE_ID'), $order->getUserId());

        $isSubscribed = true;
        if ($arOrderProperty['UF_MB_IS_SUBSCRIBED'] === 'N') {
            $isSubscribed = false;
        }

        $subscriptions = [
                'subscription' => [
                        'brand'        => Options::getModuleOption('BRAND'),
                        'isSubscribed' => $isSubscribed
                ]
        ];

        $customer->setSubscriptions($subscriptions);
        $orderDTO->setCustomer($customer);

        $discounts = [];
        $bonuses = $_SESSION['PAY_BONUSES'];

        if (!empty($bonuses)) {
            $discounts[] = new DiscountRequestDTO([
                    'type'        => 'balance',
                    'amount'      => $bonuses,
                    'balanceType' => [
                            'ids' => ['systemName' => 'Main']
                    ]
            ]);
        }

        if ($_SESSION['PROMO_CODE'] && !empty($_SESSION['PROMO_CODE'])) {
            if (strpos($_SESSION['PROMO_CODE'], ',') !== false) {
                $applyCouponsList = explode(',', $_SESSION['PROMO_CODE']);

                if (is_array($applyCouponsList) && !empty($applyCouponsList)) {
                    foreach ($applyCouponsList as $couponItem) {
                        $discounts[] = new DiscountRequestDTO([
                                'type'   => 'promoCode',
                                'id'     => trim($couponItem),
                                'amount' => $_SESSION['PROMO_CODE_AMOUNT'] ?: 0
                        ]);
                    }
                }
            } else {
                $discounts[] = new DiscountRequestDTO([
                        'type'   => 'promoCode',
                        'id'     => $_SESSION['PROMO_CODE'],
                        'amount' => $_SESSION['PROMO_CODE_AMOUNT'] ?: 0
                ]);
            }
        }

        if (!empty($discounts)) {
            $orderDTO->setDiscounts($discounts);
        }

        try {
            if (Helper::isUnAuthorizedOrder($arUser) || (is_object($USER) && !$USER->IsAuthorized())) {
                $createOrderResult = $mindbox->order()->CreateUnauthorizedOrder(
                        $orderDTO,
                        Options::getOperationName('createUnauthorizedOrder')
                )->sendRequest();
            } else {
                $createOrderResult = $mindbox->order()->CreateAuthorizedOrder(
                        $orderDTO,
                        Options::getOperationName('createAuthorizedOrder')
                )->sendRequest();
            }

            if ($createOrderResult->getValidationErrors()) {
                return new Main\EventResult(Main\EventResult::ERROR);
            }

            $createOrderResult = $createOrderResult->getResult()->getField('order');
            $_SESSION['MINDBOX_ORDER'] = $createOrderResult ? $createOrderResult->getId('mindbox') : false;
        } catch (Exceptions\MindboxClientErrorException $e) {
            return new Main\EventResult(Main\EventResult::ERROR);
        } catch (Exceptions\MindboxUnavailableException $e) {
            $orderDTO = new OrderUpdateRequestDTO();

            $_SESSION['PAY_BONUSES'] = 0;
            unset($_SESSION['PROMO_CODE']);
            unset($_SESSION['PROMO_CODE_AMOUNT']);
            unset($_SESSION['TOTAL_PRICE']);

            if ($_SESSION['MINDBOX_ORDER']) {
                $orderDTO->setId('mindbox', $_SESSION['MINDBOX_ORDER']);
            }

            $now = new DateTime();
            $now = $now->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            $orderDTO->setUpdatedDateTimeUtc($now);

            $customer = new CustomerRequestDTO();
            $mindboxId = Helper::getMindboxId($order->getUserId());

            if ($mindboxId) {
                $customer->setId('mindbox', $mindboxId);
            }

            if (is_object($USER)) {
                $customer->setEmail($USER->GetEmail());
            }

            $phone = $_SESSION['ANONYM']['PHONE'];
            unset($_SESSION['ANONYM']['PHONE']);

            if ($phone) {
                $customer->setMobilePhone(Helper::formatPhone($phone));
            }

            if (is_object($USER) && $USER->IsAuthorized()) {
                $customer->setField('isAuthorized', true);
            } else {
                $customer->setField('isAuthorized', false);
            }

            $orderDTO->setCustomer($customer);
            $orderDTO->setPointOfContact(Options::getModuleOption('POINT_OF_CONTACT'));

            $lines = [];
            foreach ($basketItems as $basketItem) {
                $basketItem->setField('CUSTOM_PRICE', 'N');
                $basketItem->save();
                $line = new LineRequestDTO();
                $line->setField('lineId', $basketItem->getId());
                $line->setQuantity($basketItem->getQuantity());
                $catalogPrice = \CPrice::GetBasePrice($basketItem->getProductId())['PRICE'];
                $line->setProduct([
                        'productId'        => Helper::getElementCode($basketItem->getProductId()),
                        'basePricePerItem' => $catalogPrice
                ]);

                $lines[] = $line;
            }

            $orderDTO->setLines($lines);
            $orderDTO->setField('totalPrice', $basket->getPrice());

            $_SESSION['OFFLINE_ORDER'] = [
                    'DTO' => $orderDTO,
            ];

            return new Main\EventResult(Main\EventResult::SUCCESS);
        } catch (Exceptions\MindboxClientException $e) {
            $request = $mindbox->order()->getRequest();

            if ($request) {
                QueueTable::push($request);
            }
        }

        return new Main\EventResult(Main\EventResult::SUCCESS);
    }

    /**
     * @param $order
     * @param $has
     * @param $basket
     * @return Main\EventResult|false
     * @throws Main\ArgumentException
     * @throws Main\ObjectPropertyException
     * @throws Main\SystemException
     */
    public static function onBeforeSaleOrderFinalAction($order, $has, $basket)
    {
        if (Helper::isDeleteOrderAdminAction()) {
            return new Main\EventResult(Main\EventResult::SUCCESS);
        }

        return self::finalAction($order, $basket);
    }

    /**
     * @param Main\Event $event
     * @return void
     */
    public static function onSalePropertyValueSetField(\Bitrix\Main\Event $event)
    {
        if (Helper::isStandardMode() && Helper::isAdminSection()) {
            $orderMatchList = Helper::getOrderFieldsMatch();

            $getEntity = $event->getParameter('ENTITY');
            $value = $event->getParameter('VALUE');
            $order = $getEntity->getCollection()->getOrder();
            $propertyData = $getEntity->getProperty();

            if (!empty($order) && $order instanceof \Bitrix\Sale\Order) {
                $additionFields = [
                        'customFields' => [$orderMatchList[$propertyData['CODE']] => $value],
                ];

                self::updateMindboxOrderItems($order, $additionFields);
            }
        }
    }


    /**
     * @param Main\Event $event
     * @return void
     */
    public static function onBeforeSaleShipmentSetField(\Bitrix\Main\Event $event)
    {
        if (Helper::isStandardMode() && Helper::isAdminSection()) {
            $orderEntity = $event->getParameter('ENTITY');
            $orderId = $orderEntity->getField('ORDER_ID');
            $statusValue = $event->getParameter('VALUE');

            if ($event->getParameter('NAME') === 'STATUS_ID') {
                self::updateMindboxOrderStatus($orderId, $statusValue);
            }
        }
    }

    /**
     * @param $orderId
     * @param $newOrderStatus
     * @return void
     */
    public static function onSaleStatusOrder($orderId, $newOrderStatus)
    {
        self::updateMindboxOrderStatus($orderId, $newOrderStatus);
    }

    /**
     * @param $orderId
     * @param $cancelFlag
     * @param $cancelDesc
     * @return void
     */
    public static function onSaleCancelOrder($orderId, $cancelFlag, $cancelDesc)
    {
        if (Helper::isStandardMode()) {
            $statusCodeAlias = ($cancelFlag === 'Y') ? 'CANCEL' : 'CANCEL_ABORT';
            self::updateMindboxOrderStatus($orderId, $statusCodeAlias);
        }
    }

    /**
     * @param $orderId
     * @param $statusCode
     * @return false|void
     */
    public static function updateMindboxOrderStatus($orderId, $statusCode)
    {
        $mindbox = static::mindbox();

        if ($mindbox && Helper::isMindboxOrder($orderId)) {
            $mindboxStatusCode = self::getMindboxStatusByShopStatus($statusCode);

            if ($mindboxStatusCode !== false) {
                $request = $mindbox->getClientV3()->prepareRequest(
                        'POST',
                        Options::getOperationName('updateOrderStatus'),
                        new DTO([
                                'orderLinesStatus' => $mindboxStatusCode,
                                'order' => [
                                        'ids' => [
                                                'websiteId' => $orderId
                                        ]
                                ]
                        ]),
                        '',
                        [],
                        true,
                        false
                );

                try {
                    $response = $request->sendRequest();
                } catch (Exceptions\MindboxClientException $e) {
                    return false;
                }
            }
        }
    }

    /**
     * @param \Bitrix\Sale\Order $order
     * @param $additionalFields
     * @return false|void
     */
    public static function updateMindboxOrderItems(\Bitrix\Sale\Order $order, $additionalFields = [])
    {
        $orderId = $order->getId();
        $orderStatus = $order->getField('STATUS_ID');
        $orderUserId = $order->getField('USER_ID');

        if (!$order->isNew() && !Helper::isMindboxOrder($order->getId())) {
            return;
        }

        $mindbox = Options::getConfig();

        if (!$mindbox) {
            return;
        }

        $mindboxStatusCode = self::getMindboxStatusByShopStatus($orderStatus);

        if (empty($mindboxStatusCode)) {
            return false;
        }

        $orderBasket = $order->getBasket();

        if ($orderBasket) {
            $basketItems = $orderBasket->getBasketItems();
            $lines = [];

            foreach ($basketItems as $basketItem) {
                $lines[] = [
                        'lineId' => $basketItem->getId(),
                        'quantity' => $basketItem->getQuantity(),
                        'basePricePerItem' => $basketItem->getPrice(),
                        'status' => $mindboxStatusCode,
                        'product' => [
                                'ids' => [
                                        Options::getModuleOption('EXTERNAL_SYSTEM') => Helper::getElementCode($basketItem->getProductId())
                                ]
                        ],
                ];
            }
        }

        $requestFields = [
                'ids' => [
                        Options::getModuleOption('TRANSACTION_ID') => $orderId
                ],
                'lines' => $lines
        ];

        if (!empty($additionalFields) && is_array($additionalFields)) {
            $requestFields = $requestFields + $additionalFields;
        }

        $requestData = [
                'customer' => [
                        'ids' => [
                                Options::getModuleOption('WEBSITE_ID') => $orderUserId
                        ],
                ],
                'order' => $requestFields
        ];

        $request = $mindbox->getClientV3()->prepareRequest(
                'POST',
                Options::getOperationName('updateOrderItems'),
                new DTO($requestData)
        );

        try {
            $response = $request->sendRequest();
        } catch (Exceptions\MindboxClientException $e) {
            return false;
        }
    }


    public static function getMindboxStatusByShopStatus($shopStatus)
    {
        $return = false;

        if (!empty($shopStatus)) {
            $statusOptionsJson = Option::get('mindbox.marketing', 'ORDER_STATUS_FIELDS_MATCH', '{}');
            $statusOptionsData = json_decode($statusOptionsJson, true);

            if (!empty($statusOptionsData) && is_array($statusOptionsData)) {
                foreach ($statusOptionsData as $item) {
                    if ($shopStatus == $item['bitrix']) {
                        $return = $item['mindbox'];
                        break;
                    }
                }
            }
        }

        return $return;
    }
}
