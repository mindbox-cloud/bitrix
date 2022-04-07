<?php

namespace Mindbox\Handlers;

use Bitrix\Main;
use Bitrix\Sale;
use Mindbox\DTO\DTO;
use Mindbox\DTO\V3\Requests\CustomerIdentityRequestDTO;
use Mindbox\DTO\V3\Requests\ProductListItemRequestCollection;
use Mindbox\DTO\V3\Requests\ProductListItemRequestDTO;
use Mindbox\DTO\V3\Requests\ProductRequestDTO;
use Mindbox\Core;
use Mindbox\Helper;
use Mindbox\Options;
use Mindbox\Exceptions;
use Mindbox\QueueTable;

class Basket
{
    use Core;
    /**
     * @param Main\Event $event
     * @return void
     */
    public static function onSaleBasketItemEntityDeletedStandart(\Bitrix\Main\Event $event)
    {
        $entity = $event->getParameter("ENTITY");
        $order = $entity->getCollection()->getOrder();

        if ($order instanceof \Bitrix\Sale\Order) {
            \Mindbox\Handlers\Order::updateMindboxOrderItems($order);
        }
    }

    /**
     * @param Main\Event $event
     * @return Main\EventResult|void
     */
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

    /**
     * @param Main\Event $event
     * @return void
     */
    public static function onSaleBasketItemEntitySavedStandart(\Bitrix\Main\Event $event)
    {
        if (Helper::isStandardMode() && Helper::isAdminSection()) {
            $entity = $event->getParameter("ENTITY");
            $order = $entity->getCollection()->getOrder();

            if (!empty($order) && $order instanceof \Bitrix\Sale\Order) {
                \Mindbox\Handlers\Order::updateMindboxOrderItems($order);
            }
        }
    }

    /**
     * @param $event
     * @return void
     * @throws Main\ArgumentException
     * @throws Main\ArgumentTypeException
     * @throws Main\NotImplementedException
     */
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
            self::setWishList();
        }

        if (empty($_SESSION['MB_WISHLIST']) && isset($_SESSION['MB_WISHLIST_COUNT'])) {
            self::clearWishList();
        }
    }

    /**
     * @param $basket
     * @return void
     */
    public static function onSaleBasketSaved($basket)
    {
        $basketItems = $basket->getBasketItems();

        self::setCartMindbox($basketItems);

        if (empty($basketItems)) {
            $_SESSION['MB_CLEAR_CART'] = 'Y';
        }
    }

    /**
     * @param $basketItems
     */
    public static function setCartMindbox($basketItems)
    {
        global $USER;

        $mindbox = static::mindbox();
        if (!$mindbox) {
            return;
        }

        $arLines = [];
        $arAllLines = [];
        foreach ($basketItems as $basketItem) {
            $arAllLines[$basketItem->getProductId()] = $basketItem->getProductId();
            if ($basketItem->getField('DELAY') === 'Y') {
                continue;
            }

            $productId = $basketItem->getProductId();
            $arLines[$productId]['basketItem'] = $basketItem;
            $arLines[$productId]['quantity'] += $basketItem->getQuantity();
            $arLines[$productId]['priceOfLine'] += $basketItem->getPrice() * $basketItem->getQuantity();
        }

        $lines = [];
        foreach ($arLines as $arLine) {
            $product = new ProductRequestDTO();
            $product->setId(
                    Options::getModuleOption('EXTERNAL_SYSTEM'),
                    Helper::getElementCode($arLine['basketItem']->getProductId())
            );

            $line = new ProductListItemRequestDTO();
            $line->setProduct($product);
            $line->setCount($arLine['quantity']);
            $line->setPriceOfLine($arLine['priceOfLine']);
            $lines[] = $line;
        }

        if (is_object($USER) && $USER->IsAuthorized() && !empty($USER->GetEmail())) {
            $fields = [
                    'email' => $USER->GetEmail()
            ];
            $customer = Helper::iconvDTO(new CustomerIdentityRequestDTO($fields));
        }

        if (empty($arAllLines) && count($_SESSION['MB_WISHLIST_COUNT'])) {
            self::clearWishList();
        }

        if (empty($arLines)) {
            if (!isset($_SESSION['MB_CLEAR_CART'])) {
                self::clearCart();
            }

            return;
        }

        try {
            $mindbox->productList()->setProductList(
                    new ProductListItemRequestCollection($lines),
                    Options::getOperationName('setProductList'),
                    $customer
            )->sendRequest();
        } catch (Exceptions\MindboxClientErrorException $e) {
        } catch (Exceptions\MindboxClientException $e) {
            $lastResponse = $mindbox->productList()->getLastResponse();
            if ($lastResponse) {
                $request = $lastResponse->getRequest();
                QueueTable::push($request);
            }
        }
    }

    public static function setWishList()
    {
        $mindbox = static::mindbox();
        if (!$mindbox) {
            return false;
        }

        $basket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), Main\Context::getCurrent()->getSite());
        $basketItems = $basket->getBasketItems();
        $arLines = [];
        foreach ($basketItems as $basketItem) {
            if ($basketItem->getField('DELAY') === 'N') {
                continue;
            }
            $productId = $basketItem->getProductId();
            $arLines[ $productId ]['basketItem'] = $basketItem;
            $arLines[ $productId ]['quantity'] += $basketItem->getQuantity();
            $arLines[ $productId ]['priceOfLine'] += $basketItem->getPrice();
        }

        $lines = [];
        foreach ($arLines as $arLine) {
            $product = new ProductRequestDTO();
            $product->setId(Options::getModuleOption('EXTERNAL_SYSTEM'), Helper::getElementCode($arLine['basketItem']->getProductId()));
            $line = new ProductListItemRequestDTO();
            $line->setProduct($product);
            $line->setCount($arLine['quantity']);
            $line->setPriceOfLine($arLine['priceOfLine']);
            $lines[] = $line;
        }

        if (empty($lines)) {
            return false;
        }

        try {
            $mindbox->productList()->setWishList(
                    new ProductListItemRequestCollection($lines),
                    Options::getOperationName('setWishList')
            )->sendRequest();
            $_SESSION['MB_WISHLIST_COUNT'] = count($_SESSION['MB_WISHLIST']);
            self::setCartMindbox($basketItems);
        } catch (Exceptions\MindboxClientErrorException $e) {
            $lastResponse = $mindbox->productList()->getLastResponse();
            if ($lastResponse) {
                $request = $lastResponse->getRequest();
                QueueTable::push($request);
            }
        } catch (Exceptions\MindboxClientException $e) {
            $lastResponse = $mindbox->productList()->getLastResponse();
            if ($lastResponse) {
                $request = $lastResponse->getRequest();
                QueueTable::push($request);
            }
        }
    }

    public static function clearWishList()
    {
        $mindbox = static::mindbox();
        if (!$mindbox) {
            return false;
        }

        $basket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), Main\Context::getCurrent()->getSite());
        $basketItems = $basket->getBasketItems();

        try {
            $mindbox->productList()->clearWishList(Options::getOperationName('clearWishList'))->sendRequest();
            unset($_SESSION['MB_WISHLIST_COUNT']);
            self::setCartMindbox($basketItems);
        } catch (Exceptions\MindboxClientErrorException $e) {
            $lastResponse = $mindbox->productList()->getLastResponse();
            if ($lastResponse) {
                $request = $lastResponse->getRequest();
                QueueTable::push($request);
            }
        } catch (Exceptions\MindboxClientException $e) {
            $lastResponse = $mindbox->productList()->getLastResponse();
            if ($lastResponse) {
                $request = $lastResponse->getRequest();
                QueueTable::push($request);
            }
        }
    }

    private static function clearCart()
    {
        $mindbox = static::mindbox();
        if (!$mindbox) {
            return false;
        }

        $_SESSION['MB_CLEAR_CART'] = 'Y';

        try {
            $mindbox->productList()->clearCart(Options::getOperationName('clearCart'))->sendRequest();
        } catch (Exceptions\MindboxClientErrorException $e) {
            $lastResponse = $mindbox->productList()->getLastResponse();
            if ($lastResponse) {
                $request = $lastResponse->getRequest();
                QueueTable::push($request);
            }
        } catch (Exceptions\MindboxClientException $e) {
            $lastResponse = $mindbox->productList()->getLastResponse();
            if ($lastResponse) {
                $request = $lastResponse->getRequest();
                QueueTable::push($request);
            }
        }
    }
}
