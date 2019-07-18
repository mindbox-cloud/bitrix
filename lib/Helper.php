<?php
/**
 * Created by @copyright QSOFT.
 */

namespace Mindbox;


use Bitrix\Main\UserTable;
use CPHPCache;
use Mindbox\DTO\DTO;

class Helper
{
    public static function getNumEnding($number, $endingArray)
    {
        $number = $number % 100;
        if ($number >= 11 && $number <= 19) {
            $ending = $endingArray[2];
        } else {
            $i = $number % 10;
            switch ($i) {
                case (1):
                    $ending = $endingArray[0];
                    break;
                case (2):
                case (3):
                case (4):
                    $ending = $endingArray[1];
                    break;
                default:
                    $ending = $endingArray[2];
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
                'limit' => 1
            ]
        )->fetch();

        if ($rsUser && isset($rsUser['UF_MINDBOX_ID'])) {
            $mindboxId = $rsUser['UF_MINDBOX_ID'];
        }

        return $mindboxId;
    }

    public static function formatPhone($phone) {
        return str_replace([' ', '(', ')', '-', '+'], "", $phone);
    }

    public static function formatDate($date) {
        return ConvertDateTime($date, "YYYY-MM-DD") ?: null;
    }

    public static function iconvDTO(DTO $dto, $in = true)
    {
        if(LANG_CHARSET === 'UTF-8') {
            return $dto;
        }

        if($in) {
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
        $dtoArray = array_map(function ($field) use($in, $out) {
            return is_array($field) ?
                self::encodeDTOArray($field, $in, $out) : iconv($in, $out, $field);
        }, $arr);

        return $dtoArray;
    }

    public static function getProductId($id)
    {
        $result = '';
        if(Options::getModuleOption('USE_SKU')) {
            $result = ltrim(stristr($id, '#'), '#');
        } else {
            $result = $id;
        }

        return $result;
    }

}