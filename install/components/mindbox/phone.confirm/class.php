<?php
/**
 * Created by @copyright QSOFT.
 */

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\UserTable;
use Mindbox\Ajax;
use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\DTO\V3\Requests\SmsConfirmationRequestDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Exceptions\MindboxException;
use Mindbox\Options;
use Mindbox\DTO\DTO;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

class PhoneConfirm extends CBitrixComponent implements Controllerable
{
    protected $actions = [
        'checkCode',
        'resendCode',
    ];

    private $userInfo;

    private $mindbox;

    public function __construct(CBitrixComponent $component = null)
    {
        parent::__construct($component);

        try {
            if(!Loader::includeModule('qsoftm.mindbox')) {
                ShowError(GetMessage('MB_PC_MODULE_NOT_INCLUDED', ['#MODULE#' => 'qsoftm.mindbox']));
                return;
            }
        } catch (LoaderException $e) {
            ShowError(GetMessage('MB_PC_MODULE_NOT_INCLUDED', ['#MODULE#' => 'qsoftm.mindbox']));;
            return;
        }

        $this->userInfo = $this->getUser();
        if (!$this->userInfo['UF_PHONE_CONFIRMED'] && !empty($this->userInfo['PERSONAL_PHONE'])) {
            $this->mindbox = Options::getConfig();
        }
    }

    public function executeComponent()
    {
        $this->arResult['USER_INFO']['UF_PHONE_CONFIRMED'] = $this->userInfo['UF_PHONE_CONFIRMED'];
        $this->arResult['USER_INFO']['PERSONAL_PHONE'] = $this->userInfo['PERSONAL_PHONE'];
        $this->includeComponentTemplate();
    }


    public function configureActions()
    {
        return Ajax::configureActions($this->actions);
    }

    public function checkCodeAction($code)
    {
        if (!$this->mindbox) {
            return Ajax::errorResponse(GetMessage('MB_PC_BAD_MODULE_SETTING'));
        }

        $code = htmlspecialcharsEx(trim($code));
        if (!$code) {
            return Ajax::errorResponse(GetMessage('MB_PC_EMPTY'));
        }

        global $USER;
		$customer = new CustomerRequestDTO(['ids' => ['mindboxId' => $this->userInfo['UF_MINDBOX_ID']]]);
        $sms = new SmsConfirmationRequestDTO(['code' => $code]);

        try {
            $check = $this->mindbox->customer()
                ->confirmMobile($customer, $sms, Options::getOperationName('confirmMobile'))->sendRequest();
        } catch (MindboxClientException $e) {
            return Ajax::errorResponse(GetMessage('MB_PC_ERROR_CONFIRM'));
        }

        $confirmation = $check->getResult()->getSmsConfirmation();
        if(!$confirmation) {
            return Ajax::errorResponse(GetMessage('MB_PC_EMPTY'));
        }
        $status = $confirmation->getProcessingStatus();

        if ($status === 'MobilePhoneConfirmed') {
            $USER->Update($this->userInfo['ID'], ['UF_PHONE_CONFIRMED' => '1']);
            return [
                'type' => 'success',
                'message' => GetMessage('MB_PC_SUCCESS_CONFIRM')
            ];
        }

        return [
            'type' => 'error',
            'message' => GetMessage('MB_PC_INCORRECT_CODE')
        ];
    }

    public function resendCodeAction()
    {
        if (!$this->mindbox) {
            return Ajax::errorResponse(GetMessage('MB_PC_BAD_MODULE_SETTING'));
        }
        $customer = new CustomerRequestDTO(['ids' => ['mindboxId' => $this->userInfo['UF_MINDBOX_ID']]]);
        try {
            $this->mindbox->customer()->resendConfirmationCode($customer,
                Options::getOperationName('resendConfirmationCode'))->sendRequest();
        } catch (MindboxClientException $e) {
            return Ajax::errorResponse($e);
        }
    }

    protected function getUser()
    {
        global $USER;

        $rsUser = UserTable::getList(
            [
                'select' => ['PERSONAL_PHONE', 'UF_MINDBOX_ID', 'UF_PHONE_CONFIRMED', 'ID'],
                'filter' => ['ID' => $USER->GetID()]
            ]
        )->fetch();

        $mindbox = Options::getConfig();
		$request = $mindbox->getClientV3()->prepareRequest('POST',
			Options::getOperationName('getCustomerInfo'),
			new DTO([
				'customer' => [
					'ids' => [
						'mindboxId' => $rsUser['UF_MINDBOX_ID']
					]
				]
			]));

		try {
			$response = $request->sendRequest()->getResult();
		} catch (MindboxClientException $e) {
			return $rsUser;
		}

		$customer = $response->getCustomer();
		if ($customer && $customer->getProcessingStatus() === 'Found') {
		    $pending = $customer->getPendingMobilePhone();
		    if($pending) {
                $rsUser['UF_PHONE_CONFIRMED'] = false;
            } else {
                $rsUser['UF_PHONE_CONFIRMED'] = $customer->getIsMobilePhoneConfirmed();
            }
		}

        return $rsUser;
    }
}