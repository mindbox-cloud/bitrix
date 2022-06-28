<?php

namespace Mindbox;

use \COption;
use Mindbox\Exceptions\MindboxConfigException;
use Mindbox\Loggers\MindboxFileLogger;

class Options
{
    private static $operations = [
        'authorize' => 'AuthorizeCustomer',
        'addToCart' => 'AddToCart',
        'autoConfirmMobile' => 'AutoConfirmMobilePhone',
        'calculateCart' => 'CalculateCart',
        'calculateAuthorizedCart' => 'CalculateAuthorizedCart',
        'calculateUnauthorizedCart' =>  'CalculateUnauthorizedCart',
        'calculateAuthorizedCartAdmin'  => 'CalculateAuthorizedCartAdmin',
        'cancelOrder' => 'CancelOrder',
        'checkActive' => 'CheckCustomerIsInLoyalityProgram',
        'checkAuthorizationCode' => 'CheckMobilePhoneAuthorizationCode',
        'checkByPhone' => 'CheckCustomerByMobilePhone',
        'checkByMail' => 'CheckCustomerByEmail',
        'check' => 'CheckCustomer',
        'confirmOrder' => 'ConfirmOrder',
        'confirmMobile' => 'ConfirmMobilePhone',
        'createOrder' => 'CreateOrder',
        'beginUnauthorizedOrderTransaction' =>  'BeginUnauthorizedOrderTransaction',
        'beginAuthorizedOrderTransaction' =>  'BeginAuthorizedOrderTransaction',
        'beginAuthorizedOrderTransactionAdmin'  =>  'BeginAuthorizedOrderTransactionAdmin',
        'createAuthorizedOrder' =>  'CreateAuthorizedOrder',
        'createUnauthorizedOrder'   =>  'CreateUnauthorizedOrder',
        'edit' => 'EditCustomer',
        'fill' => 'FillCustomerProfile',
        'getBalance' => 'GetCustomerBalance',
        'getBonusPointsHistory' => 'GetCustomerBonusPointsHistory',
        'getDataByDiscountCard' => 'GetCustomerDataByDiscountCard',
        'getCustomerInfo' => 'GetCustomerInfo',
        'getOrders' => 'GetCustomerOrders',
        'getSubscriptions' => 'GetCustomerSubscriptions',
        'merge' => 'MergeCustomers',
        'offlineOrder' => 'OfflineOrder',
        'register' => 'RegisterCustomer',
        'registerFromAnonymousOrder' => 'RegisterCustomerFromAnonymousOrder',
        'removeFromCart' => 'RemoveProduct',
        'resendConfirmationCode' => 'ResendMobilePhoneConfirmationCode',
        'resendEmailConfirm' => 'ResendEmailConfirmation',
        'sendAuthorizationCode' => 'SendMobilePhoneAuthorizationCode',
        'setProductList' => 'SetCart',
        'subscribe' => 'SubscribeCustomer',
        'viewProduct' => 'ViewProduct',
        'viewCategory' => 'ViewCategory',
        'commitOrderTransaction'    =>  'CommitOrderTransaction',
        'commitOrderTransactionAdmin'   =>  'CommitOrderTransactionAdmin',
        'rollbackOrderTransaction'  =>  'RollbackOrderTransaction',
        'rollbackOrderTransactionAdmin' =>  'RollbackOrderTransactionAdmin',
        'saveOfflineOrder'          =>  'SaveOfflineOrder',
        'setWishList'   =>  'SetWishList',
        'clearWishList' =>  'ClearWishList',
        'clearCart'     =>  'ClearCart',
        'updateOrderItemsStatus' => 'UpdateOrderItemsStatus',
        'updateOrderStatus' => 'UpdateOrderStatus',
        'updateOrderItems' => 'UpdateOrderItems',
        'getOrdersList'  =>  'GetOrdersList',
        'calculateUnauthorizedProduct' => 'CalculateUnauthorizedProduct',
        'checkCustomerByEmail'  =>  'CheckCustomerByEmail'
    ];

    private static $sdkOptions = [
        'endpointId' => 'ENDPOINT',
        'secretKey' => 'SECRET_KEY',
        'timeout' => 'TIMEOUT',
        'httpClient' => 'HTTP_CLIENT',
        'domain' => 'DOMAIN',
    ];

    public static function getOperationName($alias)
    {
        if (empty(static::$operations[$alias])) {
            throw new MindboxConfigException('Unknow Operation Name: ' . $alias);
        }

        return  static::getPrefix() . '.' . static::$operations[$alias];
    }

    public static function getSDKOptions()
    {
        foreach (static::$sdkOptions as $key => $option) {
            $sdkOptions[$key] = COption::GetOptionString('mindbox.marketing', $option);
        }

        // for standard mode
        $domain = COption::GetOptionString('mindbox.marketing', 'SYSTEM_NAME') .  '-services.mindbox.';

        $domainZone = COption::GetOptionString('mindbox.marketing', 'API_DOMAIN', 'ru');

        $sdkOptions['domainZone'] = $domainZone;
        $sdkOptions['domain'] = $domain.$domainZone;

        return $sdkOptions;
    }

    public static function getConfig($queue = false)
    {
        $config = static::getSDKOptions();
        if ($queue) {
            $config['timeout'] = COption::GetOptionString('mindbox.marketing', 'QUEUE_TIMEOUT', 30);
        }
        $path = COption::GetOptionString('mindbox.marketing', 'LOG_PATH');

        try {
            $mindbox =  new Mindbox($config, new MindboxFileLogger($path, 'debug'));
            $mindbox->getClientV2()->addHeaders(['Mindbox-Integration' => 'Bitrix', 'Mindbox-Integration-Version' => COption::GetOptionString('mindbox.marketing', 'MODULE_VERSION', '1.0')]);
            $mindbox->getClientV3()->addHeaders(['Mindbox-Integration' => 'Bitrix', 'Mindbox-Integration-Version' => COption::GetOptionString('mindbox.marketing', 'MODULE_VERSION', '1.0')]);

            return $mindbox;
        } catch (MindboxConfigException $e) {
            return false;
        }
    }


    public static function getPrefix()
    {
        return  COption::GetOptionString('mindbox.marketing', 'WEBSITE_PREFIX', 'Website');
    }

    public static function getExternalSystem()
    {
        return  COption::GetOptionString('mindbox.marketing', 'EXTERNAL_SYSTEM', 'system1c');
    }

    public static function getModuleOption($option)
    {
        return COption::GetOptionString('mindbox.marketing', $option);
    }
}
