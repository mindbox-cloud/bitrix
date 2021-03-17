<?php
/**
 * Created by @copyright QSOFT.
 */

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Mindbox\Ajax;
use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\DTO\V3\Requests\PageRequestDTO;
use Mindbox\Exceptions\MindboxException;
use Mindbox\Helper;
use Mindbox\Options;
use Mindbox\DTO\DTO;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

class BonusHistory extends CBitrixComponent implements Controllerable
{
    protected $actions = [
        'page'
    ];

    private $mindbox;

    public function __construct(CBitrixComponent $component = null)
    {
        parent::__construct($component);

        try {
            if(!Loader::includeModule('mindbox.marketing')) {
                ShowError(GetMessage('MB_BH_MODULE_NOT_INCLUDED', ['#MODULE#' => 'mindbox.marketing']));
                return;
            }
        } catch (LoaderException $e) {
            ShowError(GetMessage('MB_BH_MODULE_NOT_INCLUDED', ['#MODULE#' => 'mindbox.marketing']));
            return;
        }

        $this->mindbox = Options::getConfig();
    }

    public function configureActions()
    {
        return Ajax::configureActions($this->actions);
    }

    public function pageAction($page)
    {
        if (!$this->mindbox) {
            return Ajax::errorResponse('Incorrect module settings');
        }
        $page = intval($page);
        $this->arParams = Ajax::loadParams(self::getName());
        $size = isset($this->arParams['PAGE_SIZE']) ? $this->arParams['PAGE_SIZE'] : 0;

        try {
            $orders = $this->getHistory($page);
            $showMore = count($orders) === intval($size);

            return [
                'type' => 'success',
                'page' => $page,
                'html' => $this->getHtml($orders),
                'more' => $showMore
            ];
        } catch (Mindbox\Exceptions\MindboxException $e) {
            return Ajax::errorResponse('Can\'t load requested page');
        }
    }


    /**
     * @param $page
     * @return array
     * @throws  MindboxException
     */
    public function getHistory($page)
    {
        if (!$this->mindbox) {
            throw new MindboxException('Incorrect module settings');
        }
        $page = intval($page);
        $history = [];
        $mindboxId = $this->getMindboxId();
        if(!$mindboxId) {
            throw new MindboxException(GetMessage('MB_BH_ERROR_MESSAGE'));
        }
        $operation = Options::getOperationName('getBonusPointsHistory');

        $pageDTO = new PageRequestDTO();
        $pageDTO->setItemsPerPage($this->arParams['PAGE_SIZE']);
        $pageDTO->setPageNumber($page);

        $customer = new CustomerRequestDTO();
        $customer->setId('mindboxId', $mindboxId);

        try {
            $response = $this->mindbox->customer()->getBonusPointsHistory($customer, $pageDTO,
                $operation)->sendRequest();
        } catch (Exception $e) {
            throw new MindboxException('Requested page is empty or doesn\'t exist');
        }

        $result = $response->getResult();

        if(!$result->getCustomerActions()) {
            throw new MindboxException('Requested page is empty or doesn\'t exist');
        }


        foreach ($result->getCustomerActions() as $action) {
            foreach ($action->getCustomerBalanceChanges() as $customerBalanceChanges) {
                $comment = $customerBalanceChanges->getField('comment');
                if (empty($comment)) {
                    $type = $customerBalanceChanges->getField('balanceChangeKind')->getField('systemName');
                    $isPositive = (int)$customerBalanceChanges->getField('changeAmount') > 0;
                    $orderId = array_pop($action->getOrder()->getField('ids'));
                    $comment = '';
                    if ($type === 'RetailOrderBonus') {
                        if ($isPositive) {
                            $comment = GetMessage('MB_EARN_POINTS') . $orderId;
                        } else {
                            $comment = GetMessage('MB_RETURN_POINTS') . $orderId;
                        }
                    } elseif ($type === 'RetailOrderPayment') {
                        if ($isPositive) {
                            $comment = GetMessage('MB_SPEND_POINTS') . $orderId;
                        } else {
                            $comment = GetMessage('MB_REFUND_POINTS') . $orderId;
                        }
                    }
                }

                $history[] = [
                    'start' => $this->formatTime($action->getDateTimeUtc()),
                    'size' => $customerBalanceChanges->getChangeAmount(),
                    'name' => $comment,
                    'end' => $this->formatTime($customerBalanceChanges->getExpirationDateTimeUtc())
                ];
            }
        }

        if(!$this->getMindboxId()) {
            return $history;
        }

        $request = $this->mindbox->getClientV3()->prepareRequest('POST',
            Options::getOperationName('getCustomerInfo'),
            new DTO(['customer' => ['ids' => ['mindboxId' => $this->getMindboxId()]]]));

        try {
            $response = $request->sendRequest()->getResult();
            $arBalances = reset($response->getBalances()->getFieldsAsArray());
            $this->arResult['BALANCE'] = [
                'available' => $arBalances['available'],
                'blocked' => $arBalances['blocked']
            ];
        } catch (MindboxClientException $e) {
            throw new MindboxException($e->getMessage());
        }

        return $history;
    }

    public function formatTime($utc)
    {
        return (new DateTime($utc))->format('Y-m-d H:i:s');
    }

    public function executeComponent()
    {


        $_SESSION[self::getName()] = $this->arParams;

        $this->prepareResult();

        $this->includeComponentTemplate();
    }

    public function prepareResult()
    {
        $page = 1;

        try {
            $this->arResult['HISTORY'] = $this->getHistory($page);
        } catch (MindboxException $e) {
            $this->arResult['ERROR'] = GetMessage('MB_BH_ERROR_MESSAGE');
        }
    }

    protected function getHtml($history)
    {
        $html = '';

        foreach ($history as $change) {
            $html .= GetMessage('MB_BH_BALANCE_HTML',
                [
                   '#START#' => $change['start'],
                   '#SIZE#' => $change['size'],
                   '#END#' => $change['end'],
                   '#NAME#' => $change['name']
                ]
            );
        }

        return $html;
    }

    private function getMindboxId()
    {
        global $USER;

        return Helper::getMindboxId($USER->GetID());
    }
}