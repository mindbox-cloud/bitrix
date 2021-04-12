<?php


namespace Mindbox;

use \Bitrix\Main\Loader;
use \Bitrix\Highloadblock\HighloadBlockTable as HLB;
use \Bitrix\Highloadblock\HighloadBlockLangTable;

class ExtensionCartRulesActions extends \CSaleActionCtrlBasketGroup
{
    public static function GetClassName()
    {
        return __CLASS__;
    }

    public static function GetControlID()
    {
        return "DiscountFromDirectory";
    }

    public static function GetControlDescr()
    {
        return parent::GetControlDescr();
    }

    public static function GetAtoms()
    {
        return static::GetAtomsEx(false, false);
    }

    public static function GetControlShow($arParams)
    {
        $arAtoms = static::GetAtomsEx(false, false);
        $arResult = [
            "controlId" => static::GetControlID(),
            "group" => false,
            "label" => "Применить скидку из справочника",
            "defaultText" => "",
            "showIn" => static::GetShowIn($arParams["SHOW_IN_GROUPS"]),
            "control" => [
                "Применить скидку из HighLoad блока",
                $arAtoms["HLB"]
            ]
        ];

        return $arResult;
    }

    public static function GetAtomsEx($strControlID = false, $boolEx = false)
    {
        $boolEx = true === $boolEx;
        $hlbList = [];
        if (\Bitrix\Main\Loader::includeModule('highloadblock')) {
            $dbRes = HLB::GetList([]);
            while ($el = $dbRes->fetch()) {
                $hlbList[$el['ID']] = $el['NAME'];
            }
            $res = HighloadBlockLangTable::GetList(['filter' => ['=LID' => LANGUAGE_ID]]);
            while ($el = $res->fetch()) {
                if ($hlbList[$el['ID']]) {
                    $hlbList[$el['ID']] = $el['NAME'] . " [" . $hlbList[$el['ID']] . "]";
                }
            }
        }
        $arAtomList = [
            "HLB" => [
                "JS" => [
                    "id" => "HLB",
                    "name" => "extra",
                    "type" => "select",
                    "values" => $hlbList,
                    "defaultText" => "...",
                    "defaultValue" => "",
                    "first_option" => "..."
                ],
                "ATOM" => [
                    "ID" => "HLB",
                    "FIELD_TYPE" => "string",
                    "FIELD_LENGTH" => 255,
                    "MULTIPLE" => "N",
                    "VALIDATE" => "list"
                ]
            ],
        ];
        if (!$boolEx) {
            foreach ($arAtomList as &$arOneAtom) {
                $arOneAtom = $arOneAtom["JS"];
            }
            if (isset($arOneAtom)) {
                unset($arOneAtom);
            }
        }
        return $arAtomList;
    }

    public static function Generate($arOneCondition, $arParams, $arControl, $arSubs = false)
    {
        $mxResult = __CLASS__ . "::applyProductDiscount(" . $arParams["ORDER"] . ", " . "\"" . $arOneCondition["HLB"] . "\"" . ");";

        return $mxResult;
    }

    /**
     * Применяет скидку из справочника к товарам из корзины
     * @param $arOrder
     * @param $hlb - Highload block
     */
    public static function applyProductDiscount(&$arOrder, $hlb)
    {
        $userId = $arOrder['USER_ID'];
        if ($userId && \Bitrix\Main\Loader::includeModule('highloadblock')) {
            $arBasketId = [];
            foreach ($arOrder['BASKET_ITEMS'] as &$product) {
                $arBasketId[] = $product['ID'];
            }
            unset($product);
            $hlblock = HLB::getById($hlb)->fetch();
            if (!$hlblock) {
                return;
            }
            $entity = HLB::compileEntity($hlblock);
            $entityClass = $entity->getDataClass();
            //Находим записи в справочнике с нужными параметрами - товар/пользователь
            $dbRes = $entityClass::getList([
                'filter' => [
                    '=UF_BASKET_ID' => $arBasketId,
                ],
                'order' => [
                    'ID' => 'DESC'
                ]
            ]);
            $discounts = [];
            while ($el = $dbRes->fetch()) {
                if (!$discounts[$el['UF_PRODUCT']]) {
                    $discounts[$el['UF_BASKET_ID']] = $el;
                }
            }

            //Применяем скидку
            foreach ($arOrder['BASKET_ITEMS'] as &$product) {
                $basketId = $product['ID'];
                if ($discounts[$basketId]) {
                    if ($discounts[$basketId]['UF_DISCOUNTED_PRICE']) {
                        $discountPrice = $product['PRICE'] - $discounts[$basketId]['UF_DISCOUNTED_PRICE'];
                    }

                    $product['DISCOUNT_PRICE'] = $product['DISCOUNT_PRICE'] + $discountPrice;
                    $product['PRICE'] = $discounts[$basketId]['UF_DISCOUNTED_PRICE'];
                }
            }
            unset($product);
        }
    }
}