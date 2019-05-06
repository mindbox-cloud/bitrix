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
        'cancelOrder' => 'CancelOrder',
        'checkActive' => 'CheckCustomerIsInLoyalityProgram',
        'checkAuthorizationCode' => 'CheckMobilePhoneAuthorizationCode',
        'checkByPhone' => 'CheckCustomerByMobilePhone',
        'checkByMail' => 'CheckCustomerByEmail',
        'check' => 'CheckCustomer',
        'confirmOrder' => 'ConfirmOrder',
        'confirmMobile' => 'ConfirmMobilePhone',
        'createOrder' => 'CreateOrder',
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
        'viewCategory' => 'ViewCategory'
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
        if(empty(static::$operations[$alias])) {
            throw new MindboxConfigException('Unknow Operation Name');
        }

        return  static::getPrefix() . '.' . static::$operations[$alias];
    }

    public static function getSDKOptions()
    {
        foreach (static::$sdkOptions as $key => $option) {
            $sdkOptions[$key] = COption::GetOptionString('qsoftm.mindbox', $option);
        }

        return $sdkOptions;
    }

    public static function getConfig($queue = false)
    {
        $config = static::getSDKOptions();

        if($queue) {
            $config['timeout'] = COption::GetOptionString('qsoftm.mindbox','QUEUE_TIMEOUT', 30);
        }
        $path = COption::GetOptionString('qsoftm.mindbox', 'LOG_PATH');

        return new Mindbox($config, new MindboxFileLogger($path, 'debug'));
    }


    public static function getPrefix()
    {
        return  COption::GetOptionString('qsoftm.mindbox', 'WEBSITE_PREFIX', 'Website');
    }

    public static function getModuleOption($option) {
        return COption::GetOptionString('qsoftm.mindbox', $option);
    }
}