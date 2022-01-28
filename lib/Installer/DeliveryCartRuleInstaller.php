<?php

namespace Mindbox\Installer;

use Bitrix\Main\Loader;
use Bitrix\Highloadblock as HL;
use Mindbox\Discount\MindboxDeliveryDiscountAction;

class DeliveryCartRuleInstaller extends CartRuleInstaller
{
    const HL_NAME = 'MindboxDelivery';
    const HL_TABLE = 'mindbox_delivery';
    const DISCOUNT_NAME = 'MindboxDelivery';

    protected $hlNames = [
        'ru' => 'MindboxDelivery',
        'en' => 'MindboxDelivery'
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
                'UF_DELIVERY_ID' => [
                    'ENTITY_ID' => $userFieldObject,
                    'FIELD_NAME' => 'UF_DELIVERY_ID',
                    'USER_TYPE_ID' => 'integer',
                    'MANDATORY' => 'N',
                    'EDIT_FORM_LABEL' => ['ru' => '', 'en' => ''],
                    'LIST_COLUMN_LABEL' => ['ru' => '', 'en' => ''],
                    'LIST_FILTER_LABEL' => ['ru' => '', 'en' => ''],
                    'ERROR_MESSAGE' => ['ru' => '', 'en' => ''],
                    'HELP_MESSAGE' => ['ru' => '', 'en' => ''],
                ],
                'UF_ORDER_ID' => [
                    'ENTITY_ID' => $userFieldObject,
                    'FIELD_NAME' => 'UF_ORDER_ID',
                    'USER_TYPE_ID' => 'integer',
                    'MANDATORY' => 'N',
                    'EDIT_FORM_LABEL' => ['ru' => '', 'en' => ''],
                    'LIST_COLUMN_LABEL' => ['ru' => '', 'en' => ''],
                    'LIST_FILTER_LABEL' => ['ru' => '', 'en' => ''],
                    'ERROR_MESSAGE' => ['ru' => '', 'en' => ''],
                    'HELP_MESSAGE' => ['ru' => '', 'en' => ''],
                ],
                'UF_FUSER_ID' => [
                    'ENTITY_ID' => $userFieldObject,
                    'FIELD_NAME' => 'UF_FUSER_ID',
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
            'PRIORITY' => 100,
            'SORT' => 100,
            'DISCOUNT_VALUE' => 'mindbox',
            'DISCOUNT_TYPE' => 'P',
            'LAST_LEVEL_DISCOUNT' => 'N',
            'LAST_DISCOUNT' => 'N',
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
                            'CLASS_ID' => MindboxDeliveryDiscountAction::GetControlID(),
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