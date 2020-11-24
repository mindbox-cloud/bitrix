<?php
/**
 * Created by @copyright QSOFT.
 */

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Mindbox\DTO\V3\Requests\CustomerIdentityRequestCollection;
use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\Ajax;
use Mindbox\DTO\V3\Requests\MergeCustomersRequestDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Exceptions\MindboxUnavailableException;
use Mindbox\Helper;
use Mindbox\Options;
use Mindbox\QueueTable;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

class DiscountCard extends CBitrixComponent implements Controllerable
{
    protected $actions = [
        'sendCard',
        'sendCode',
        'resend'
    ];

    private $mindbox;

    public function __construct(CBitrixComponent $component = null)
    {
        parent::__construct($component);

        try {
            if (!Loader::includeModule('mindbox.marketing')) {
                ShowError(GetMessage('MB_DC_MODULE_NOT_INCLUDED', ['#MODULE#' => 'mindbox.marketing']));
                return;
            }
        } catch (LoaderException $e) {
            ShowError(GetMessage('MB_DC_MODULE_NOT_INCLUDED', ['#MODULE#' => 'mindbox.marketing']));
            return;
        }

        $this->mindbox = Options::getConfig();
    }

    public function configureActions()
    {
        return Ajax::configureActions($this->actions);
    }


    public function sendCardAction($card)
    {
        if (!$this->mindbox) {
            return Ajax::errorResponse(GetMessage('MB_DC_BAD_MODULE_SETTING'));
        }
        $card = htmlspecialcharsEx(trim($card));
        if (empty($card)) {
            return Ajax::errorResponse(GetMessage('MB_DC_CARD_NOT_FOUND'));
        }

        $customerDto = new CustomerRequestDTO([
            'discountCard' => [
                'ids' => ['number' => $card]
            ]
        ]);
        try {
            $response = $this->mindbox->customer()->getDataByDiscountCard($customerDto,
                Options::getOperationName('getDataByDiscountCard'))->sendRequest();
        } catch (MindboxClientException $e) {
            return Ajax::errorResponse(GetMessage('MB_DC_CARD_ERROR'));
        }
        $customer = $response->getResult()->getCustomer();

        if (!$customer) {
            return [
                'type' => 'warning',
                'message' => GetMessage('MB_DC_CARD_NOT_FOUND')
            ];
        }

        if ($customer->getProcessingStatus() === 'NotFound') {
            return [
                'type' => 'warning',
                'message' => GetMessage('MB_DC_CARD_NOT_FOUND')
            ];
        }

        $phone = $customer->getMobilePhone();
        if (empty($phone)) {
            return [
                'type' => 'warning',
                'message' => GetMessage('MB_DC_PHONE_NOT_FOUND')
            ];
        }

        try {
            $this->mindbox->customer()->sendAuthorizationCode(new CustomerRequestDTO(['ids' => ['mindboxId' => $this->getMindboxId()]]),
                Options::getOperationName('sendAuthorizationCode'))->sendRequest();
        } catch (MindboxClientException $e) {
            return Ajax::errorResponse(GetMessage('MB_DC_CARD_ERROR'));
        }

        return [
            'type' => 'success',
            'phone' => $phone
        ];
    }


    public function resendAction($phone)
    {
        if (!$this->mindbox) {
            return Ajax::errorResponse(GetMessage('MB_DC_BAD_MODULE_SETTING'));
        }
        $phone = htmlspecialcharsEx(trim($phone));

        $customerDto = new CustomerRequestDTO(['mobilePhone' => $phone]);
        try {
            $this->mindbox->customer()->sendAuthorizationCode($customerDto,
                Options::getOperationName('sendAuthorizationCode'))->sendRequest();
        } catch (MindboxUnavailableException $e) {
            $lastResponse = $this->mindbox->customer()->getLastResponse();

            if ($lastResponse) {
                $request = $lastResponse->getRequest();
                QueueTable::push($request);
            }
        } catch (MindboxClientException $e) {
            return Ajax::errorResponse($e);
        }

        $_SESSION['LAST_TRY'] = time();
        return [
            'type' => 'success',
            'time' => $_SESSION['LAST_TRY']
        ];
    }

    public function sendCodeAction($code, $phone)
    {
        if (!$this->mindbox) {
            return Ajax::errorResponse(GetMessage('MB_DC_BAD_MODULE_SETTING'));
        }
        $code = htmlspecialcharsEx(trim($code));
        $phone = htmlspecialcharsEx(trim($phone));

        $customerDto = new CustomerRequestDTO(['mobilePhone' => $phone]);
        try {
            $response = $this->mindbox->customer()->checkAuthorizationCode($customerDto, $code,
                Options::getOperationName('checkAuthorizationCode'))->sendRequest();
        } catch (MindboxClientException $e) {
            return Ajax::errorResponse(GetMessage('MB_DC_CARD_ERROR'));
        }

        $customer = $response->getResult()->getCustomer();
        if (!$customer) {
            return Ajax::errorResponse(GetMessage('MB_DC_WRONG_CODE'));
        }
        if ($customer->getProcessingStatus() === 'Found') {
            $customerDto = new CustomerRequestDTO();
            $customerDto->setId('mindboxId', $customer->getId('mindboxId'));
            $resultingCustomer = new CustomerRequestDTO(['ids' => ['mindboxId' => $this->getMindboxId()]]);
            $customersToMerge = new CustomerIdentityRequestCollection([$customerDto]);
            try {
                $this->mindbox->customer()->merge(new MergeCustomersRequestDTO([
                    'customersToMerge' => $customersToMerge,
                    'resultingCustomer' => $resultingCustomer
                ]), Options::getOperationName('merge'))->sendRequest();
            } catch (MindboxUnavailableException $e) {
                $lastResponse = $this->mindbox->customer()->getLastResponse();

                if($lastResponse) {
                    $request = $lastResponse->getRequest();
                    QueueTable::push($request);
                }
            } catch (MindboxClientException $e) {
                return Ajax::errorResponse(GetMessage('MB_DC_CARD_ERROR'));
            }

            $url = $this->arParams['PERSONAL_PAGE_URL'];

            return [
                'type' => 'success',
                'url' => $url,
                'message' => GetMessage('MB_DC_CARD_SUCCESS')
            ];
        } else {
            return Ajax::errorResponse(GetMessage('MB_DC_WRONG_CODE'));
        }

    }

    public function onPrepareComponentParams($arParams)
    {
        $arParams['PERSONAL_PAGE_URL'] = trim($arParams['PERSONAL_PAGE_URL']);

        return $arParams;
    }

    public function executeComponent()
    {
        $this->prepareResult();

        $this->includeComponentTemplate();
    }

    private function getMindboxId()
    {
        global $USER;

        return Helper::getMindboxId($USER->GetID());
    }

    private function prepareResult()
    {
        $this->arResult['LAST_TRY'] = $_SESSION['LAST_TRY'];
    }
}