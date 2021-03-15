<?php
/**
 * Created by @copyright QSOFT.
 */

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
            ;
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

            if ($user->getProcessingStatus() !== 'Found') {
                return Ajax::errorResponse(GetMessage('MB_AUS_USER_NOT_FOUND'));
            }

            $arFilter = [
                [
                    "LOGIC"=>"OR",
                    [
                        'UF_MINDBOX_ID' => $user->getId('mindboxId')
                    ],
                    [
                        "PERSONAL_PHONE"    =>  $user->getField('mobilePhone')
                    ],
                    [
                        'PERSONAL_MOBILE'   =>  $user->getField('mobilePhone')
                    ],
                    [
                        'EMAIL' =>  $user->getField('email')
                    ]
                ]
            ];
            $dbUser = Bitrix\Main\UserTable::getList(
                [
                    'filter' => $arFilter
                ]
            );
            if ($bxUser = $dbUser->fetch()) {
                $fields = [
                    'UF_MINDBOX_ID'      => $user->getId('mindboxId')
                ];
                $user = new \CUser;
                $user->Update(
                    $bxUser['ID'],
                    $fields
                );
                $USER->Authorize($bxUser['ID']);
                return [
                    'type' => 'success',
                    'message' => GetMessage('MB_AUS_SUCCESS')
                ];
            } else {
                $_SESSION['NEW_USER_MB_ID'] = $user->getId('mindboxId');
                $firstName = $user->getField('firstName');
                $lastName = $user->getField('lastName');
                $email = $user->getField('email');
                $context = \Bitrix\Main\Application::getInstance()->getContext();
                $siteId = $context->getSite();
                $password  = randString(10);
                $mobilePhone = $user->getField('mobilePhone');
                $birthDate = $user->getField('birthDate');
                $sex = $user->getField('sex');

                if (empty($email)) {
                    $email = $mobilePhone . '@no-reply.com';
                }

                $arFields = [
                    "NAME"              => $firstName,
                    "LAST_NAME"         => $lastName,
                    "EMAIL"             => $email,
                    "LOGIN"             => $mobilePhone,
                    'PERSONAL_PHONE'    =>  $mobilePhone,
                    'PHONE_NUMBER'      =>  $mobilePhone,
                    'PERSONAL_BIRTHDAY' =>  date('d.m.Y', strtotime($birthDate)),
                    'PERSONAL_GENDER'   =>  (($sex == 'female')? 'F':'M'),
                    "LID"               => $siteId,
                    "ACTIVE"            => "Y",
                    "PASSWORD"          => $password,
                    "CONFIRM_PASSWORD"  => $password,
                    'UF_MINDBOX_ID'     =>  $user->getId('mindboxId')
                ];

                $ID = $USER->Add($arFields);
                if (intval($ID) > 0) {
                    $USER->Authorize($ID);
                    return [
                        'type' => 'success',
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

    public function fillupAction($fields)
    {
        if (!$this->mindbox) {
            return Ajax::errorResponse(GetMessage('MB_AUS_BAD_MODULE_SETTING'));
        }
        global $USER;
        
        foreach ($fields as $key => $value) {
            $fields[$key] = htmlspecialcharsEx(trim($value));
        }

        $sex = false;
        if ($fields['PERSONAL_GENDER'] === 'M') {
            $sex = 'male';
        }

        if ($fields['PERSONAL_GENDER'] === 'F') {
            $sex = 'female';
        }
        
        $fields['PERSONAL_PHONE'] = Helper::formatPhone($fields['PERSONAL_PHONE']);
        $customer = new CustomerRequestDTO(
            [
                'email' => $fields['EMAIL'],
                'lastName' => $fields['LAST_NAME'],
                'firstName' => $fields['NAME'],
                'mobilePhone' => $fields['PERSONAL_PHONE'],
                'birthDate' => Helper::formatDate($fields['PERSONAL_BIRTHDAY']),
            ]
        );

        if ($sex) {
            $customer->setField('sex', $sex);
        }

        $customer->setId('mindboxId', $_SESSION['NEW_USER_MB_ID']);

        try {
            $registerResponse = $this->mindbox->customer()->fill(
                $customer,
                Options::getOperationName('fill')
            )->sendRequest();
        } catch (MindboxClientException $e) {
            return Ajax::errorResponse($e);
        }
        if ($errors = $registerResponse->getValidationErrors()) {
            $errors = $this->parseValidtaionErrors($errors);

            return [
                'type' => 'validation errors',
                'errors' => $errors
            ];
        }

        $_SESSION['OFFLINE_REGISTER'] = true;
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
                'message' => $error->getMessage()
            ];
        }

        return $parsedErrors;
    }
}
