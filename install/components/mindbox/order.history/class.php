<?php
/**
 * Created by @copyright QSOFT.
 */

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Helper;
use Mindbox\Options;
use Mindbox\Ajax;

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
                ;
                return;
            }

            if (!Loader::includeModule('catalog')) {
                ShowError(GetMessage('MODULE_NOT_INCLUDED', ['#MODULE#' => 'catalog']));
                ;
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

        $orders = $this->getOrders($page);
        $showMore = count($orders) === intval($size);

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
        $page = intval($page);

        list($start, $range) = $this->getInterval($page);
        $mindboxId = $this->getMindboxId();
        $operation = 'DirectCrm.V21CustomerOrderListOperation';
        $transactionId = Options::getModuleOption('TRANSACTION_ID');


        try {
            $queryParams = [
                'startingIndex' =>  $start,
                'countToReturn' =>  $range,
                'mindbox'   =>  $mindboxId,
                'orderLineStatuses' =>  ''
            ];
            $response = $this->mindbox->getClientV2()
                ->prepareRequest('GET', $operation, null, 'by-customer', $queryParams)
                ->sendRequest();

            $ordersDTO = $response->getResult()->getOrders();


            foreach ($ordersDTO as $order) {
                $id = $order->getId('mindbox');
                if (empty($id)) {
                    continue;
                }

                $orders[$id] = [
                    'id' => $order->getId($transactionId),
                    'created' => $order->getCreatedDateTimeUtc()
                ];

                $acuiredBonuses = 0;
                $spentBonuses = 0;
                $lines = $order->getLines();
                foreach ($lines as $line) {
                    $arSku = $line->getField('sku');
                    $product = $this->getProductById($arSku['skuId']);
                    $orders[$id]['lines'][] = [
                        'name' => $product['NAME'],
                        'link' => $product['DETAIL_PAGE_URL'],
                        'price' => $line->getField('discountedPrice')
                    ];
                    foreach ($line->getAppliedDiscounts() as $discount) {
                        if ($discount->getType() === 'balance') {
                            $spentBonuses += intval($discount->getAmount());
                        }
                    }
                }

                $acuiredBonuses += intval($order->getTotalAcquiredBalanceChange());

                $deliveryCost = $order->getField('deliveryCost');

                $orders[$id]['spentBonuses'] = $spentBonuses;
                $orders[$id]['acuiredBonuses'] = $acuiredBonuses;
                $orders[$id]['deliveryCost'] = $deliveryCost;
            }
        } catch (MindboxClientException $e) {
            $orders = [];
        }


        return $orders;
    }

    public function onPrepareComponentParams($arParams)
    {
        $arParams['PAGE_SIZE'] = intval($arParams['PAGE_SIZE']) ? : 0;
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

        $this->arResult['ORDERS'] = $this->getOrders($page);
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
            $html .= GetMessage('ORDER_HEADER', ['#ID#' => $order['id'], '#CREATED#' => $order['created'] ]);
            $html .= GetMessage('ORDER_START_HEAD');
            if ($order['spentBonuses']) {
                $html .= GetMessage('ORDER_SPENT', ['#SPENT#' => $order['spentBonuses'], '#END#' => Helper::getNumEnding(
                    $order['spentBonuses'],
                    GetMessage('ENDINGS_ARRAY')
                )]);
            }

            if ($order['acuiredBonuses']) {
                $html .=  ' ' . GetMessage('ORDER_ACUIRED', ['#ACUIRED#' => $order['acuiredBonuses'],'#END#' => Helper::getNumEnding(
                    $order['acuiredBonuses'],
                    GetMessage('ENDINGS_ARRAY')
                )]);
            }

            $html .= GetMessage('ORDER_END_HEAD');
            $html .= GetMessage('ORDER_START_TABLE');

            foreach ($order['lines'] as $line) {
                $html .= GetMessage('ORDER_LINE', ['#LINK#' => $line['link'], '#NAME#' => $line['name'], '#PRICE#' => $line['price']]);
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
