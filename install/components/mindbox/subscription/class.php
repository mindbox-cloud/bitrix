<?php
/**
 * Created by @copyright QSOFT.
 */

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\DTO\V3\Requests\SubscriptionRequestDTO;
use Mindbox\Exceptions\MindboxBadRequestException;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Exceptions\MindboxConflictException;
use Mindbox\Exceptions\MindboxForbiddenException;
use Mindbox\Exceptions\MindboxNotFoundException;
use Mindbox\Exceptions\MindboxTooManyRequestsException;
use Mindbox\Exceptions\MindboxUnauthorizedException;
use Mindbox\Exceptions\MindboxUnavailableException;
use Mindbox\Options;
use Mindbox\Ajax;
use Mindbox\QueueTable;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

class Subscripion extends CBitrixComponent implements Controllerable
{
    protected $actions = [
        'subscribe'
    ];

    private $mindbox;

    public function __construct(CBitrixComponent $component = null)
    {
        parent::__construct($component);

        try {
            if (!Loader::includeModule('qsoftm.mindbox')) {
                ShowError(GetMessage('MB_SU_MODULE_NOT_INCLUDED', ['#MODULE#' => 'qsoftm.mindbox']));;
                return;
            }
        } catch (LoaderException $e) {
            ShowError(GetMessage('MB_SU_MODULE_NOT_INCLUDED', ['#MODULE#' => 'qsoftm.mindbox']));;
            return;
        }

        $this->mindbox = Options::getConfig();
    }

    public function configureActions()
    {
        return Ajax::configureActions($this->actions);
    }

    public function subscribeAction($email)
    {
        if (!$this->mindbox) {
            return Ajax::errorResponse(GetMessage('MB_SU_BAD_MODULE_SETTING'));
        }
        $email = htmlspecialcharsEx(trim($email));
        if (empty($email)) {
            return Ajax::errorResponse('Incorrect email');
        }

        $customer = new CustomerRequestDTO(['email' => $email]);
        $subscripton = new SubscriptionRequestDTO(['pointOfContact' => 'Email']);
        $customer->setSubscriptions([$subscripton]);
        try {
            $this->mindbox->customer()->subscribe($customer,
                Options::getOperationName('subscribe'))->sendRequest();

            return [
                'type' => 'success',
                'message' => GetMessage('MB_SU_SUCCESS')
            ];
        } catch (MindboxClientException $e) {
            $lastResponse = $this->mindbox->customer()->getLastResponse();
            if ($lastResponse) {
                $request = $lastResponse->getRequest();
                QueueTable::push($request);
            }
            return [
                'type' => 'queue',
                'message' => GetMessage('MB_SU_QUEUE')
            ];
        }

    }

    public function executeComponent()
    {
        $this->includeComponentTemplate();
    }
}