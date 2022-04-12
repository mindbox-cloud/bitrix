<?php
/**
 * Created by @copyright QSOFT.
 */

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Exceptions\MindboxException;
use Mindbox\Helper;
use Mindbox\Options;
use Mindbox\Ajax;
use Mindbox\DTO\V3\Requests\PageRequestDTO;
use Mindbox\DTO\V3\Requests\CustomerRequestDTO as CustomerRequestDTOV3;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

class OrderHistory extends CBitrixComponent implements Controllerable
{
    protected $actions = [
        'page'
    ];

    private $mindbox;

    public function __construct(CBitrixComponent $component = null)
    {
        parent::__construct($component);

        try {
            if (!Loader::includeModule('mindbox.marketing')) {
                ShowError(GetMessage('MODULE_NOT_INCLUDED', ['#MODULE#' => 'mindbox.marketing']));

                return;
            }

            if (!Loader::includeModule('catalog')) {
                ShowError(GetMessage('MODULE_NOT_INCLUDED', ['#MODULE#' => 'catalog']));
                die();
            }
        } catch (LoaderException $e) {
            ShowError($e->getMessage());

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
        $page = intval($page);
        $this->arParams = Ajax::loadParams(self::getName());
        $size = isset($this->arParams['PAGE_SIZE']) ? $this->arParams['PAGE_SIZE'] : 0;

        try {
            $orders = $this->getOrders($page);
            $showMore = count($orders) === intval($size);
        } catch (MindboxException $e) {
            $this->arResult['ERROR'] = $e->getMessage();
        }

        return [
            'type' => 'success',
            'page' => $page,
            'html' => $this->getHtml($orders),
            'more' => $showMore
        ];
    }

    public function getInterval($page)
    {
        $page = intval($page);

        $range = $this->arParams['PAGE_SIZE'];
        $start = ($page - 1) * $range;

        return [$start, $range];
    }

    /**
     * @param $page
     * @return array
     */
    public function getOrders($page)
    {
        global $USER;
        if (!$USER->IsAuthorized()) {
            throw new MindboxException(GetMessage('MODULE_NOT_INCLUDED'));
        }

        $mindboxId = $this->getMindboxId();
        if (!$mindboxId) {
            throw new MindboxException(GetMessage('ORDER_ERROR_MESSAGE'));
        }

        $page = intval($page);
        $transactionId = Options::getModuleOption('TRANSACTION_ID');
        $externalSystemId = Options::getModuleOption('EXTERNAL_SYSTEM');

        $operation = Options::getOperationName('getOrdersList');

        $pageDTO = new PageRequestDTO();
        $pageDTO->setItemsPerPage($this->arParams['PAGE_SIZE']);
        $pageDTO->setPageNumber($page);

        $customer = new CustomerRequestDTOV3();
        $customer->setId('mindboxId', $mindboxId);

        try {
            $response = $this->mindbox->customer()->getOrdersList(
                $customer,
                $pageDTO,
                $operation
            )->sendRequest();
        } catch (Exception $e) {
            throw new MindboxException(GetMessage('ORDER_ERROR_MESSAGE'));
        }


        $result = $response->getResult();
        $orders = [];
        $ordersDTO = $result->getOrders();

        foreach ($ordersDTO as $order) {
            $id = $order->getId('mindboxId');

            if (empty($id)) {
                continue;
            }

            $orders[$id] = [
                'id'      => $order->getId($transactionId)?? $id,
                'created' => $order->getField('createDateTimeUtc')
            ];

            $acuiredBonuses = 0;
            $spentBonuses = 0;
            $lines = $order->getLines();

            foreach ($lines as $line) {
                $productId = $line->getProduct()->getId($externalSystemId);
                $product = $this->getProductById($productId);
                $orders[$id]['lines'][] = [
                    'name'  => $product['NAME'],
                    'link'  => $product['DETAIL_PAGE_URL'],
                    'price' => $line->getPriceOfLine()
                ];
                foreach ($line->getAppliedPromotions() as $discount) {
                    if ($discount->getType() === 'balance') {
                        $spentBonuses += intval($discount->getAmount());
                    } elseif ($discount->getType() === 'earnedBonusPoints') {
                        $acuiredBonuses += intval($discount->getAmount());
                    }
                }
            }

            $deliveryCost = $order->getField('deliveryCost');

            $orders[$id]['spentBonuses'] = $spentBonuses;
            $orders[$id]['acuiredBonuses'] = $acuiredBonuses;
            $orders[$id]['deliveryCost'] = $deliveryCost;
        }

        return $orders;
    }

    public function onPrepareComponentParams($arParams)
    {
        $arParams['PAGE_SIZE'] = intval($arParams['PAGE_SIZE']) ?: 0;

        return $arParams;
    }

    public function executeComponent()
    {


        $_SESSION[self::getName()] = $this->arParams;

        $this->prepareResult();

        $this->includeComponentTemplate();
    }

    public function prepareResult()
    {
        $page = $_REQUEST['page'] ?: 1;
        try {
            $this->arResult['ORDERS'] = $this->getOrders($page);
        } catch (MindboxException $e) {
            $this->arResult['ERROR'] = $e->getMessage();
        }
    }

    protected function getProductById($id)
    {
        $res = CIBlockElement::GetByID($id);
        if ($product = $res->GetNext()) {
            return $product;
        }

        return false;
    }

    protected function getHtml($orders)
    {
        $html = '';
        foreach ($orders as $order) {
            $html .= GetMessage('ORDER_HEADER', ['#ID#' => $order['id'], '#CREATED#' => $order['created']]);
            $html .= GetMessage('ORDER_START_HEAD');
            if ($order['spentBonuses']) {
                $html .= GetMessage('ORDER_SPENT', [
                    '#SPENT#' => $order['spentBonuses'],
                    '#END#'   => Helper::getNumEnding(
                        $order['spentBonuses'],
                        GetMessage('ENDINGS_ARRAY')
                    )
                ]);
            }

            if ($order['acuiredBonuses']) {
                $html .= ' ' . GetMessage('ORDER_ACUIRED', [
                        '#ACUIRED#' => $order['acuiredBonuses'],
                        '#END#'     => Helper::getNumEnding(
                            $order['acuiredBonuses'],
                            GetMessage('ENDINGS_ARRAY')
                        )
                    ]);
            }

            $html .= GetMessage('ORDER_END_HEAD');
            $html .= GetMessage('ORDER_START_TABLE');

            foreach ($order['lines'] as $line) {
                $html .= GetMessage(
                    'ORDER_LINE',
                    ['#LINK#' => $line['link'], '#NAME#' => $line['name'], '#PRICE#' => $line['price']]
                );
            }

            $html .= GetMessage('ORDER_END_TABLE');
        }

        return $html;
    }


    private function getMindboxId()
    {
        global $USER;

        return Helper::getMindboxId($USER->GetID());
    }
}
