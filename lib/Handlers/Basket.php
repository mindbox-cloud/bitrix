<?php

namespace Mindbox\Handlers;

use Bitrix\Main;
use Bitrix\Sale;
use Mindbox\DTO\DTO;
use Mindbox\Helper;
use Mindbox\Options;
use Mindbox\Exceptions;

class Basket
{
    public static function onSaleBasketItemEntityDeletedStandart(\Bitrix\Main\Event $event)
    {
        $entity = $event->getParameter("ENTITY");
        $order = $entity->getCollection()->getOrder();

        if ($order instanceof \Bitrix\Sale\Order) {
            Helper::updateMindboxOrderItems($order);
        }
    }

    public static function onSaleBasketItemEntityDeletedLoyalty(\Bitrix\Main\Event $event)
    {
        if (Helper::isDeleteOrderAdminAction() || Helper::isDeleteOrderItemAdminAction()) {
            return new Main\EventResult(Main\EventResult::SUCCESS);
        }

        $entity = $event->getParameter("ENTITY");
        $order = $entity->getCollection()->getOrder();
        $orderId = $order->getId();

        if (!empty($entity)
                && $orderId > 0
                && Helper::isMindboxOrder($orderId)
        ) {
            $deleteLines[] = [
                    'lineId' => $entity->getId(),
                    'quantity' => $entity->getQuantity() + 1,
                    'status' => 'Cancelled',
            ];

            $requestData = [
                    'order' => [
                            'ids' => [
                                    Options::getModuleOption('TRANSACTION_ID') => $orderId
                            ],
                            'lines' => $deleteLines
                    ]
            ];

            $request = Helper::mindbox()->getClientV3()->prepareRequest(
                    'POST',
                    Options::getOperationName('updateOrderItemsStatus'),
                    new DTO($requestData)
            );

            try {
                $response = $request->sendRequest();
            } catch (Exceptions\MindboxClientException $e) {
            }
        }
    }

    public static function onSaleBasketItemEntitySavedStandart(\Bitrix\Main\Event $event)
    {
        if (Helper::isStandardMode() && Helper::isAdminSection()) {
            $entity = $event->getParameter("ENTITY");
            $order = $entity->getCollection()->getOrder();

            if (!empty($order) && $order instanceof \Bitrix\Sale\Order) {
                Helper::updateMindboxOrderItems($order);
            }
        }
    }

    public static function onSaleBasketItemRefreshData($event)
    {
        $basketItem = $event;
        $basket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), Main\Context::getCurrent()->getSite());
        $basketItems = $basket->getBasketItems();

        if (empty($basketItems)) {
            $_SESSION['MB_CLEAR_CART'] = 'Y';
        } else {
            unset($_SESSION['MB_CLEAR_CART']);
        }

        if ($basketItem->getField('DELAY') === 'Y') {
            $_SESSION['MB_WISHLIST'][$basketItem->getProductId()] = $basketItem;
        } else {
            if ($basketItem->getField('DELAY') === 'N' && array_key_exists(
                            $basketItem->getProductId(),
                            $_SESSION['MB_WISHLIST']
                    )) {
                unset($_SESSION['MB_WISHLIST'][$basketItem->getProductId()]);
            }
        }

        if (!empty($_SESSION['MB_WISHLIST']) && count($_SESSION['MB_WISHLIST']) !== $_SESSION['MB_WISHLIST_COUNT']) {
            Helper::setWishList();
        }

        if (empty($_SESSION['MB_WISHLIST']) && isset($_SESSION['MB_WISHLIST_COUNT'])) {
            Helper::clearWishList();
        }
    }

    public static function onSaleBasketSaved($basket)
    {
        $basketItems = $basket->getBasketItems();

        Helper::setCartMindbox($basketItems);

        if (empty($basketItems)) {
            $_SESSION['MB_CLEAR_CART'] = 'Y';
        }
    }
}