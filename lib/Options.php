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
        'rollbackOrderTransaction'  =>  'RollbackOrderTransaction',
        'saveOfflineOrder'          =>  'SaveOfflineOrder',
        'setWishList'   =>  'SetWishList',
        'clearWishList' =>  'ClearWishList',
        'clearCart' =>  'ClearCart'
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
            throw new MindboxConfigException('Unknow Operation Name');
        }

        return  static::getPrefix() . '.' . static::$operations[$alias];
    }

    public static function getSDKOptions()
    {
        foreach (static::$sdkOptions as $key => $option) {
            $sdkOptions[$key] = COption::GetOptionString('mindbox.marketing', $option);
        }

        // for standard mode
        $sdkOptions['domain'] = COption::GetOptionString('mindbox.marketing', 'SYSTEM_NAME') .  '-services.mindbox.ru';

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
            $mindbox->getClientV2()->addHeaders(['Mindbox-Integration' => 'Bitrix']);
            $mindbox->getClientV3()->addHeaders(['Mindbox-Integration' => 'Bitrix']);

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
