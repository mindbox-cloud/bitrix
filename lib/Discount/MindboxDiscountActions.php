<?php

namespace Mindbox\Discount;

use Bitrix\Main\Loader;
use \Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Sale\Fuser;

class MindboxDiscountActions extends \Bitrix\Sale\Discount\Actions
{
    /**
     * @param array $order
     * @param array $action
     */
    public static function applyToDelivery(array &$arOrder, array $action)
    {
        global $USER;

        if (!Loader::includeModule('highloadblock')) {
            return;
        }

        if ((int)$arOrder['DELIVERY_ID'] <= 0) {
            return;
        }

        if ((int)$action['HLB'] <= 0) {
            return;
        }

        $hlblock = HighloadBlockTable::getById((int)$action['HLB'])->fetch();

        if (!$hlblock) {
            return;
        }

        $entity = HighloadBlockTable::compileEntity($hlblock);
        $entityClass = $entity->getDataClass();

        $filter = [];
        $filter['UF_DELIVERY_ID'] = (int)$arOrder['DELIVERY_ID'];

        if ((int)$arOrder['ID'] > 0) {
            $filter['UF_ORDER_ID'] = (int)$arOrder['ID'];
        } else {
            if ($USER->IsAuthorized() && (int)$arOrder['USER_ID'] > 0) {
                $fuserId = Fuser::getIdByUserId((int)$arOrder['USER_ID']);
            } elseif ($USER->IsAuthorized() && (int)$USER->GetID() > 0) {
                $fuserId = Fuser::getIdByUserId((int)$USER->GetID());
            } else {
                $fuserId = Fuser::getId();
            }

            if (empty($fuserId)) {
                return;
            }

            $filter['UF_FUSER_ID'] = $fuserId;
        }

        $discount = $entityClass::getList([
                'filter' => $filter,
                'select' => ['UF_DISCOUNTED_PRICE'],
                'limit' => 1
        ])->fetch();

        if (!$discount) {
            return;
        }

        $value = (float)$discount['UF_DISCOUNTED_PRICE'];

        $action['VALUE'] = -1 * ($arOrder['PRICE_DELIVERY'] - $value);
        $action['UNIT'] = self::VALUE_TYPE_FIX;
        $action['CURRENCY'] = static::getCurrency();

        parent::applyToDelivery($arOrder, $action);
    }
}
