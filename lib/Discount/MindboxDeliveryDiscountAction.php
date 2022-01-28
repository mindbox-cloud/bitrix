<?php

namespace Mindbox\Discount;

use \Bitrix\Main\Loader;
use \Bitrix\Highloadblock\HighloadBlockTable as HLB;
use \Bitrix\Highloadblock\HighloadBlockLangTable;
use \Bitrix\Main\Localization\Loc;

class MindboxDeliveryDiscountAction extends \CSaleActionCtrlDelivery
{
    public static function GetControlID()
    {
        return 'mindbox_delivery';
    }

    public static function GetControlDescr()
    {
        $description = parent::GetControlDescr();
        $description['EXECUTE_MODULE'] = 'sale';

        return $description;
    }

    public static function GetControlShow($arParams)
    {
        $arAtoms = static::GetAtomsEx(false, false);

        $arResult = [
                'controlId' => static::GetControlID(),
                'group' => false,
                'label' => Loc::getMessage('APPLY_DISCOUNT_FROM_MINDBOX_DELIVERY'),
                'defaultText' => '',
                'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
                'control' => [
                        Loc::getMessage('APPLY_DISCOUNT_FROM_MINDBOX_DELIVERY'),
                        $arAtoms['HLB']
                ]
        ];

        return $arResult;
    }

    public static function GetAtomsEx($strControlID = false, $boolEx = false)
    {
        $boolEx = true === $boolEx;
        $hlbList = [];
        if (Loader::includeModule('highloadblock')) {
            $dbRes = HLB::GetList([]);

            while ($el = $dbRes->fetch()) {
                $hlbList[$el['ID']] = $el['NAME'];
            }

            $res = HighloadBlockLangTable::GetList(['filter' => ['=LID' => LANGUAGE_ID]]);

            while ($el = $res->fetch()) {
                if ($hlbList[$el['ID']]) {
                    $hlbList[$el['ID']] = $el['NAME'] . ' [' . $hlbList[$el['ID']] . ']';
                }
            }
        }

        $arAtomList = [
                'HLB' => [
                        'JS' => [
                                'id' => 'HLB',
                                'name' => 'extra',
                                'type' => 'select',
                                'values' => $hlbList,
                                'defaultText' => '...',
                                'defaultValue' => '',
                                'first_option' => '...'
                        ],
                        'ATOM' => [
                                'ID' => 'HLB',
                                'FIELD_TYPE' => 'string',
                                'FIELD_LENGTH' => 255,
                                'MULTIPLE' => 'N',
                                'VALIDATE' => 'list'
                        ]
                ],
        ];

        if (!$boolEx) {
            foreach ($arAtomList as &$arOneAtom) {
                $arOneAtom = $arOneAtom['JS'];
            }
            if (isset($arOneAtom)) {
                unset($arOneAtom);
            }
        }

        return $arAtomList;
    }

    public static function Generate($arOneCondition, $arParams, $arControl, $arSubs = false)
    {
        $options = [
                'HLB' => $arOneCondition['HLB'],
        ];

        $mxResult = '\\'. __NAMESPACE__ . '\\MindboxDiscountActions::applyToDelivery('
                . $arParams['ORDER'] . ', '
                . var_export($options, true)
                . ');';

        return $mxResult;
    }
}
