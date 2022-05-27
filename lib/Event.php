<?php
/**
 * Содержит события
 */

namespace Mindbox;

defined('MINDBOX_ADMIN_MODULE_NAME') or define('MINDBOX_ADMIN_MODULE_NAME', 'mindbox.marketing');

use Bitrix\Main\Loader;
use Bitrix\Main;;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Page\AssetLocation;
use Mindbox\Components\CalculateProductData;

Loader::includeModule('catalog');
Loader::includeModule('sale');
Loader::includeModule('main');

/**
 * Class Event
 * @package Mindbox
 */
class Event
{
    const TRACKER_JS_FILENAME = "https://api.mindbox.ru/scripts/v1/tracker.js";

    /**
     * @bitrixModuleId main
     * @bitrixEventCode OnAfterUserAuthorize
     * @langEventName OnAfterUserAuthorize
     * @param $arUser
     */
    public static function OnAfterUserAuthorizeHandler($arUser)
    {
        \Mindbox\Handlers\User::onAfterUserAuthorize($arUser);
    }

    /**
     * @bitrixModuleId main
     * @bitrixEventCode OnBeforeUserUpdate
     * @langEventName OnBeforeUserUpdate
     * @param $arFields
     * @return bool
     */
    public static function OnBeforeUserUpdateHandler(&$arFields)
    {
       $result = \Mindbox\Handlers\User::onBeforeUserUpdate($arFields);

       return $result;
    }

    /**
     * @bitrixModuleId main
     * @bitrixEventCode OnBeforeUserAdd
     * @langEventName OnBeforeUserAdd
     * @param $arFields
     * @return false
     */
    public static function OnBeforeUserAddHandler(&$arFields)
    {
        $return = \Mindbox\Handlers\User::onBeforeUserAdd($arFields);

        return $return;
    }

    /**
     * @bitrixModuleId main
     * @bitrixEventCode OnAfterUserAdd
     * @langEventName OnAfterUserAdd
     * @param $arFields
     */
    public static function OnAfterUserAddHandler(&$arFields)
    {
        \Mindbox\Handlers\User::onAfterUserAdd($arFields);
    }

    /**
     * @bitrixModuleId sale
     * @bitrixEventCode OnSaleUserDelete
     * @langEventName OnSaleUserDelete
     * @isSystem true
     * @return void
     */
    public static function OnSaleUserDeleteHandler($id)
    {
        \Mindbox\Handlers\User::onSaleUserDelete($id);
    }

    /**
     * @bitrixModuleId sale
     * @bitrixEventCode OnSaleBasketSaved
     * @langEventName OnSaleBasketSaved
     * @param $basket
     * @return Main\EventResult|false
     */
    public static function OnSaleBasketSavedHandler($basket)
    {
        \Mindbox\Handlers\Basket::onSaleBasketSaved($basket);

        return new Main\EventResult(Main\EventResult::SUCCESS);
    }

    /**
     * @bitrixModuleId sale
     * @bitrixEventCode OnSaleBasketItemRefreshData
     * @langEventName OnSaleBasketItemRefreshData
     * @param $event
     * @return \Bitrix\Main\EventResult
     */
    public static function OnSaleBasketItemRefreshDataHandler($event)
    {
        \Mindbox\Handlers\Basket::onSaleBasketItemRefreshData($event);

        return new Main\EventResult(Main\EventResult::SUCCESS);
    }

    /**
     * @bitrixModuleId sale
     * @bitrixEventCode OnSaleBasketItemEntitySaved
     * @langEventName OnSaleBasketItemEntitySaved
     * @notCompatible truel
     */
    public static function OnSaleBasketItemEntitySavedHandler(\Bitrix\Main\Event $event)
    {
        if (Helper::isAdminSection() && Helper::isStandardMode()) {
            \Mindbox\Handlers\Basket::onSaleBasketItemEntitySavedStandart($event);
        }
    }

    /**
     * @bitrixModuleId sale
     * @bitrixEventCode OnSaleBasketItemEntityDeleted
     * @langEventName OnSaleBasketItemEntityDeleted
     * @notCompatible true
     */
    public static function OnSaleBasketItemDeletedHandler(\Bitrix\Main\Event $event)
    {
        if (Helper::isAdminSection()) {
            if (Helper::isStandardMode()) {
                \Mindbox\Handlers\Basket::OnSaleBasketItemEntityDeletedStandart($event);
            } else {
                \Mindbox\Handlers\Basket::onSaleBasketItemEntityDeletedLoyalty($event);
            }
        }
    }


    /**
     * @bitrixModuleId sale
     * @bitrixEventCode OnSaleOrderBeforeSaved
     * @langEventName OnSaleOrderBeforeSaved
     * @notCompatible true
     * @param $event
     * @return Main\EventResult
     */
    public static function OnSaleOrderBeforeSavedHandler(\Bitrix\Main\Event $event)
    {
        $return = \Mindbox\Handlers\Order::onSaleOrderBeforeSaved($event);

        return $return;
    }

    /**
     * @bitrixModuleId sale
     * @bitrixEventCode OnSaleOrderSaved
     * @langEventName OnSaleOrderSaved
     * @notCompatible true
     * @param $order
     * @return Main\EventResult
     */
    public static function OnSaleOrderSavedHandler(\Bitrix\Main\Event $event)
    {
        if (Helper::isLoyaltyMode()) {
            $return = \Mindbox\Handlers\Order::onSaleOrderSavedLoyalty($event);
        } else {
            $return = \Mindbox\Handlers\Order::onSaleOrderSavedStandart($event);
        }

        return $return;
    }

    /**
     * @bitrixModuleId sale
     * @bitrixEventCode OnBeforeSaleOrderFinalAction
     * @langEventName OnBeforeSaleOrderFinalAction
     * @param $basket
     * @return Main\EventResult|false
     */
    public static function OnBeforeSaleOrderFinalActionHandler($order, $has, $basket)
    {
        $return = \Mindbox\Handlers\Order::onBeforeSaleOrderFinalAction($order, $has, $basket);

        return $return;
    }

    /**
     * @bitrixModuleId sale
     * @bitrixEventCode OnSalePropertyValueSetField
     * @langEventName OnSalePropertyValueSetField
     * @notCompatible true
     * @param Main\Event $event
     */
    public static function OnSalePropertyValueSetFieldHandler(Main\Event $event)
    {
        $return = \Mindbox\Handlers\Order::onSalePropertyValueSetField($event);

        return $return;
    }

    /**
     * @bitrixModuleId sale
     * @bitrixEventCode OnBeforeSaleShipmentSetField
     * @langEventName OnBeforeSaleShipmentSetField
     * @notCompatible true
     * @param Main\Event $event
     * @return bool
     */
    public static function OnBeforeSaleShipmentSetFieldHandler(Main\Event $event)
    {
        $return = \Mindbox\Handlers\Order::onBeforeSaleShipmentSetField($event);

        return $return;
    }

    /**
     * @bitrixModuleId sale
     * @bitrixEventCode OnSaleStatusOrder
     * @langEventName OnSaleStatusOrder
     * @param Main\Event $event
     * @return bool
     */
    public static function OnSaleStatusOrderHandler($orderId, $newOrderStatus)
    {
        $return = \Mindbox\Handlers\Order::onSaleStatusOrder($orderId, $newOrderStatus);

        return $return;
    }

    /**
     * @bitrixModuleId sale
     * @bitrixEventCode OnSaleCancelOrder
     * @langEventName OnSaleCancelOrder
     * @param $orderId
     * @param $cancelFlag
     * @param $cancelDesc
     * @return void
     */
    public static function OnSaleCancelOrderHandler($orderId, $cancelFlag, $cancelDesc)
    {
        $return = \Mindbox\Handlers\Order::onSaleCancelOrder($orderId, $cancelFlag, $cancelDesc);

        return $return;
    }

    /**
     * @bitrixModuleId main
     * @bitrixEventCode OnAdminSaleOrderEdit
     * @langEventName OnAdminSaleOrderEdit
     * @return false
     */
    public static function OnAdminSaleOrderEditHandler()
    {
        if (Helper::isLoyaltyMode()) {
            $jsString = Helper::getAdditionalScriptForOrderEditPage();

            if (isset($jsString) && !empty($jsString)) {
                Asset::getInstance()->addString($jsString, true, AssetLocation::AFTER_JS);
            }
        }
    }

    /**
     * @bitrixModuleId main
     * @bitrixEventCode OnBeforeProlog
     * @langEventName OnBeforeProlog
     * @isSystem true
     */
    public static function OnBeforePrologHandler()
    {
        Loader::includeModule(MINDBOX_ADMIN_MODULE_NAME);
    }

    /**
     * @bitrixModuleId main
     * @bitrixEventCode OnProlog
     * @langEventName OnProlog
     * @param $arFields
     */
    public static function OnPrologHandler()
    {
        $defaultOptions = \Bitrix\Main\Config\Option::getDefaults("mindbox.marketing");
        $jsString = "<script data-skip-moving=\"true\">\r\n" . file_get_contents($_SERVER['DOCUMENT_ROOT'] . $defaultOptions['TRACKER_JS_FILENAME']) . "</script>\r\n";
        $jsString .= '<script data-skip-moving="true" src="' . self::TRACKER_JS_FILENAME . '" async></script>';
        Asset::getInstance()->addString($jsString, true);
    }

    /**
     * @bitrixModuleId main
     * @bitrixEventCode OnEndBufferContent
     * @langEventName OnEndBufferContent
     * @param $content
     */
    public static function OnEndBufferContentHandler(&$content)
    {
        $calc = new CalculateProductData();
        $calc->handle($content);
    }

    /**
     * @bitrixModuleId main
     * @bitrixEventCode OnAfterSetOption_YML_CHUNK_SIZE
     * @langEventName onAfterSetOption_YML_CHUNK_SIZE
     * @notCompatible true
     * @isSystem true
     */
    public static function onAfterSetOption_YML_CHUNK_SIZE(\Bitrix\Main\Event $event)
    {
        $agents = \CAgent::GetList(['ID' => 'DESC'], ['NAME' => '\Mindbox\YmlFeedMindbox::start(%']);

        $existingAgents = [];

        while ($agent = $agents->Fetch()) {
            $regex = '#(?<=\().+?(?=\))#';

            preg_match($regex, $agent['NAME'], $match);

            if (!empty($match) && intval($match[0]) > 0) {
                $existingAgents[] = $agent['ID'];
            }
        }

        foreach ($existingAgents as $num) {
            \CAgent::Delete($num);
        }

        if (!empty($existingAgents)) {
            $now = new \Bitrix\Main\Type\DateTime();
            \CAgent::AddAgent(
                    "\Mindbox\YmlFeedMindbox::start(1);",
                    MINDBOX_ADMIN_MODULE_NAME,
                    'N',
                    86400,
                    $now,
                    'Y',
                    $now,
                    30
            );
        }
    }
}
