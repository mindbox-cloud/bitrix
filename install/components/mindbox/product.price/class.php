<?php

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use \Bitrix\Main\Data\Cache;
use Mindbox\Ajax;
use Mindbox\Components\CalculateProductData;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

class ProductPrice extends CBitrixComponent implements Controllerable
{
    const PLACEHOLDER_PRICE_PREFIX = 'MINDBOX_PRICE';
    const PLACEHOLDER_OLD_PRICE_PREFIX = 'MINDBOX_OLD_PRICE';
    const LABEL_POSITION = 'after'
    ;
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
        return $arParams;
    }

    public function changeProductAction($productId, $price)
    {
        $productCache = \Mindbox\Components\CalculateProductData::getProductCache($productId);
        $useCache = false;

        if (!empty($productCache)) {
            $useCache = true;

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
            'cache' => $useCache
        ];
    }

    protected function createPlaceholder($prefix): string
    {
        return "{{{$prefix}|{$this->arParams['ID']}|{$this->arParams['PRICE']}}}";
    }

    protected function createPricePlaceholder(): string
    {
        $prefix = self::PLACEHOLDER_PRICE_PREFIX;
        return "{{{$prefix}|{$this->arParams['ID']}|{$this->arParams['PRICE']}|{$this->getLabelHtml()}|{$this->getPriceValueHtml()}}}";
    }

    protected function createOldPricePlaceholder(): string
    {
        $prefix = self::PLACEHOLDER_OLD_PRICE_PREFIX;
        return "{{{$prefix}|{$this->arParams['ID']}|{$this->arParams['PRICE']}|after::|{$this->getOldPriceValueHtml()}}}";
    }

    protected function getLabelHtml()
    {
        return self::LABEL_POSITION . '::<span class="mindbox-product-price__currency">' . $this->arParams['CURRENCY'] . '</span>';
    }

    protected function getPriceValueHtml(): string
    {
        return '<span class="mindbox-product-price__price">:value:</span>';
    }

    protected function getOldPriceValueHtml(): string
    {
        return '<span class="mindbox-product-price__discount">:value:</span>';
    }


    public function executeComponent()
    {
        $productCache = CalculateProductData::getHtmlProductCache($this->arParams['ID'], 'MINDBOX_PRICE');
        $this->arResult['PRODUCT_ID'] = $this->arParams['ID'];

        if (!empty($productCache)) {
            $this->arResult['MINDBOX_PRICE'] = $productCache;
            $this->arResult['MINDBOX_OLD_PRICE'] = CalculateProductData::getHtmlProductCache($this->arParams['ID'], 'MINDBOX_OLD_PRICE');
        } else {
            $this->arResult['MINDBOX_PRICE'] = $this->createPricePlaceholder();
            $this->arResult['MINDBOX_OLD_PRICE'] = $this->createOldPricePlaceholder();
        }

        $this->includeComponentTemplate();
    }
}