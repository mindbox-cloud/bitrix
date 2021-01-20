<?php
/**
 * Created by @copyright QSOFT.
 */

namespace Mindbox;


use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use CCatalog;
use CIBlock;
use COption;
use CPHPCache;
use Mindbox\DTO\DTO;

class Helper
{
    public static function getNumEnding($number, $endingArray)
    {
        $number = $number % 100;
        if ($number >= 11 && $number <= 19) {
            $ending = $endingArray[ 2 ];
        } else {
            $i = $number % 10;
            switch ($i) {
                case (1):
                    $ending = $endingArray[ 0 ];
                    break;
                case (2):
                case (3):
                case (4):
                    $ending = $endingArray[ 1 ];
                    break;
                default:
                    $ending = $endingArray[ 2 ];
            }
        }

        return $ending;
    }

    public static function getMindboxId($id)
    {
        $mindboxId = false;
        $rsUser = UserTable::getList(
            [
                'select' => [
                    'UF_MINDBOX_ID'
                ],
                'filter' => ['ID' => $id],
                'limit'  => 1
            ]
        )->fetch();

        if ($rsUser && isset($rsUser[ 'UF_MINDBOX_ID' ])) {
            $mindboxId = $rsUser[ 'UF_MINDBOX_ID' ];
        }

        return $mindboxId;
    }

    public static function formatPhone($phone)
    {
        return str_replace([' ', '(', ')', '-', '+'], "", $phone);
    }

    public static function formatDate($date)
    {
        return ConvertDateTime($date, "YYYY-MM-DD") ?: null;
    }

    public static function iconvDTO(DTO $dto, $in = true)
    {
        if (LANG_CHARSET === 'UTF-8') {
            return $dto;
        }

        if ($in) {
            return self::convertDTO($dto, LANG_CHARSET, 'UTF-8');
        } else {
            return self::convertDTO($dto, 'UTF-8', LANG_CHARSET);
        }
    }

    public static function convertDTO(DTO $DTO, $in, $out)
    {
        $class = get_class($DTO);
        $dtoArray = $DTO->getFieldsAsArray();
        $dtoArray = self::encodeDTOArray($dtoArray, $in, $out);

        return new $class($dtoArray);
    }

    private static function encodeDTOArray($arr, $in, $out)
    {
        $dtoArray = array_map(function ($field) use ($in, $out) {
            return is_array($field) ?
                self::encodeDTOArray($field, $in, $out) : iconv($in, $out, $field);
        }, $arr);

        return $dtoArray;
    }

    /**
     * ����� ���������� �� ������
     * @param \Bitrix\Sale\Basket $basketItem
     *
     * @return $result
     */

    public static function getProductId($basketItem)
    {
        $result = '';
        $id = $basketItem->getField('PRODUCT_XML_ID');

        if(!$id) {
            $productId = $basketItem->getField('PRODUCT_ID');
            $arProduct = \CIBlockElement::GetByID($productId)->GetNext();
            $id = $arProduct['XML_ID'];
        }

        if(!$id) {
            $id = $basketItem->getField('PRODUCT_ID');
        }
        if(strpos($id, '#') !==false) {
            $result = ltrim(stristr($id, '#'), '#');
        } else {
            $result = $id;
        }
        return $result;
    }

    /**
     * ����� ��������� ������ ����������
     *
     * @return $arIblock
     */
    public static function getIblocks()
    {
        $arIblock = [];
        $result = CIBlock::GetList(
            [],
            [
                'ACTIVE' => 'Y',
            ]
        );
        while ($ar_res = $result->Fetch()) {
            $arIblock[ $ar_res[ 'ID' ] ] = $ar_res[ 'NAME' ] . " (" . $ar_res[ 'ID' ] . ")";
        }

        return $arIblock;
    }

    public static function getProps()
    {
        $props = [];

        $catalogId = COption::GetOptionString(ADMIN_MODULE_NAME, 'CATALOG_IBLOCK_ID', '0');
        if (!empty($catalogId) && $catalogId !== '0') {
            $iblockProperties = CIBlock::GetProperties($catalogId);
            while ($iblockProperty = $iblockProperties-> Fetch()) {
                $props['PROPERTY_'.$iblockProperty['CODE']] = $iblockProperty['NAME'];
            }
        }

        return $props;
    }

    public static function getOffersProps()
    {
        $offerProps = [];

        if (!Loader::includeModule('sale')) {
            return $offerProps;
        }

        $catalogId = COption::GetOptionString(ADMIN_MODULE_NAME, 'CATALOG_IBLOCK_ID', '0');

        if (!empty($catalogId) && $catalogId !== '0') {
            $select = ['ID', 'IBLOCK_ID', 'OFFERS_IBLOCK_ID'];
            $filter = ['IBLOCK_ID' => $catalogId];
            $offersCatalogId = CCatalog::GetList([], $filter, false, [], $select)->Fetch()['OFFERS_IBLOCK_ID'];
        }

        if (!empty($offersCatalogId) && $offersCatalogId !== '0') {
            $iblockProperties = CIBlock::GetProperties($offersCatalogId);
            while ($iblockProperty = $iblockProperties-> Fetch()) {
                $offerProps['PROPERTY_'.$iblockProperty['CODE']] = $iblockProperty['NAME'];
            }
        }

        return $offerProps;
    }

    /**
     * ����� ���������� ����� �������� ������ (����������/�����������)
     *
     * @return $isSync
     */
    public static function isSync()
    {
        if (COption::GetOptionString('mindbox.marketing', 'MODE') == 'standard') {
            $isSync = false;
        } else {
            $isSync = true;
        }
        return $isSync;
    }


    /**
     * ����� ���������� ��� ����� �� ����������������� ������������
     *
     * @return boolean
     */
    public static function isUnAuthorizedOrder($arUser) {
        return date('dmYHi', time()) === date('dmYHi', strtotime($arUser['DATE_REGISTER']));
    }

    /**
     * @param array $basketItems
     * @return array
     */
    public static function removeDuplicates($basketItems)
    {
        $uniqueItems = [];

        /**
         * @var \Bitrix\Sale\BasketItem $item
         */
        foreach ($basketItems as $item) {
            $uniqueItems[$item->getField('PRODUCT_ID')][] = $item;
        }

        if (count($uniqueItems) === count($basketItems)) {
            return $basketItems;
        }

        $uniqueBasketItems = [];

        foreach ($uniqueItems as $id => $groupItems) {
            $item = current($groupItems);
            $quantity = 0;
            foreach ($groupItems as $groupItem) {
                $quantity += $groupItem->getField('QUANTITY');
            }
            $item->setField('QUANTITY', $quantity);
            $uniqueBasketItems[] = $item;
        }

        return $uniqueBasketItems;
    }
}