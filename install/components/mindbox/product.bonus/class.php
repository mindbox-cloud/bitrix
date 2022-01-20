<?php

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use \Bitrix\Main\Data\Cache;
use Mindbox\Ajax;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

class ProductBonus extends CBitrixComponent implements Controllerable
{
    const PLACEHOLDER_PREFIX = 'MINDBOX_BONUS';
    protected $actions = [
        'changeProduct'
    ];

    public function __construct(CBitrixComponent $component = null)
    {
        parent::__construct($component);

        try {
            if (!Loader::includeModule('mindbox.marketing')) {
                ShowError(GetMessage('MODULE_NOT_INCLUDED', ['#MODULE#' => 'mindbox.marketing']));
                return;
            }
        } catch (LoaderException $e) {
            ShowError(GetMessage('MB_AUS_MODULE_NOT_INCLUDED', ['#MODULE#' => 'mindbox.marketing']));;
            return;
        }
    }

    public function configureActions()
    {
        return Ajax::configureActions($this->actions);
    }

    public function onPrepareComponentParams($arParams)
    {
        if (empty($arParams['ID'])) {
            $productXmlId = \Mindbox\Helper::getElementCode($arParams['ID']);
            $arParams['ID'] = $productXmlId;
        }


        return $arParams;
    }

    public function changeProductAction($productId, $price)
    {
        $productCache = \Mindbox\Components\CalculateProductData::getProductCache($productId);

        if (!empty($productCache)) {
            $return = [
                'MINDBOX_BONUS' => $productCache['MINDBOX_BONUS'],
            ];
        } else {
            $objCalculateProductData = new \Mindbox\Components\CalculateProductData();
            $calcData = $objCalculateProductData->getCalculateProductsData([
                $productId => ['price' => $price, 'id' => $productId]
            ]);

            $return = current($calcData);
        }

        return [
            'type' => 'success',
            'return' => $return,
        ];
    }

    protected function createPlaceholder()
    {
        $prefix = self::PLACEHOLDER_PREFIX;
        return "{{{$prefix}|{$this->arParams['ID']}|{$this->arParams['PRICE']}}}";
    }

    public function executeComponent()
    {
        $productCache = \Mindbox\Components\CalculateProductData::getProductCache($this->arParams['ID']);

        if (!empty($productCache)) {
            $this->arResult['MINDBOX_BONUS'] = $productCache['MINDBOX_BONUS'];
        } else {
            $this->arResult['MINDBOX_BONUS'] = $this->createPlaceholder();
        }

        $this->includeComponentTemplate();
    }
}