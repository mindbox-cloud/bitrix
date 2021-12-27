<?php

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use \Bitrix\Main\Data\Cache;
use Mindbox\Ajax;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

class ProductPrice extends CBitrixComponent implements Controllerable
{
    const PLACEHOLDER_PRICE_PREFIX = 'MINDBOX_PRICE';
    const PLACEHOLDER_OLD_PRICE_PREFIX = 'MINDBOX_OLD_PRICE';

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
        //return Ajax::configureActions($this->actions);
    }

    public function onPrepareComponentParams($arParams)
    {
        if (empty($arParams['ID'])) {
            $productXmlId = \Mindbox\Helper::getElementCode($arParams['ID']);
            $arParams['ID'] = $productXmlId;
        }
       // echo '<pre>'; print_r($arParams);

        return $arParams;
    }

    public function changeProductAction($productId, $price)
    {
        $productCache = \Mindbox\Components\CalculateProductData::getProductCache($productId);

        if (!empty($productCache)) {
            $return = [
                'MINDBOX_PRICE' => $productCache['MINDBOX_PRICE'],
                'MINDBOX_OLD_PRICE' => $productCache['MINDBOX_OLD_PRICE']
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

    protected function createPlaceholder($prefix): string
    {
        return "{{{$prefix}|{$this->arParams['ID']}|{$this->arParams['PRICE']}}}";
    }


    public function executeComponent()
    {
        $productCache = \Mindbox\Components\CalculateProductData::getProductCache($this->arParams['ID']);
        $this->arResult['PRODUCT_ID'] = $this->arParams['ID'];

        if (!empty($productCache)) {
            $this->arResult['MINDBOX_PRICE'] = $productCache['MINDBOX_PRICE'];
            $this->arResult['MINDBOX_OLD_PRICE'] = $productCache['MINDBOX_OLD_PRICE'];
        } else {
            $this->arResult['MINDBOX_PRICE'] = $this->createPlaceholder(self::PLACEHOLDER_PRICE_PREFIX);
            $this->arResult['MINDBOX_OLD_PRICE'] = $this->createPlaceholder(self::PLACEHOLDER_OLD_PRICE_PREFIX);
        }

        $this->includeComponentTemplate();
    }
}