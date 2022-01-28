<?php

namespace Mindbox\Installer;

use Bitrix\Main\Loader;
use Bitrix\Highloadblock as HL;

class ProductCartRuleInstaller extends CartRuleInstaller
{
    const HL_NAME = 'Mindbox';
    const HL_TABLE = 'mindbox';
    const DISCOUNT_NAME = 'Mindbox';

    protected $hlNames = [
        'ru' => 'Mindbox',
        'en' => 'Mindbox'
    ];

    public function createHighLoadBlock()
    {
        $create = HL\HighloadBlockTable::add([
            'NAME' => static::HL_NAME,
            'TABLE_NAME' => static::HL_TABLE,
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
                    'USER_TYPE_ID' => 'double',
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
                    'USER_TYPE_ID' => 'integer',
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

    public function createCartRule($hlBlockId)
    {
        $siteId = $this->getActiveSite();
        $siteUserGroups = $this->getUserGroupsIds();

        $discountFields = [
            'LID' => $siteId,
            'SITE_ID' => $siteId,
            'NAME' => static::DISCOUNT_NAME,
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
                            'DATA' => ['HLB' => $hlBlockId],
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
}
