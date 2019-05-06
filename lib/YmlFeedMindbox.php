<?php

namespace Mindbox;
use domDocument;

class YmlFeedMindbox
{
    public static function start()
    {
        $cond = empty(Options::getModuleOption("YML_NAME")) || empty(Options::getModuleOption("USE_SKU")) || empty(Options::getModuleOption("CATALOG_IBLOCK_ID"));

        if ($cond) {
            return '\Mindbox\YmlFeedMindbox::start();';
        } elseif (!\CModule::IncludeModule("currency")) {
            return '\Mindbox\YmlFeedMindbox::start();';
        } elseif (!\CModule::IncludeModule("iblock")) {
            return '\Mindbox\YmlFeedMindbox::start();';
        } elseif (!\CModule::IncludeModule("catalog")) {
            return '\Mindbox\YmlFeedMindbox::start();';
        }
        self::generateYml();
        return '\Mindbox\YmlFeedMindbox::start();';
    }

    protected static function generateYml()
    {
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
        $cats = self::getCategories();
        while ($cat = $cats->GetNext()) {
            $category = $dom->createElement("category", htmlspecialchars($cat["NAME"], ENT_XML1 | ENT_QUOTES));
            $category->setAttribute("id", $cat["ID"]);
            if (isset($cat["IBLOCK_SECTION_ID"]) && !empty($cat["IBLOCK_SECTION_ID"])) {
                $category->setAttribute("parentId", $cat["IBLOCK_SECTION_ID"]);
            }
            $categories->appendChild($category);
        }

        $offers = $dom->createElement("offers");
        $offers = $shop->appendChild($offers);

        $basePriceId = self::getBasePriceId();
        $prods = self::getProds($basePriceId);

        if (Options::getModuleOption("USE_SKU")) {
            $prodIds = self::getProdsIds($prods);
            $prodsOfrs = self::getOffers($basePriceId, $prodIds);
            foreach ($prodsOfrs as $prodId => $ofrs) {
                foreach ($ofrs as $ofr) {
                    $offer = $dom->createElement("offer");
                    $offer->setAttribute("group_id", $prods[$prodId]['XML_ID']);
                    $offer->setAttribute("id", $ofr["XML_ID"]);
                    $offer = $offers->appendChild($offer);
                    $offerName = $dom->createElement("name", htmlspecialchars($ofr["NAME"], ENT_XML1 | ENT_QUOTES));
                    $offer->appendChild($offerName);
                    if ($prods[$prodId]["DETAIL_PAGE_URL"]) {
                        $offerUrl = $dom->createElement("url", htmlspecialchars(self::getProtocol() . $_SERVER["SERVER_NAME"] . $prods[$prodId]["DETAIL_PAGE_URL"], ENT_XML1 | ENT_QUOTES));
                        $offer->appendChild($offerUrl);
                    }
                    $offerPrice = $dom->createElement("price", $ofr["CATALOG_PRICE_" . $basePriceId]);
                    $offer->appendChild($offerPrice);
                    $offerCurrencyId = $dom->createElement("currencyId", htmlspecialchars($ofr["CATALOG_CURRENCY_" . $basePriceId], ENT_XML1 | ENT_QUOTES));
                    $offer->appendChild($offerCurrencyId);
                    $offerCategoryId = $dom->createElement("categoryId", $prods[$prodId]["IBLOCK_SECTION_ID"]);
                    $offer->appendChild($offerCategoryId);
                    $url = self::getPictureUrl($ofr["DETAIL_PICTURE"]);
                    if($url) {
                        $offerPicture = $dom->createElement("picture", htmlspecialchars(self::getProtocol() . $url, ENT_XML1 | ENT_QUOTES));
                        $offer->appendChild($offerPicture);
                    }

                }
                if (array_key_exists($prodId, $prods)) {
                    unset($prods[$prodId]);
                }
            }
        } else {
            foreach ($prods as $prod) {
                $offer = $dom->createElement("offer");
                $offer->setAttribute("id", $prod["XML_ID"]);
                $offer = $offers->appendChild($offer);
                $offerName = $dom->createElement("name", htmlspecialchars($prod["NAME"], ENT_XML1 | ENT_QUOTES));
                $offer->appendChild($offerName);
                if($prod["DETAIL_PAGE_URL"]) {
                    $offerUrl = $dom->createElement("url", htmlspecialchars(self::getProtocol() . $_SERVER["SERVER_NAME"] . $prod["DETAIL_PAGE_URL"], ENT_XML1 | ENT_QUOTES));
                    $offer->appendChild($offerUrl);
                }
                $offerPrice = $dom->createElement("price", $prod["CATALOG_PRICE_" . $basePriceId]);
                $offer->appendChild($offerPrice);
                $offerCurrencyId = $dom->createElement("currencyId", htmlspecialchars($prod["CATALOG_CURRENCY_" . $basePriceId], ENT_XML1 | ENT_QUOTES));
                $offer->appendChild($offerCurrencyId);
                $offerCategoryId = $dom->createElement("categoryId", $prod["IBLOCK_SECTION_ID"]);
                $offer->appendChild($offerCategoryId);
                $url = self::getPictureUrl($prod["DETAIL_PICTURE"]);
                if ($url) {
                    $offerPicture = $dom->createElement("picture", htmlspecialchars(self::getProtocol() . $url, ENT_XML1 | ENT_QUOTES));
                    $offer->appendChild($offerPicture);
                }

            }
        }

        $dom->save($_SERVER["DOCUMENT_ROOT"] . "/" . Options::getModuleOption("YML_NAME"));
    }

    /**
     * Получает протокол.
     * @return string
     */
    protected static function getProtocol()
    {
        return !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] != 'off' ? 'https://' : 'http://';
    }

    /**
     * Возвращает категории.
     * @return mixed (CIBlockResult)
     */
    protected static function getCategories()
    {
        $arSelect = array(
            "ID",
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
        $arSelect = array("ID",
            "IBLOCK_ID",
            "NAME",
            "DETAIL_PAGE_URL",
            "CATALOG_GROUP_" . $basePriceId,
            "IBLOCK_SECTION_ID",
            "DETAIL_PICTURE",
            "XML_ID"

        );
        return \CCatalogSKU::getOffersList(
            $prodIds,
            intval(Options::getModuleOption("CATALOG_IBLOCK_ID")),
            array("ACTIVE" => "Y"),
            $arSelect,
            array()
        );
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

    /**
     * Возвращает массив с информацией о продуктах, где ключ - это id продукта
     * @param string $basePriceId id базовой цены
     * @return array
     */
    protected static function getProds($basePriceId)
    {
        $arSelect = array(
            "IBLOCK_ID",
            "ID",
            "IBLOCK_SECTION_ID",
            "DETAIL_PAGE_URL",
            "CATALOG_GROUP_" . $basePriceId,
            "NAME",
            "DETAIL_PICTURE",
            "XML_ID"
        );

        $prods = \CIBlockElement::GetList(
            array("SORT" => "ASC"),
            array("IBLOCK_ID" => intval(Options::getModuleOption("CATALOG_IBLOCK_ID")), "ACTIVE" => "Y"),
            false,
            false,
            $arSelect
        );
        $prodsInfo = array();
        while ($prod = $prods->GetNext()) {
            $prodsInfo[$prod["ID"]] = $prod;
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
    protected static function getPictureUrl($id) {
        $url = \CFile::GetPath($id);
        if(!$url) {
            return false;
        }
        return $_SERVER["SERVER_NAME"] . $url;
    }

    /**
     * Возвращает название сайта
     * @return string
     */
    protected static function getSiteName() {
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