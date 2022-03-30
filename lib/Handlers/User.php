<?php

namespace Mindbox\Handlers;

use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserTable;
use CUser;
use Mindbox\Core;
use Mindbox\DTO\DTO;
use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\Helper;
use Mindbox\Options;
use Mindbox\QueueTable;
use Mindbox\Exceptions;

class User
{
    use Core;

    public static function onAfterUserAuthorize($arUser)
    {
        if (!$arUser['user_fields']['ID']) {
            return;
        }

        $userMindboxId = false;
        $rsUser = UserTable::getList(
                [
                        'select' => [
                                'UF_MINDBOX_ID'
                        ],
                        'filter' => ['ID' => $arUser['user_fields']['ID']],
                        'limit'  => 1
                ]
        )->fetch();

        if ($rsUser && isset($rsUser['UF_MINDBOX_ID']) && $rsUser['UF_MINDBOX_ID'] > 0) {
            $userMindboxId = $rsUser['UF_MINDBOX_ID'];
        }

        if (!isset($_REQUEST['AUTH_FORM']) && !isset($_REQUEST['TYPE']) || Context::getCurrent()->getRequest()->isAdminSection()) {
            return;
        }

        if (empty($arUser['user_fields']['LAST_LOGIN']) && !$userMindboxId) {
            return;
        }

        $mindbox = static::mindbox();

        if (!$mindbox) {
            return;
        }

        if (isset($_SESSION['NEW_USER_MINDBOX']) && $_SESSION['NEW_USER_MINDBOX'] === true) {
            unset($_SESSION['NEW_USER_MINDBOX']);

            return;
        }

        $mindboxId = Helper::getMindboxId($arUser['user_fields']['ID']);

        if (empty($mindboxId) && Helper::isLoyaltyMode()) {
            $request = $mindbox->getClientV3()->prepareRequest(
                    'POST',
                    Options::getOperationName('getCustomerInfo'),
                    new DTO([
                            'customer' => [
                                    'ids' => [
                                            Options::getModuleOption('WEBSITE_ID') => $arUser['user_fields']['ID']
                                    ]
                            ]
                    ])
            );

            try {
                $response = $request->sendRequest();
            } catch (Exceptions\MindboxClientException $e) {
                return;
            }

            if ($response->getResult()->getCustomer()->getProcessingStatus() === 'Found') {
                $fields = [
                    'UF_EMAIL_CONFIRMED' => $response->getResult()->getCustomer()->getIsEmailConfirmed(),
                    'UF_MINDBOX_ID'      => $response->getResult()->getCustomer()->getId('mindboxId')
                ];

                $user = new CUser;
                $user->Update(
                        $arUser['user_fields']['ID'],
                        $fields
                );
            } else {
                return;
            }
        }

        $customer = new CustomerRequestDTO([
                'ids' => [
                        Options::getModuleOption('WEBSITE_ID') => $arUser['user_fields']['ID']
                ]
        ]);

        $lastName = trim($arUser['user_fields']['LAST_NAME']);
        $firstName = trim($arUser['user_fields']['NAME']);
        $middleName = trim($arUser['user_fields']['SECOND_NAME']);
        $email = trim($arUser['user_fields']['EMAIL']);
        $mobilePhone = trim($arUser['user_fields']['PERSONAL_PHONE']);
        $phoneNumber = trim($arUser['user_fields']['PHONE_NUMBER']);

        if (!empty($phoneNumber)) {
            $mobilePhone = $phoneNumber;
        }

        if (!empty($lastName)) {
            $customer->setLastName($lastName);
        }

        if (!empty($firstName)) {
            $customer->setFirstName($firstName);
        }

        if (!empty($middleName)) {
            $customer->setMiddleName($middleName);
        }

        if (!empty($email)) {
            $customer->setEmail($email);
        }

        if (!empty($mobilePhone)) {
            $customer->setMobilePhone($mobilePhone);
        }

        try {
            $mindbox->customer()->authorize(
                    $customer,
                    Options::getOperationName('authorize')
            )->sendRequest();
        } catch (Exceptions\MindboxUnavailableException $e) {
            $lastResponse = $mindbox->customer()->getLastResponse();

            if ($lastResponse) {
                $request = $lastResponse->getRequest();
                QueueTable::push($request);
            }
        } catch (Exceptions\MindboxClientException $e) {
            return;
        }
    }

    public static function onBeforeUserUpdate(&$arFields)
    {
        global $APPLICATION;

        if (isset($_REQUEST['c']) &&
                $_REQUEST['c'] === 'mindbox:auth.sms' &&
                isset($_REQUEST['action']) &&
                $_REQUEST['action'] === 'checkCode'
        ) {
            return;
        }

        $mindbox = static::mindbox();

        if (!$mindbox) {
            return;
        }

        $params = [
                'select' => ['EMAIL', 'PERSONAL_PHONE', 'UF_MINDBOX_ID'],
                'filter' => ['=ID' => $arFields['ID']],
                'limit' => 1
        ];

        if (class_exists('\Bitrix\Main\UserPhoneAuthTable')) {
            $params['runtime'] = [
                    new \Bitrix\Main\Entity\ReferenceField(
                            'R_PHONE_AUTH',
                            '\Bitrix\Main\UserPhoneAuthTable',
                            ['=this.ID' => 'ref.USER_ID'],
                            ['join_type' => 'LEFT']
                    ),
            ];

            $params['select']['PHONE_NUMBER'] = 'R_PHONE_AUTH.PHONE_NUMBER';
        }

        $dbUser = UserTable::getList($params)->fetch();

        if (!$dbUser) {
            return;
        }

        if (!isset($arFields['PERSONAL_PHONE'])) {
            $arFields['PERSONAL_PHONE'] = $arFields['PERSONAL_MOBILE'];
        }

        if (!isset($arFields['PERSONAL_PHONE']) && !empty($arFields['PHONE_NUMBER'])) {
            $arFields['PERSONAL_PHONE'] = $arFields['PHONE_NUMBER'];
        }

        if (!isset($arFields['PERSONAL_PHONE']) && !empty($dbUser['PHONE_NUMBER'])) {
            $arFields['PERSONAL_PHONE'] = $dbUser['PHONE_NUMBER'];
        }

        if (isset($arFields['EMAIL']) && $dbUser['EMAIL'] != $arFields['EMAIL']) {
            $arFields['UF_EMAIL_CONFIRMED'] = '0';
        }

        if (isset($arFields['PERSONAL_PHONE'])) {
            $arFields['PERSONAL_PHONE'] = Helper::formatPhone($arFields['PERSONAL_PHONE']);
        }

        if (isset($_SESSION['OFFLINE_REGISTER']) && $_SESSION['OFFLINE_REGISTER']) {
            unset($_SESSION['OFFLINE_REGISTER']);

            return;
        }

        $userId = $arFields['ID'];
        $mindboxId = $dbUser['UF_MINDBOX_ID'];

        if (!empty($userId)) {
            $sex = substr(ucfirst($arFields['PERSONAL_GENDER']), 0, 1) ?: null;

            $fields = [
                'birthDate'   => Helper::formatDate($arFields["PERSONAL_BIRTHDAY"]),
                'firstName'   => $arFields['NAME'],
                'middleName'  => $arFields['SECOND_NAME'],
                'lastName'    => $arFields["LAST_NAME"],
                'mobilePhone' => $arFields['PERSONAL_PHONE'],
                'email'       => $arFields['EMAIL'],
                'sex'         => $sex
            ];

            $customFields = Helper::getCustomFieldsForUser($userId, $arFields);
            if (!empty($customFields)) {
                $fields['customFields'] = $customFields;
            }

            $fields = array_filter($fields, function ($item) {
                return isset($item);
            });

            if (!isset($fields)) {
                return;
            }

            if (empty($mindboxId) && Helper::isLoyaltyMode()) {
                $request = $mindbox->getClientV3()->prepareRequest(
                    'POST',
                    Options::getOperationName('getCustomerInfo'),
                    new DTO([
                        'customer' => [
                            'ids' => [
                                    Options::getModuleOption('WEBSITE_ID') => $arFields['ID']
                            ]
                        ]
                    ])
                );

                try {
                    $response = $request->sendRequest();
                } catch (Exceptions\MindboxClientException $e) {
                    $APPLICATION->ThrowException(Loc::getMessage('MB_USER_EDIT_ERROR'));

                    return false;
                }

                if ($response->getResult()->getCustomer()->getProcessingStatus() === 'Found') {
                    $mindboxId = $response->getResult()->getCustomer()->getId('mindboxId');
                    $arFields['UF_MINDBOX_ID'] = $mindboxId;
                }
            }

            if (Helper::isStandardMode()) {
                $fields['ids'][Options::getModuleOption('WEBSITE_ID')] = $userId;
            } else {
                $fields['ids']['mindboxId'] = $mindboxId;
            }

            $customer = new CustomerRequestDTO($fields);
            $customer = Helper::iconvDTO($customer);
            unset($fields);

            try {
                $updateResponse = $mindbox->customer()->edit(
                    $customer,
                    Options::getOperationName('edit'),
                    true
                )->sendRequest()->getResult();
            } catch (Exceptions\MindboxClientException $e) {
                $APPLICATION->ThrowException(Loc::getMessage('MB_USER_EDIT_ERROR'));

                return false;
            }

            $updateResponse = Helper::iconvDTO($updateResponse, false);

            $status = $updateResponse->getStatus();

            if ($status === 'ValidationError') {
                $errors = $updateResponse->getValidationMessages();
                $APPLICATION->ThrowException(Helper::formatValidationMessages($errors));

                return false;
            }
        }
    }

    public static function onBeforeUserAdd(&$arFields)
    {
        global $APPLICATION, $USER;

        if (Helper::isStandardMode()) {
            return;
        }

        if ($_REQUEST['mode'] == 'class'
                && $_REQUEST['c'] == 'mindbox:auth.sms'
                && $_REQUEST['action'] == 'checkCode'
        ) {
            return;
        }

        if ($_REQUEST['mode'] == 'class'
                && $_REQUEST['c'] == 'mindbox:auth.sms'
                && $_REQUEST['action'] == 'fillup'
        ) {
            return;
        }



        if (!$USER || is_string($USER)) {
            return;
        }

        $mindbox = static::mindbox();
        if (!$mindbox) {
            return;
        }

        if (!isset($arFields['PERSONAL_PHONE']) && isset($arFields['PERSONAL_MOBILE'])) {
            $arFields['PERSONAL_PHONE'] = $arFields['PERSONAL_MOBILE'];
        }

        if (empty($arFields['PERSONAL_PHONE']) && isset($arFields['PHONE_NUMBER'])) {
            $arFields['PERSONAL_PHONE'] = $arFields['PHONE_NUMBER'];
        }

        if (isset($arFields['PERSONAL_PHONE'])) {
            $arFields['PERSONAL_PHONE'] = Helper::formatPhone($arFields['PERSONAL_PHONE']);
        }

        $sex = substr(ucfirst($arFields['PERSONAL_GENDER']), 0, 1) ?: null;
        $fields = [
                'email'       => $arFields['EMAIL'],
                'lastName'    => $arFields['LAST_NAME'],
                'middleName'  => $arFields['SECOND_NAME'],
                'firstName'   => $arFields['NAME'],
                'mobilePhone' => $arFields['PERSONAL_PHONE'],
                'birthDate'   => Helper::formatDate($arFields['PERSONAL_BIRTHDAY']),
                'sex'         => $sex,
        ];

        $fields = array_filter($fields);

        $customFields = [];
        $ufFields = array_filter($arFields, function ($value, $key) {
            return strpos($key, 'UF_') !== false;
        }, ARRAY_FILTER_USE_BOTH);

        foreach ($ufFields as $code => $value) {
            if (!empty($customName = Helper::getMatchByCode($code, Helper::getUserFieldsMatch()))) {
                $customFields[Helper::sanitizeNamesForMindbox($customName)] = $value;
            }
        }

        if (!empty($customFields)) {
            $fields['customFields'] = $customFields;
        }

        $isSubscribed = true;
        if ($arFields['UF_MB_IS_SUBSCRIBED'] === '0') {
            $isSubscribed = false;
        }

        $fields['subscriptions'] = [
                [
                        'brand'        => Options::getModuleOption('BRAND'),
                        'isSubscribed' => $isSubscribed
                ]
        ];

        $customer = Helper::iconvDTO(new CustomerRequestDTO($fields));

        unset($fields);

        try {
            $registerResponse = $mindbox->customer()->register(
                    $customer,
                    Options::getOperationName('register'),
                    true,
                    Helper::isSync()
            )->sendRequest()->getResult();
        } catch (Exceptions\MindboxUnavailableException $e) {
            $APPLICATION->ThrowException(Loc::getMessage("MB_USER_REGISTER_LOYALTY_ERROR"));

            return false;
        } catch (Exceptions\MindboxClientException $e) {
            $APPLICATION->ThrowException(Loc::getMessage("MB_USER_REGISTER_LOYALTY_ERROR"));

            return false;
        } catch (\Exception $e) {
            $APPLICATION->ThrowException(Loc::getMessage("MB_USER_REGISTER_LOYALTY_ERROR"));

            return false;
        }

        if ($registerResponse) {
            $registerResponse = Helper::iconvDTO($registerResponse, false);
            $status = $registerResponse->getStatus();

            if ($status === 'ValidationError') {
                try {
                    $fields = [
                            'email'       => $arFields['EMAIL'],
                            'mobilePhone' => $arFields['PERSONAL_PHONE'],
                    ];

                    $customer = Helper::iconvDTO(new CustomerRequestDTO($fields));

                    $registerResponse = $mindbox->customer()->CheckCustomer(
                            $customer,
                            Options::getOperationName('check'),
                            true
                    )->sendRequest()->getResult();
                } catch (\Exception $e) {
                    $errors = $registerResponse->getValidationMessages();
                    $APPLICATION->ThrowException(Helper::formatValidationMessages($errors));

                    return false;
                }
            }

            $customer = $registerResponse->getCustomer();
            $mindBoxId = $customer->getId('mindboxId');
            $arFields['UF_MINDBOX_ID'] = $mindBoxId;
            $_SESSION['NEW_USER_MB_ID'] = $mindBoxId;
            $_SESSION['NEW_USER_MINDBOX'] = true;
        }
    }

    public static function onAfterUserAdd(&$arFields)
    {
        global $APPLICATION;

        $mindbox = static::mindbox();

        if (!$mindbox) {
            return;
        }



        if (Helper::isStandardMode()) {
            if (empty($arFields['EMAIL']) || empty($arFields['ID'])) {
                return;
            }

            if (!isset($arFields['PERSONAL_PHONE']) && isset($arFields['PERSONAL_MOBILE'])) {
                $arFields['PERSONAL_PHONE'] = $arFields['PERSONAL_MOBILE'];
            }

            if (empty($arFields['PERSONAL_PHONE']) && isset($arFields['PHONE_NUMBER'])) {
                $arFields['PERSONAL_PHONE'] = $arFields['PHONE_NUMBER'];
            }

            if (isset($arFields['PERSONAL_PHONE'])) {
                $arFields['PERSONAL_PHONE'] = Helper::formatPhone($arFields['PERSONAL_PHONE']);
            }

            $sex = substr(ucfirst($arFields['PERSONAL_GENDER']), 0, 1) ?: null;
            $fields = [
                'email'       => $arFields['EMAIL'],
                'lastName'    => $arFields['LAST_NAME'],
                'middleName'  => $arFields['SECOND_NAME'],
                'firstName'   => $arFields['NAME'],
                'mobilePhone' => $arFields['PERSONAL_PHONE'],
                'birthDate'   => Helper::formatDate($arFields['PERSONAL_BIRTHDAY']),
                'sex'         => $sex,
                'ids'         => [Options::getModuleOption('WEBSITE_ID') => $arFields['ID']]
            ];

            $fields = array_filter($fields);

            $customFields = [];
            $ufFields = array_filter($arFields, function ($value, $key) {
                return strpos($key, 'UF_') !== false;
            }, ARRAY_FILTER_USE_BOTH);

            foreach ($ufFields as $code => $value) {
                if (!empty($customName = Helper::getMatchByCode($code, Helper::getUserFieldsMatch()))) {
                    $customFields[Helper::sanitizeNamesForMindbox($customName)] = $value;
                }
            }

            if (!empty($customFields)) {
                $fields['customFields'] = $customFields;
            }

            $isSubscribed = true;
            if ($arFields['UF_MB_IS_SUBSCRIBED'] === '0') {
                $isSubscribed = false;
            }

            $fields['subscriptions'] = [
                [
                    'brand'        => Options::getModuleOption('BRAND'),
                    'isSubscribed' => $isSubscribed
                ]
            ];

            $customer = Helper::iconvDTO(new CustomerRequestDTO($fields));

            unset($fields);

            try {
                $mindbox->customer()->register(
                    $customer,
                    Options::getOperationName('register'),
                    true,
                    Helper::isSync()
                )->sendRequest()->getResult();
            } catch (\Exception $e) {
                return;
            }
        } else {
            $mindBoxId = $arFields['UF_MINDBOX_ID'];

            if (!$mindBoxId) {
                return;
            }

            $request = $mindbox->getClientV3()->prepareRequest(
                'POST',
                Options::getOperationName('getCustomerInfo'),
                new DTO([
                    'customer' => [
                            'ids' => [
                                    'mindboxId' => $mindBoxId
                            ]
                    ]
                ])
            );

            try {
                $response = $request->sendRequest();
            } catch (Exceptions\MindboxClientException $e) {
                $APPLICATION->ThrowException($e->getMessage());

                return false;
            }

            if ($response->getResult()->getCustomer()->getProcessingStatus() === 'Found') {
                $fields = [
                    'UF_EMAIL_CONFIRMED' => $response->getResult()->getCustomer()->getIsEmailConfirmed(),
                    'UF_MINDBOX_ID'      => $response->getResult()->getCustomer()->getId('mindboxId')
                ];

                $user = new CUser;
                $user->Update(
                        $arFields['ID'],
                        $fields
                );
            }
        }
    }

    public static function onSaleUserDelete($id)
    {
        if (class_exists('\\Mindbox\\Discount\\DeliveryDiscountEntity')) {
            $deliveryDiscountEntity = new \Mindbox\Discount\DeliveryDiscountEntity();
            $deliveryDiscountEntity->deleteByFilter([
                    'UF_FUSER_ID' => $id,
                    'UF_ORDER_ID' => null
            ]);
        }
    }
}
