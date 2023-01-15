<?php

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Helper;
use Mindbox\Options;
use Mindbox\Ajax;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

class AuthSms extends CBitrixComponent implements Controllerable
{
    protected $actions = [
        'sendCode',
        'resend',
        'checkCode',
        'fillup',
        'captchaUpdate'
    ];

    private $user;
    private $mindbox;

    public function __construct(CBitrixComponent $component = null)
    {
        parent::__construct($component);

        try {
            if (!Loader::includeModule('mindbox.marketing')) {
                ShowError(GetMessage('MODULE_NOT_INCLUDED', ['#MODULE#' => 'mindbox.marketing']));

                return;
            }
        } catch (LoaderException $e) {
            ShowError(GetMessage('MB_AUS_MODULE_NOT_INCLUDED', ['#MODULE#' => 'mindbox.marketing']));

            return;
        }

        $this->mindbox = Options::getConfig();
        $this->user = new CustomerRequestDTO();
    }

    public function configureActions()
    {
        return Ajax::configureActions($this->actions);
    }

    public function sendCodeAction($phone)
    {
        if (!$this->mindbox) {
            return Ajax::errorResponse(GetMessage('MB_AUS_BAD_MODULE_SETTING'));
        }
        $phone = htmlspecialcharsEx(trim($phone));
        $this->user->setMobilePhone(Helper::formatPhone($phone));

        try {
            $response = $this->mindbox->customer()->sendAuthorizationCode(
                $this->user,
                Options::getOperationName('sendAuthorizationCode')
            )->sendRequest();
        } catch (MindboxClientException $e) {
            return Ajax::errorResponse(GetMessage('MB_AUS_AUTH_UNAVAILABLE'));
        }

        $validationErrors = $response->getValidationErrors();
        if (!empty($validationErrors)) {
            return Ajax::errorResponse(reset($validationErrors)->getMessage());
        }

        if ($response->getResult()->getCustomer()->getProcessingStatus() !== 'Found') {
            return Ajax::errorResponse(GetMessage('MB_AUS_USER_NOT_FOUND'));
        }

        return [
            'type' => 'success'
        ];
    }


    public function resendAction($phone)
    {
        if (!$this->mindbox) {
            return Ajax::errorResponse(GetMessage('MB_AUS_BAD_MODULE_SETTING'));
        }
        if (empty($phone)) {
            return Ajax::errorResponse('empty phone');
        }

        $this->user->setMobilePhone(Helper::formatPhone($phone));

        try {
            $this->mindbox->customer()->sendAuthorizationCode(
                $this->user,
                Options::getOperationName('sendAuthorizationCode')
            )->sendRequest();
        } catch (MindboxClientException $e) {
            return Ajax::errorResponse($e);
        }

        return [
            'type' => 'success'
        ];
    }

    public function checkCodeAction($code, $phone)
    {
        if (!$this->mindbox) {
            return Ajax::errorResponse(GetMessage('MB_AUS_BAD_MODULE_SETTING'));
        }
        global $USER;

        $code = htmlspecialcharsEx(trim($code));
        $phone = htmlspecialcharsEx(trim($phone));
        $phone = Helper::formatPhone($phone);

        if (empty($code)) {
            return Ajax::errorResponse(GetMessage('MB_AUS_EMPTY_CODE'));
        }

        $customerDto = new CustomerRequestDTO(['mobilePhone' => $phone]);

        try {
            $checkCodeResponse = $this->mindbox->customer()->checkAuthorizationCode(
                $customerDto,
                $code,
                Options::getOperationName('checkAuthorizationCode')
            )->sendRequest();

            $validationErrors = $checkCodeResponse->getValidationErrors();

            if (!empty($validationErrors)) {
                return Ajax::errorResponse(reset($validationErrors)->GetMessage());
            }

            $user = $checkCodeResponse->getResult()->getCustomer();
            $userEmail = $user->getField('email');
            $userFirstName = $user->getField('firstName');
            $userLastName = $user->getField('lastName');
            $userMobilePhone = $user->getField('mobilePhone');
            $userBirthDate = $user->getField('birthDate');
            $userSex = $user->getField('sex');

            if ($user->getProcessingStatus() !== 'Found') {
                return Ajax::errorResponse(GetMessage('MB_AUS_USER_NOT_FOUND'));
            }

            if (empty($userEmail) ||
                empty($userFirstName) ||
                empty($userLastName)
            ) {
                $_SESSION['NEW_USER_MB_ID'] = $user->getId('mindboxId');

                return [
                    'type'        => 'fillup',
                    'mobilePhone' => $userMobilePhone,
                    'firstName'   => $userFirstName,
                    'lastName'    => $userLastName,
                    'email'       => $userEmail,
                    'birthDate'   => date('d.m.Y', strtotime($userBirthDate)),
                    'sex'         => $userSex
                ];
            }

            $arFilter = [
                [
                    "LOGIC" => "OR",
                    [
                        'UF_MINDBOX_ID' => $user->getId('mindboxId')
                    ],
                ]
            ];

            if (isset($getMobilePhone) && !empty($getMobilePhone)) {
                $arFilter = array_merge($arFilter, [
                    [
                        "PERSONAL_PHONE" => $getMobilePhone
                    ],
                    [
                        "PERSONAL_PHONE" => $this->preparePhoneNumber($getMobilePhone)
                    ],
                    [
                        'PERSONAL_MOBILE' => $getMobilePhone
                    ],
                    [
                        'PERSONAL_MOBILE' => $this->preparePhoneNumber($getMobilePhone)
                    ],
                ]);
            }

            if (!empty($getEmail)) {
                $arFilter = array_merge($arFilter, [
                    [
                        'EMAIL' => $getEmail
                    ]
                ]);
            }

            $dbUser = Bitrix\Main\UserTable::getList(
                [
                    'filter' => $arFilter
                ]
            );

            if ($bxUser = $dbUser->fetch()) {
                $fields = [
                    'UF_MINDBOX_ID' => $user->getId('mindboxId')
                ];

                $user = new \CUser;
                $user->Update(
                    $bxUser['ID'],
                    $fields
                );

                $USER->Authorize($bxUser['ID']);

                return [
                    'type'    => 'success',
                    'message' => GetMessage('MB_AUS_SUCCESS')
                ];
            } else {
                $_SESSION['NEW_USER_MB_ID'] = $user->getId('mindboxId');
                $firstName = $user->getField('firstName');
                $lastName = $user->getField('lastName');
                $email = $user->getField('email');
                $context = \Bitrix\Main\Application::getInstance()->getContext();
                $siteId = $context->getSite();
                $password = randString(10);
                $mobilePhone = $user->getField('mobilePhone');
                $birthDate = $user->getField('birthDate');
                $sex = $user->getField('sex');

                if (empty($email)) {
                    $email = $mobilePhone . '@no-reply.com';
                }

                $arFields = [
                    'NAME'             => $firstName,
                    'LAST_NAME'        => $lastName,
                    'EMAIL'            => $email,
                    'LOGIN'            => $mobilePhone,
                    'PERSONAL_PHONE'   => $this->preparePhoneNumber($mobilePhone),
                    'PHONE_NUMBER'     => $this->preparePhoneNumber($mobilePhone),
                    'PERSONAL_GENDER'  => ($sex == 'female') ? 'F' : 'M',
                    'LID'              => $siteId,
                    'ACTIVE'           => 'Y',
                    'PASSWORD'         => $password,
                    'CONFIRM_PASSWORD' => $password,
                    'UF_MINDBOX_ID'    => $user->getId('mindboxId')
                ];

                if (!empty($birthDate)) {
                    global $DB;
                    $siteDateFormat = $DB->DateFormatToPHP(\CSite::GetDateFormat('SHORT'));
                    $arFields['PERSONAL_BIRTHDAY'] = date($siteDateFormat, strtotime($birthDate));
                }

                $createUserId = $USER->Add($arFields);

                if (intval($createUserId) > 0) {
                    $USER->Authorize($createUserId);

                    return [
                        'type'    => 'success',
                        'message' => GetMessage('MB_AUS_SUCCESS')
                    ];
                } else {
                    return Ajax::errorResponse(GetMessage('MB_AUS_REG_UNAVAILABLE'));
                }
            }
        } catch (MindboxClientException $e) {

            return Ajax::errorResponse(GetMessage('MB_AUS_REG_UNAVAILABLE'));
        }
    }

    protected function preparePhoneNumber($number)
    {
        if (strpos($number, '+') === false) {
            $number = '+' . $number;
        }

        return $number;
    }

    public function fillupAction($fields)
    {
        if (!$this->mindbox) {
            return Ajax::errorResponse(GetMessage('MB_AUS_BAD_MODULE_SETTING'));
        }
        global $USER;

        foreach ($fields as $key => $value) {
            $fields[$key] = htmlspecialcharsEx(trim($value));
        }

        $fields['PERSONAL_PHONE'] = Helper::formatPhone($fields['PERSONAL_PHONE']);
        $customer = new CustomerRequestDTO(
            [
                'email'       => $fields['EMAIL'],
                'lastName'    => $fields['LAST_NAME'],
                'firstName'   => $fields['NAME'],
                'mobilePhone' => $fields['PERSONAL_PHONE'],
                'birthDate'   => $fields['PERSONAL_BIRTHDAY'],
                'sex'         => $fields['PERSONAL_GENDER']
            ]
        );

        $mindboxId = $_SESSION['NEW_USER_MB_ID'];

        if (!$mindboxId) {
            return [
                'type'    => 'error',
                'message' => GetMessage('MB_AUS_USER_NOT_FOUND')
            ];
        }


        if ($fields['EMAIL'] !== $fields['EMAIL_ORIGINAL']) {
            if (Helper::isCustomerExist($fields['EMAIL'])) {
                return [
                    'type'    => 'error',
                    'message' => GetMessage('MB_AUS_USER_ALREADY_EXISTS')
                ];
            }
        }

        $customer->setId('mindboxId', $mindboxId);

        $_SESSION['OFFLINE_REGISTER'] = true;

        $arFilter = [
            [
                "LOGIC" => "OR",
                [
                    "UF_MINDBOX_ID" => $mindboxId
                ],
                [
                    "EMAIL" => $fields['EMAIL']
                ]
            ]
        ];

        $dbUser = Bitrix\Main\UserTable::getList([
            'filter' => $arFilter
        ]);

        if ($bxUser = $dbUser->fetch()) {
            $fields['UF_MINDBOX_ID'] = $mindboxId;
            if ($fields['PERSONAL_GENDER'] == 'female') {
                $fields['PERSONAL_GENDER'] = 'F';
            } else {
                $fields['PERSONAL_GENDER'] = 'M';
            }

            if (!empty($fields['PERSONAL_BIRTHDAY'])) {
                global $DB;
                $siteDateFormat = $DB->DateFormatToPHP(\CSite::GetDateFormat('SHORT'));
                $fields['PERSONAL_BIRTHDAY'] = date($siteDateFormat, strtotime($fields['PERSONAL_BIRTHDAY']));
            }

            $USER->Authorize($bxUser['ID']);

            $user = new \CUser;
            $user->Update(
                $USER->GetID(),
                $fields
            );

            $strError = $user->LAST_ERROR;

            try {
                $registerResponse = $this->mindbox->customer()->edit(
                    $customer,
                    Options::getOperationName('edit')
                )->sendRequest();
            } catch (MindboxClientException $e) {
                return Ajax::errorResponse($e);
            }
            if ($errors = $registerResponse->getValidationErrors()) {
                $errors = $this->parseValidtaionErrors($errors);

                return [
                    'type'   => 'validation errors',
                    'errors' => $errors
                ];
            }

            return [
                'type'    => 'success',
                'message' => GetMessage('MB_AUS_SUCCESS')
            ];
        } else {
            if (!$fields['PASSWORD'] ||
                !$fields['CONFIRM_PASSWORD']
            ) {
                $fields['PASSWORD'] = $fields['CONFIRM_PASSWORD'] = \Bitrix\Main\Authentication\ApplicationPasswordTable::generatePassword();
            }

            $reg = $USER->Register(
                $fields['EMAIL'],
                $fields['NAME'],
                $fields['LAST_NAME'],
                $fields['PASSWORD'],
                $fields['CONFIRM_PASSWORD'],
                $fields['EMAIL'],
                false,
                $fields['captcha_word'],
                $fields['captcha_sid']
            );

            if ($reg['TYPE'] !== 'OK') {
                return Ajax::errorResponse($reg['MESSAGE']);
            }

            $fields = [
                'UF_MINDBOX_ID' => $mindboxId
            ];

            $user = new \CUser;
            $user->Update(
                $USER->GetID(),
                $fields
            );

            try {
                $registerResponse = $this->mindbox->customer()->edit(
                    $customer,
                    Options::getOperationName('edit')
                )->sendRequest();
            } catch (MindboxClientException $e) {
                return Ajax::errorResponse($e);
            }
            if ($errors = $registerResponse->getValidationErrors()) {
                $errors = $this->parseValidtaionErrors($errors);

                return [
                    'type'   => 'validation errors',
                    'errors' => $errors
                ];
            }
        }

        return ['type' => 'success'];
    }

    public function captchaUpdateAction()
    {
        global $APPLICATION;

        return ['captcha_sid' => htmlspecialcharsbx($APPLICATION->CaptchaGetCode())];
    }

    public function executeComponent()
    {
        $this->getCaptcha();
        $this->includeComponentTemplate();
    }

    protected function getCaptcha()
    {
        global $APPLICATION;
        if (COption::GetOptionString("main", "captcha_registration", "N") == "Y") {
            $this->arResult["USE_CAPTCHA"] = "Y";
            $this->arResult["CAPTCHA_CODE"] = htmlspecialcharsbx($APPLICATION->CaptchaGetCode());
        } else {
            $this->arResult["USE_CAPTCHA"] = "N";
        }
    }

    protected function parseValidtaionErrors($errors)
    {
        $parsedErrors = [];
        foreach ($errors as $error) {
            $parsedErrors[] = [
                'location' => $error->getLocation(),
                'message'  => $error->getMessage()
            ];
        }

        return $parsedErrors;
    }
}
