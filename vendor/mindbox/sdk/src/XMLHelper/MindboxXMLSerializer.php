<?php


namespace Mindbox\XMLHelper;

use Mindbox\DTO\DTO;
use SimpleXMLElement;

/**
 * Class MindboxXMLSerializer
 *
 * @package Mindbox\XMLHelper
 */
class MindboxXMLSerializer
{
    /**
     * Генерация xml строки из массива данных.
     *
     * @param string $name
     * @param array  $data
     *
     * @return string
     */
    public static function fromArrayToXML($name, array $data)
    {
        $xml    = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><' . $name . '></' . $name . '>');
        $result = self::getXML($xml, $data)->asXML();

        return (string)$result;
    }

    /**
     * Рекурсивно конвертирует массив в xml.
     *
     * @param SimpleXMLElement $xml
     * @param array            $data
     *
     * @return SimpleXMLElement
     */
    private static function getXML(&$xml, array $data)
    {
        foreach ($data as $key => $value) {
            if ($key === DTO::XML_ITEM_NAME_INDEX) {
                continue;
            }
            $key = self::getKey($key, $data);
            if (is_array($value)) {
                $subNode = $xml->addChild($key);
                self::getXML($subNode, $value);
            } elseif (is_bool($value)) {
                $xml->addChild($key, var_export($value, true));
            } else {
                $xml->addChild($key, $value);
            }
        }

        return $xml;
    }

    /**
     * Возвращает ключ для элемента xml.
     *
     * @param mixed $key
     * @param mixed $data
     *
     * @return string
     */
    private static function getKey($key, $data)
    {

        if (!is_numeric($key)) {
            return $key;
        }
        if (is_array($data) && isset($data[DTO::XML_ITEM_NAME_INDEX])) {
            return $data[DTO::XML_ITEM_NAME_INDEX];
        }

        return 'value';
    }

    /**
     * Генерирует массив из строки xml.
     *
     * @param string $xmlString
     *
     * @return array
     */
    public function fromXMLToArray($xmlString)
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlString, "SimpleXMLElement", LIBXML_NOCDATA);

        if ($xml === false) {
            return [];
        }

        $name  = $xml->getName();
        $array = json_decode((json_encode($xml) ?: ''), true);

        return [$name => $this->normalizeArray($array)];
    }

    /**
     * Приводит массив, сформированный из xml, к общему виду с аналогичным массивом, сформированным из json.
     * Это необходимо для универсальной обработки массива вне зависимости от формата общения с Mindbox.
     *
     * @param array $data
     *
     * @return array
     */
    private function normalizeArray($data)
    {
        foreach ($data as $parentKey => &$value) {
            if (is_array($value)) {
                $unsetFlag = false;
                foreach ($value as $key => $item) {
                    if (is_numeric($key) && is_array($item)) {
                        $data[]    = $item;
                        $unsetFlag = true;
                    }
                }
                if ($unsetFlag) {
                    unset($data[$parentKey]);
                    $data[DTO::XML_ITEM_NAME_INDEX] = $parentKey;
                }
                $value = $this->normalizeArray($value);
            }
        }

        return $data;
    }
}
