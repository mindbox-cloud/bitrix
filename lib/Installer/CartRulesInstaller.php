<?php

namespace Mindbox\Installer;

use Bitrix\Main\Loader;
use Bitrix\Highloadblock as HL;

class CartRulesInstaller
{
    const HL_NAME = 'Mindbox';
    const HL_TABLE = 'mindbox';
    const DISCOUNT_NAME = 'Mindbox';

    protected $hlNames = [
        'ru' => 'Mindbox',
        'en' => 'Mindbox'
    ];

    public function __construct()
    {
        Loader::IncludeModule('highloadblock');
        Loader::IncludeModule('sale');
    }

    public function createHighLoadBlock()
    {
        $create = HL\HighloadBlockTable::add([
            'NAME' => self::HL_NAME,
            'TABLE_NAME' => self::HL_TABLE,
        ]);

        if ($create->isSuccess()) {
            $id = $create->getId();

            foreach ($this->hlNames as $key => $val) {
                HL\HighloadBlockLangTable::add([
                    'ID' => $id,
                    'LID' => $key,
                    'NAME' => $val
                ]);
            }

            $userFieldObject = 'HLBLOCK_' . $id;

            $fields = [
                'UF_DISCOUNTED_PRICE' => [
                    'ENTITY_ID' => $userFieldObject,
                    'FIELD_NAME' => 'UF_DISCOUNTED_PRICE',
                    'USER_TYPE_ID' => 'integer',
                    'MANDATORY' => 'N',
                    'EDIT_FORM_LABEL' => ['ru' => '', 'en' => ''],
                    'LIST_COLUMN_LABEL' => ['ru' => '', 'en' => ''],
                    'LIST_FILTER_LABEL' => ['ru' => '', 'en' => ''],
                    'ERROR_MESSAGE' => ['ru' => '', 'en' => ''],
                    'HELP_MESSAGE' => ['ru' => '', 'en' => ''],
                ],
                'UF_BASKET_ID' => [
                    'ENTITY_ID' => $userFieldObject,
                    'FIELD_NAME' => 'UF_BASKET_ID',
                    'USER_TYPE_ID' => 'double',
                    'MANDATORY' => 'N',
                    'EDIT_FORM_LABEL' => ['ru' => '', 'en' => ''],
                    'LIST_COLUMN_LABEL' => ['ru' => '', 'en' => ''],
                    'LIST_FILTER_LABEL' => ['ru' => '', 'en' => ''],
                    'ERROR_MESSAGE' => ['ru' => '', 'en' => ''],
                    'HELP_MESSAGE' => ['ru' => '', 'en' => ''],
                ],
            ];

            foreach ($fields as $field) {
                $obUserField = new \CUserTypeEntity;
                $obUserField->Add($field);
            }

            return $id;

        } else {
            $errors = $create->getErrorMessages();
        }
    }

    public function deleteHighLoadBlock()
    {
        $hlBlockExist = $this->checkExistHighLoadBlock();

        if (!empty($hlBlockExist)) {
            HL\HighloadBlockTable::delete($hlBlockExist);
        }
    }

    public function checkExistHighLoadBlock()
    {
        $getHlBlock = HL\HighloadBlockTable::getList([
            'filter' => ['=NAME' => self::HL_NAME]
        ])->fetch();

        return (!empty($getHlBlock) && is_array($getHlBlock)) ? $getHlBlock['ID'] : false;
    }

    public function createCartRule($hlBlockId)
    {
        $siteId = $this->getActiveSite();
        $siteUserGroups = $this->getUserGroupsIds();

        $discountFields = [
            'LID' => $siteId,
            'SITE_ID' => $siteId,
            'NAME' => self::DISCOUNT_NAME,
            'DISCOUNT_VALUE' => 'mindbox',
            'DISCOUNT_TYPE' => 'P',
            'LAST_LEVEL_DISCOUNT' => 'Y',
            'LAST_DISCOUNT' => 'Y',
            'ACTIVE' => 'Y',
            'CURRENCY' => 'RUR',
            'USER_GROUPS' => $siteUserGroups,
            'ACTIONS' => [
                'CLASS_ID' => 'CondGroup',
                'DATA' =>
                    [
                        'All' => 'AND',
                    ],
                'CHILDREN' =>
                    [
                        0 => [
                            'CLASS_ID' => 'DiscountFromDirectory',
                            'DATA' => ['HLB' => $hlBlockId,],
                            'CHILDREN' => [],
                        ],
                    ],
            ],
            'CONDITIONS' => [
                'CLASS_ID' => 'CondGroup',
                'DATA' =>
                    [
                        'All' => 'AND',
                        'True' => 'True',
                    ],
                'CHILDREN' => []
            ]
        ];

        \CSaleDiscount::Add($discountFields);
    }

    protected function deleteCartRule()
    {
        $exist = $this->checkExistCartRule();

        if (!empty($exist) && is_array($exist) && $exist['NAME'] === self::DISCOUNT_NAME) {
            \CSaleDiscount::Delete($exist['ID']);
        }
    }

    public function checkExistCartRule()
    {
        $return = false;
        $getDiscount = \CSaleDiscount::GetList([], ['NAME' => self::DISCOUNT_NAME]);

        if ($discount = $getDiscount->Fetch()) {
            $return = $discount;
        }

        return $return;
    }

    public function getUserGroupsIds()
    {
        $return = [];
        $getUserGroups = \CGroup::GetList(($by='c_sort'), ($order='desc'), ['ACTIVE' => 'Y']);

        while ($group = $getUserGroups->Fetch()) {
            $return[] = $group['ID'];
        }

        return $return;
    }

    protected function getActiveSite()
    {
        $return = false;
        $rsSites = \Bitrix\Main\SiteTable::getList(['filter' => ['ACTIVE' => 'Y']]);

        if ($arSite = $rsSites->fetch()) {
            $return = $arSite['LID'];
        }

        return $return;
    }

    public function install()
    {
        $hlBlockExist = $this->checkExistHighLoadBlock();

        if (!empty($hlBlockExist) && (int)$hlBlockExist > 0) {
            $hlBlockId = $hlBlockExist;
        } else {
            $hlBlockId = $this->createHighLoadBlock();
        }

        if (!empty($hlBlockId)) {
            $existRule = $this->checkExistCartRule();

            if ($existRule === false) {
                $this->createCartRule($hlBlockId);
            }
        }
    }

    public function unInstall()
    {
        $this->deleteCartRule();
        $this->deleteHighLoadBlock();
    }
}