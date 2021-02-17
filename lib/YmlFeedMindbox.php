<?php

namespace Mindbox;

use domDocument;
use Mindbox\Helper;

class YmlFeedMindbox
{

    private static $stepSize = 1000;

    public static function start($step = 1)
    {
        $step = (int) $step;

        if (!isset($_SERVER["SERVER_NAME"]) || empty($_SERVER["SERVER_NAME"])) {
            $_SERVER["SERVER_NAME"] = SITE_SERVER_NAME;
        }

        $cond = empty(Options::getModuleOption("YML_NAME")) || empty(Options::getModuleOption("CATALOG_IBLOCK_ID"));

        if ($cond) {
            return "\Mindbox\YmlFeedMindbox::start($step);";
        } elseif (!\CModule::IncludeModule("currency")) {
            return "\Mindbox\YmlFeedMindbox::start($step);";
        } elseif (!\CModule::IncludeModule("iblock")) {
            return "\Mindbox\YmlFeedMindbox::start($step);";
        } elseif (!\CModule::IncludeModule("catalog")) {
            return "\Mindbox\YmlFeedMindbox::start($step);";
        }

        $prodsCount = self::getProdsCount();
        if ($step === 1 && $prodsCount > self::$stepSize) {
            self::checkAgents($prodsCount);
        }

        self::generateYml($step);

        return "\Mindbox\YmlFeedMindbox::start($step);";
    }

    protected static function checkAgents($prodsCount)
    {
        $agents = \CAgent::GetList(['ID' => 'DESC'], ['NAME' => '\Mindbox\YmlFeedMindbox::start(%']);

        $existingAgents = [];

        while ($agent = $agents->Fetch()) {
            $regex = '/(?<=\().+?(?=\))/m';

            preg_match($regex, $agent['NAME'], $match, PREG_SET_ORDER, 0);

            if (!empty($match)) {
                $existingAgents[] = $match;
            }
        }

        if ($prodsCount / count($existingAgents) > self::$stepSize) {
            foreach ($existingAgents as $num) {
                \CAgent::RemoveAgent(
                    "\Mindbox\QueueTable::start($num);",
                    "mindbox.marketing"
                );
            }

            $existingAgents = [];
        }

        if (empty($existingAgents)) {
            $newAgentsMax = ceil($prodsCount / self::$stepSize);
            self::createAgents($newAgentsMax);
        }
    }

    protected static function createAgents($max)
    {
        for ($i = 1; $i <= $max; $i++) {
            $now = new \Bitrix\Main\Type\DateTime();
            $nextSeconds = $i * 180;
            $next = \Bitrix\Main\Type\DateTime::createFromPhp(new \DateTime("now + ".$nextSeconds." sec"));
            \CAgent::AddAgent(
                "\Mindbox\YmlFeedMindbox::start($i);",
                "mindbox.marketing",
                "N",
                86400,
                $now,
                "Y",
                $next,
                30
            );
        }
    }

    protected static function generateYml($step)
    {
        if ($step === 1) {
            $dom = new domDocument("1.0", "utf-8");

            $root = $dom->createElement("yml_catalog");
            $root->setAttribute("date", date('Y-m-d H:i'));
            $dom->appendChild($root);

            $shop = $dom->createElement("shop");
            $shop = $root->appendChild($shop);

            $name = htmlspecialchars(self::getSiteName(), ENT_XML1 | ENT_QUOTES);
            $siteName = $dom->createElement("name", $name);
            $shop->appendChild($siteName);

            $companyName = $dom->createElement("company", $name);
            $shop->appendChild($companyName);

            $siteUrl = $dom->createElement("url", htmlspecialchars(self::getProtocol() . $_SERVER["SERVER_NAME"], ENT_XML1 | ENT_QUOTES));
            $shop->appendChild($siteUrl);

            $currencies = $dom->createElement("currencies");
            $currencies = $shop->appendChild($currencies);
            $crncs = self::getCurrencies();
            while ($crnc = $crncs->Fetch()) {
                $currencie = $dom->createElement("currency");
                $currencie->setAttribute("id", htmlspecialchars($crnc["CURRENCY"], ENT_XML1 | ENT_QUOTES));
                $currencie->setAttribute("rate", htmlspecialchars((int)$crnc["AMOUNT"], ENT_XML1 | ENT_QUOTES));
                $currencies->appendChild($currencie);
            }

            $categories = $dom->createElement("categories");
            $categories = $shop->appendChild($categories);
            $dbCats = self::getCategories();
            $catId = [];
            while ($cat = $dbCats->GetNext()) {
                $cats[] = $cat;
            }
            foreach ($cats as $cat) {
                $category = $dom->createElement("category", htmlspecialchars($cat["NAME"], ENT_XML1 | ENT_QUOTES));
                $category->setAttribute("id", Helper::getSectionCode($cat['ID']));
                if (isset($cat["IBLOCK_SECTION_ID"]) && !empty($cat["IBLOCK_SECTION_ID"])) {
                    $parentId = (!empty($catId[$cat['IBLOCK_SECTION_ID']]) ? $catId[$cat['IBLOCK_SECTION_ID']] : $cat["IBLOCK_SECTION_ID"]);
                    $category->setAttribute("parentId", Helper::getSectionCode($parentId));
                }
                $categories->appendChild($category);
            }

            $offers = $dom->createElement("offers");
            $offers = $shop->appendChild($offers);
        } else {
            $dom = new domDocument();
            $dom->load($_SERVER["DOCUMENT_ROOT"] . "/" . Options::getModuleOption("YML_NAME").'.raw');
            $offers = $dom->getElementsByTagName("offers")->item(0);
        }

        $basePriceId = self::getBasePriceId();
        $prods = self::getProds($basePriceId, $step);

        $prodIds = self::getProdsIds($prods);
        $prodsOfrs = self::getOffers($basePriceId, $prodIds);

        if (!empty($prodsOfrs)) {
            foreach ($prodsOfrs as $prodId => $ofrs) {
                foreach ($ofrs as $ofr) {
                    $offer = $dom->createElement("offer");
                    $offer->setAttribute("group_id", $prods[$prodId]['XML_ID']);
                    $offer->setAttribute("id", Helper::getElementCode($ofr["ID"]));
                    $available = ($ofr['CATALOG_AVAILABLE'] === 'Y' && $ofr['ACTIVE'] === 'Y') ? 'true' : 'false';
                    $offer->setAttribute("available", $available);
                    unset($available);
                    $offer = $offers->appendChild($offer);
                    if (!empty($ofr["NAME"])) {
                        $name = htmlspecialchars($ofr["NAME"], ENT_XML1 | ENT_QUOTES);
                    } else {
                        $name = htmlspecialchars($prods[$prodId]["NAME"], ENT_XML1 | ENT_QUOTES);
                    }
                    $offerName = $dom->createElement("name", $name);
                    $offer->appendChild($offerName);
                    if (!empty($ofr["PREVIEW_TEXT"])) {
                        $description = htmlspecialchars($ofr["PREVIEW_TEXT"], ENT_XML1 | ENT_QUOTES);
                    } else {
                        $description = htmlspecialchars($prods[$prodId]["PREVIEW_TEXT"], ENT_XML1 | ENT_QUOTES);
                    }
                    if (!empty($description)) {
                        $offerDescription = $dom->createElement("description", $description);
                        $offer->appendChild($offerDescription);
                    }
                    if ($prods[$prodId]["DETAIL_PAGE_URL"]) {
                        $offerUrl = $dom->createElement("url", htmlspecialchars(self::getProtocol() . $_SERVER["SERVER_NAME"] . $prods[$prodId]["DETAIL_PAGE_URL"], ENT_XML1 | ENT_QUOTES));
                        $offer->appendChild($offerUrl);
                    }
                    if (!empty($ofr['prices']) && $ofr['prices']['RESULT_PRICE']['BASE_PRICE'] !== $ofr['prices']['RESULT_PRICE']['DISCOUNT_PRICE']) {
                        $offerPrice = $dom->createElement("price", $ofr['prices']['RESULT_PRICE']['DISCOUNT_PRICE']);
                        $offer->appendChild($offerPrice);
                        $oldPrice = $dom->createElement("oldprice", $ofr["CATALOG_PRICE_" . $basePriceId]);
                        $offer->appendChild($oldPrice);
                    } else {
                        $offerPrice = $dom->createElement("price", $ofr["CATALOG_PRICE_" . $basePriceId]);
                        $offer->appendChild($offerPrice);
                    }
                    $offerCurrencyId = $dom->createElement("currencyId", htmlspecialchars($ofr["CATALOG_CURRENCY_" . $basePriceId], ENT_XML1 | ENT_QUOTES));
                    $offer->appendChild($offerCurrencyId);
                    $offerCategoryId = $dom->createElement("categoryId", Helper::getSectionCode($prods[$prodId]["IBLOCK_SECTION_ID"]));
                    $offer->appendChild($offerCategoryId);
                    $img = $ofr['DETAIL_PICTURE'] ?: $ofr['PREVIEW_PICTURE'];
                    if (!empty($img)) {
                        $url = self::getPictureUrl($img);
                    } else {
                        $img = $prods[$prodId]['DETAIL_PICTURE'] ?: $prods[$prodId]['PREVIEW_PICTURE'];
                        $url = self::getPictureUrl($img);
                    }
                    if ($url) {
                        $offerPicture = $dom->createElement("picture", htmlspecialchars(self::getProtocol() . $url, ENT_XML1 | ENT_QUOTES));
                        $offer->appendChild($offerPicture);
                    }
                    $ofr['props'] = array_merge($ofr['props'], $prods[$prodId]["props"]);
                    if (!empty($ofr['props'])) {
                        foreach ($ofr['props'] as $prop) {
                            if (!empty($prop['VALUE'])) {
                                if (is_array($prop['VALUE'])) {
                                    $prop['VALUE'] = implode('|', $prop['VALUE']);
                                }
                                if (empty($prop['CODE'])) {
                                    $prop['CODE'] = $prop['XML_ID'];
                                }
                                $prop['CODE'] = str_replace('_', '', $prop['CODE']);
                                $param = $dom->createElement('param', htmlspecialchars($prop['VALUE'], ENT_XML1 | ENT_QUOTES));
                                $param->setAttribute("name", $prop["CODE"]);

                                $offer->appendChild($param);
                            }
                        }
                    }
                }
                if (array_key_exists($prodId, $prods)) {
                    unset($prods[$prodId]);
                }
            }
        }

        if (!empty($prods)) {
            foreach ($prods as $prod) {
                $offer = $dom->createElement("offer");
                $offer->setAttribute("id", Helper::getElementCode($prod["ID"]));
                $available = ($prod['CATALOG_AVAILABLE'] === 'Y' && $prod['ACTIVE'] === 'Y') ? 'true' : 'false';
                $offer->setAttribute("available", $available);
                unset($available);
                $offer = $offers->appendChild($offer);
                $offerName = $dom->createElement("name", htmlspecialchars($prod["NAME"], ENT_XML1 | ENT_QUOTES));
                $offer->appendChild($offerName);
                if (!empty($prod["PREVIEW_TEXT"])) {
                    $offerDescription = $dom->createElement("description", htmlspecialchars($prod["PREVIEW_TEXT"], ENT_XML1 | ENT_QUOTES));
                    $offer->appendChild($offerDescription);
                }
                if ($prod["DETAIL_PAGE_URL"]) {
                    $offerUrl = $dom->createElement("url", htmlspecialchars(self::getProtocol() . $_SERVER["SERVER_NAME"] . $prod["DETAIL_PAGE_URL"], ENT_XML1 | ENT_QUOTES));
                    $offer->appendChild($offerUrl);
                }
                if (!empty($prod['prices']) && $prod['prices']['RESULT_PRICE']['BASE_PRICE'] !== $prod['prices']['RESULT_PRICE']['DISCOUNT_PRICE']) {
                    $offerPrice = $dom->createElement("price", $prod['prices']['RESULT_PRICE']['DISCOUNT_PRICE']);
                    $offer->appendChild($offerPrice);
                    $oldPrice = $dom->createElement("oldprice", $prod["CATALOG_PRICE_" . $basePriceId]);
                    $offer->appendChild($oldPrice);
                } else {
                    $offerPrice = $dom->createElement("price", $prod["CATALOG_PRICE_" . $basePriceId]);
                    $offer->appendChild($offerPrice);
                }
                $offerCurrencyId = $dom->createElement("currencyId", htmlspecialchars($prod["CATALOG_CURRENCY_" . $basePriceId], ENT_XML1 | ENT_QUOTES));
                $offer->appendChild($offerCurrencyId);
                $offerCategoryId = $dom->createElement("categoryId", Helper::getSectionCode($prod["IBLOCK_SECTION_ID"]));
                $offer->appendChild($offerCategoryId);
                $img = $prod['DETAIL_PICTURE'] ?: $prod['PREVIEW_PICTURE'];
                $url = self::getPictureUrl($img);
                if ($url) {
                    $offerPicture = $dom->createElement("picture", htmlspecialchars(self::getProtocol() . $url, ENT_XML1 | ENT_QUOTES));
                    $offer->appendChild($offerPicture);
                }
                if (!empty($prod['props'])) {
                    foreach ($prod['props'] as $prop) {
                        if (!empty($prop['VALUE'])) {
                            if (is_array($prop['VALUE'])) {
                                $prop['VALUE'] = implode('|', $prop['VALUE']);
                            }
                            if (empty($prop['CODE'])) {
                                $prop['CODE'] = $prop['XML_ID'];
                            }
                            $prop['CODE'] = str_replace('_', '', $prop['CODE']);
                            $param = $dom->createElement('param', htmlspecialchars($prop['VALUE'], ENT_XML1 | ENT_QUOTES));
                            $param->setAttribute("name", $prop["CODE"]);

                            $offer->appendChild($param);
                        }
                    }
                }
            }
        }

        if ($step === (int) ceil(self::getProdsCount() / self::$stepSize)) {
            $dom->save($_SERVER["DOCUMENT_ROOT"] . "/" . Options::getModuleOption("YML_NAME"));
        } else {
            $dom->save($_SERVER["DOCUMENT_ROOT"] . "/" . Options::getModuleOption("YML_NAME").'.raw');
        }
    }

    /**
     * Получает протокол.
     * @return string
     */
    protected static function getProtocol()
    {
        return Options::getModuleOption('PROTOCOL') === 'Y' ? 'https://' : 'http://';
    }

    /**
     * Возвращает категории.
     * @return mixed (CIBlockResult)
     */
    protected static function getCategories()
    {
        $arSelect = array(
            "ID",
            "XML_ID",
            "IBLOCK_ID",
            "IBLOCK_SECTION_ID",
            "NAME"
        );
        $arFilter = array(
            "IBLOCK_ID" => intval(Options::getModuleOption("CATALOG_IBLOCK_ID")),
            "ACTIVE" => "Y"
        );
        return \CIBlockSection::GetList(
            array("SORT" => "ASC"),
            $arFilter,
            false,
            $arSelect,
            false
        );
    }

    /**
     * Возвращает валюты.
     * @return mixed (CIBlockResult)
     */
    protected static function getCurrencies()
    {
        return \CCurrency::GetList(($by = "name"), ($order = "asc"), LANGUAGE_ID);
    }

    /**
     * Возвращает список торговых предложений
     * @param string $basePriceId id базовой цены
     * @param array $prodIds Массив id товаров
     * @return mixed
     */
    protected static function getOffers($basePriceId, $prodIds)
    {
        $offersCatalogId = \CCatalog::GetList([], ['IBLOCK_ID' => Options::getModuleOption("CATALOG_IBLOCK_ID")], false, [], ['ID', 'IBLOCK_ID', 'OFFERS_IBLOCK_ID'])->Fetch()['OFFERS_IBLOCK_ID'];

        $arSelect = array("ID",
            "IBLOCK_ID",
            "NAME",
            "DETAIL_PAGE_URL",
            "CATALOG_GROUP_" . $basePriceId,
            "IBLOCK_SECTION_ID",
            "DETAIL_PICTURE",
            "PREVIEW_PICTURE",
            "XML_ID",
            "ACTIVE"
        );

        $offersByProducts = \CCatalogSKU::getOffersList(
            $prodIds,
            (int) Options::getModuleOption("CATALOG_IBLOCK_ID"),
            array(),
            $arSelect,
            array()
        );

        $addProps = Options::getModuleOption("CATALOG_OFFER_PROPS");
        if (!empty($addProps)) {
            $addProps = explode(',', $addProps);

            $props = self::getProps($addProps, $offersCatalogId);

            foreach ($offersByProducts as &$offers) {
                foreach ($offers as $offerId => &$offer) {
                    $offer['prices'] = \CCatalogProduct::GetOptimalPrice($offer['ID']);
                    if (!empty($props[$offerId])) {
                        $offer['props'] = $props[$offerId];
                    }
                }
            }
        }

        return $offersByProducts;
    }

    /**
     * Возвращает id базовой цены
     * @return string
     */
    protected static function getBasePriceId()
    {
        $price = \CCatalogGroup::GetList(
            array(),
            array("BASE" => "Y"),
            false,
            false,
            array("ID")
        );
        $price = $price->GetNext();
        return $price["ID"];
    }

    public static function getIblockInfo($iblockId)
    {
        return \CIBlock::GetByID($iblockId)->Fetch();
    }

    protected static function getProdsCount()
    {
        return (int) (\CIBlockElement::GetList(
            ['SORT' => 'ASC'],
            ['IBLOCK_ID' => (int) Options::getModuleOption('CATALOG_IBLOCK_ID')],
            false,
            false,
            ['IBLOCK_ID', 'ID']
        ))->result->num_rows;
    }

    /**
     * Возвращает массив с информацией о продуктах, где ключ - это id продукта
     * @param string $basePriceId id базовой цены
     * @return array
     */
    protected static function getProds($basePriceId, $step)
    {
        $arSelect = array(
            "IBLOCK_ID",
            "ID",
            "IBLOCK_SECTION_ID",
            "DETAIL_PAGE_URL",
            "CATALOG_GROUP_" . $basePriceId,
            "NAME",
            "DETAIL_PICTURE",
            "PREVIEW_PICTURE",
            "PREVIEW_TEXT",
            "XML_ID",
            "ACTIVE"
        );

        $prods = \CIBlockElement::GetList(
            array("SORT" => "ASC"),
            array("IBLOCK_ID" => (int)Options::getModuleOption("CATALOG_IBLOCK_ID")),
            false,
            ['nPageSize' => self::$stepSize, 'iNumPage' => $step],
            $arSelect
        );

        while ($prod = $prods->GetNext()) {
            if (!$prod['XML_ID']) {
                $prod['XML_ID'] = $prod['ID'];
            }
            $prod['prices'] = \CCatalogProduct::GetOptimalPrice($prod['ID']);
            $prodsInfo[$prod["ID"]] = $prod;
        }

        $addProps = Options::getModuleOption("CATALOG_PROPS");
        if (!empty($addProps)) {
            $addProps = explode(',', $addProps);
            $props = self::getProps($addProps, Options::getModuleOption("CATALOG_IBLOCK_ID"), self::getProdsIds($prodsInfo));
            foreach ($props as $elementId => $prop) {
                $prodsInfo[$elementId]['props'] = $prop;
            }
        }

        return $prodsInfo;
    }

    protected static function getProps($propSelect, $iblockId, $prodsId = [])
    {
        $propNames = array_map(static function ($prop) {
            return str_replace('PROPERTY_', '', $prop);
        }, $propSelect);

        $info = self::getIblockInfo($iblockId);
        $minimalSelect = ['ID', 'IBLOCK_ID'];
        $prodsInfo = [];

        if ($info['VERSION'] === '1' && count($propSelect) > 25) {
            $propSelect = array_chunk($propSelect, 17);

            foreach ($propSelect as $select) {
                $select = array_merge($select, $minimalSelect);

                $prods = \CIBlockElement::GetList(
                    ['sort' => 'asc'],
                    ["IBLOCK_ID" => $iblockId, 'ID' => $prodsId],
                    false,
                    false,
                    $select
                );

                while ($prod = $prods->GetNextElement()) {
                    $fields = $prod->GetFields();
                    $properties = $prod->GetProperties();
                    $prodsInfo[$fields["ID"]] = array_filter($properties, static function ($prop) use ($propNames) {
                        return in_array($prop['CODE'], $propNames, true);
                    });
                }
            }
        } else {
            $propSelect = array_merge($propSelect, $minimalSelect);
            $prods = \CIBlockElement::GetList(
                ['sort' => 'asc'],
                ["IBLOCK_ID" => $iblockId, 'ID' => $prodsId],
                false,
                false,
                $propSelect
            );

            while ($prod = $prods->GetNextElement()) {
                $fields = $prod->GetFields();
                $properties = $prod->GetProperties();
                $prodsInfo[$fields["ID"]] = array_filter($properties, static function ($prop) use ($propNames) {
                    return in_array($prop['CODE'], $propNames, true);
                });
            }
        }

        return $prodsInfo;
    }

    /**
     * Возвращает массив с id продуктов
     * @param array $prods массив с продуктами, где ключ - это id продукта
     * @return array
     */
    protected static function getProdsIds($prods)
    {
        return array_keys($prods);
    }

    /**
     * Возвращает путь до изображения от корня
     * @param string $id id изображения
     * @return string
     */
    protected static function getPictureUrl($id)
    {
        $url = \CFile::GetPath($id);
        if (!$url) {
            return false;
        }
        return $_SERVER["SERVER_NAME"] . $url;
    }

    /**
     * Возвращает название сайта
     * @return string
     */
    protected static function getSiteName()
    {
        $siteInfo = \CSite::GetList($by = "sort", $order = "asc", ["ACTIVE" => "Y"]);
        $siteInfo = $siteInfo->Fetch();
        $siteName = '';
        if (!$siteInfo) {
            $siteName = 'sitename';
        } else {
            $siteName = $siteInfo['SITE_NAME'];
        }
        return !empty($siteName) ? $siteName : 'sitename';
    }
}
