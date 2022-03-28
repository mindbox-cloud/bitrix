<?php

namespace Mindbox\Installer;

use Bitrix\Main\Loader;
use Bitrix\Highloadblock as HL;

abstract class CartRuleInstaller
{
    const HL_NAME = '';
    const HL_TABLE = '';
    const DISCOUNT_NAME = '';

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
    }

    public function createCartRule($hlBlockId)
    {
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
            'filter' => ['=NAME' => static::HL_NAME]
        ])->fetch();

        return (!empty($getHlBlock) && is_array($getHlBlock)) ? $getHlBlock['ID'] : false;
    }

    protected function deleteCartRule()
    {
        $exist = $this->checkExistCartRule();

        if (!empty($exist) && is_array($exist) && $exist['NAME'] === static::DISCOUNT_NAME) {
            \CSaleDiscount::Delete($exist['ID']);
        }
    }

    public function checkExistCartRule()
    {
        $return = false;
        $getDiscount = \CSaleDiscount::GetList([], ['NAME' => static::DISCOUNT_NAME]);

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
        $rsSites = \Bitrix\Main\SiteTable::getList(['filter' => ['ACTIVE' => 'Y', 'DEF' => 'Y']]);

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
