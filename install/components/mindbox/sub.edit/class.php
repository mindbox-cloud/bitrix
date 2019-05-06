<?php
/**
 * Created by @copyright QSOFT.
 */

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\UserTable;
use Mindbox\Ajax;
use Mindbox\DTO\DTO;
use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\DTO\V3\Requests\SubscriptionRequestDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Exceptions\MindboxUnavailableException;
use Mindbox\Options;
use Mindbox\QueueTable;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

class SubEdit extends CBitrixComponent implements Controllerable
{
    protected $actions = [
        'save'
    ];

    private $userInfo;

    private $mindbox;

    public function __construct(CBitrixComponent $component = null)
    {
        parent::__construct($component);

        try {
            if (!Loader::includeModule('qsoftm.mindbox')) {
                ShowError(GetMessage('MB_SE_MODULE_NOT_INCLUDED', ['#MODULE#' => 'qsoftm.mindbox']));
                return;
            }
        } catch (LoaderException $e) {
            ShowError(GetMessage('MB_SE_MODULE_NOT_INCLUDED', ['#MODULE#' => 'qsoftm.mindbox']));;
            return;
        }

        $this->userInfo = $this->getUser();
        $this->mindbox = Options::getConfig();
    }

    public function configureActions()
    {
        return Ajax::configureActions($this->actions);
    }

    public function saveAction($fields)
    {
        $customer = new CustomerRequestDTO([
            'ids' => ['mindboxId' => $this->userInfo['UF_MINDBOX_ID']],
        ]);

        $subscriptions = [
            new SubscriptionRequestDTO([
                'pointOfContact' => 'Email',
                'isSubscribed' => $fields['SUBSCRIPTIONS']['Email']
            ]),

            new SubscriptionRequestDTO([
                'pointOfContact' => 'Sms',
                'isSubscribed' => $fields['SUBSCRIPTIONS']['Sms']
            ]),
        ];

        $customer->setSubscriptions($subscriptions);

        try {
            $this->mindbox->customer()->edit($customer, Options::getOperationName('edit'))->sendRequest();
        } catch (MindboxUnavailableException $e) {
            $lastResponse = $this->mindbox->customer()->getLastResponse();

            if ($lastResponse) {
                $request = $lastResponse->getRequest();
                QueueTable::push($request);
            }
        } catch (MindboxClientException $e) {
            return Ajax::errorResponse(GetMessage('MB_SE_ERROR_SUB'));
        }

        return [
            'type' => 'success',
            'message' => GetMessage('MB_SE_SUCCESS_SUB')
        ];
    }

    protected function getSubscriptions()
    {
        $subscriptions = ['Email' => false, 'Sms' => false];

        $request = $this->mindbox->getClientV3()->prepareRequest('POST', Options::getOperationName('getSubscriptions'),
            new DTO(['customer' => ['email' => $this->userInfo['EMAIL']]]));

        try {
            $response = $request->sendRequest()->getResult();
            foreach ($response->getCustomer()->getSubscriptions() as $subscription) {
                $pointOfContact = $subscription->getPointOfContact();
                $isSubscribed = $subscription->getIsSubscribed();
                if ($isSubscribed) {
                    $subscriptions[$pointOfContact] = true;
                }
            }
        } catch (MindboxClientException $e) {
        }

        $this->arResult['SUBSCRIPTIONS'] = $subscriptions;
    }

    protected function getUser()
    {
        global $USER;

        $rsUser = UserTable::getList(
            [
                'select' => ['UF_MINDBOX_ID', 'EMAIL'],
                'filter' => ['ID' => $USER->GetID()]
            ]
        )->fetch();

        return $rsUser;
    }

    public function executeComponent()
    {
        parent::executeComponent();
        $this->getSubscriptions();
        $this->includeComponentTemplate();
    }
}