<?php

namespace Mindbox;

use Bitrix\Catalog\PriceTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Text\Encoding;
use domDocument;

class YmlFeedMindbox
{
    const DESCRIPTION_TEXT_LENGTH = 3000;

    protected $iblockId = 0;

    protected $lid = '';

    protected $protocol = 'https://';

    protected $basePriceId = 0;

    protected $productCount = 0;

    protected $stepSize = 1000;

    protected $products = [];
    protected $offers = [];

    protected $catalogPropertyCode = [];
    protected $offersPropertyCode = [];

    public function __construct()
    {
        $this->setIblockId(Options::getModuleOption('CATALOG_IBLOCK_ID'));
        $this->setLid();
        $this->setProtocol();
        $this->setBasePriceId();
        $this->setProductCount();
        $this->setStepSize(Options::getModuleOption('YML_CHUNK_SIZE'));
        $this->setCatalogPropertyCode(Options::getModuleOption('CATALOG_PROPS'));
        $this->setOffersPropertyCode(Options::getModuleOption('CATALOG_OFFER_PROPS'));
    }

    public static function start($step = 1)
    {
        if (!isset($_SERVER['SERVER_NAME']) || empty($_SERVER['SERVER_NAME'])) {
            $_SERVER['SERVER_NAME'] = SITE_SERVER_NAME;
        }

        $cond = empty(Options::getModuleOption('YML_NAME')) || empty(Options::getModuleOption('CATALOG_IBLOCK_ID'));

        if ($cond) {
            return "\Mindbox\YmlFeedMindbox::start();";
        } elseif (!\Bitrix\Main\Loader::includeModule('currency')) {
            return "\Mindbox\YmlFeedMindbox::start();";
        } elseif (!\Bitrix\Main\Loader::includeModule('iblock')) {
            return "\Mindbox\YmlFeedMindbox::start();";
        } elseif (!\Bitrix\Main\Loader::includeModule('catalog')) {
            return "\Mindbox\YmlFeedMindbox::start();";
        }

        $ymlObj = new self();

        $ymlObj->generateYml();

        return "\Mindbox\YmlFeedMindbox::start();";
    }

    public function generateYml()
    {
        $dom = new domDocument('1.0', 'utf-8');

        $root = $dom->createElement('yml_catalog');
        $root->setAttribute('date', date('Y-m-d H:i'));
        $dom->appendChild($root);

        $shop = $dom->createElement('shop');
        $shop = $root->appendChild($shop);

        $name = self::yandexText2xml(self::getSiteName());

        $siteName = $dom->createElement('name', $name);
        $shop->appendChild($siteName);

        $companyName = $dom->createElement('company', $name);
        $shop->appendChild($companyName);

        $siteUrl = $dom->createElement('url', self::yandexText2xml($this->getProtocol() . $_SERVER['SERVER_NAME']));
        $shop->appendChild($siteUrl);

        $currencies = $dom->createElement('currencies');
        $currencies = $shop->appendChild($currencies);

        $crncs = self::getCurrencies();
        while ($crnc = $crncs->Fetch()) {
            $currencie = $dom->createElement('currency');
            $currencie->setAttribute('id', self::yandexText2xml($crnc['CURRENCY']));
            $currencie->setAttribute('rate', self::yandexText2xml((int)$crnc['AMOUNT']));
            $currencies->appendChild($currencie);
        }

        $categories = $dom->createElement('categories');
        $categories = $shop->appendChild($categories);

        $dbCats = $this->getCategories();
        while ($cat = $dbCats->fetch()) {
            $category = $dom->createElement('category', self::yandexText2xml($cat['NAME']));
            $category->setAttribute('id', Helper::getSectionCode($cat['ID']));

            if (isset($cat['IBLOCK_SECTION_ID']) && !empty($cat['IBLOCK_SECTION_ID'])) {
                $category->setAttribute('parentId', Helper::getSectionCode($cat['IBLOCK_SECTION_ID']));
            }

            $categories->appendChild($category);
        }

        $offers = $dom->createElement('offers');
        $offers = $shop->appendChild($offers);

        $countChunk = ceil($this->getProductCount() / $this->getStepSize());

        for ($step = 1; $step <= $countChunk; $step++) {
            $this->loadProducts($this->getStepSize(), $step);
            $this->loadOffers();

            foreach ($this->offers as $prodId => $ofrs) {
                foreach ($ofrs as $ofr) {
                    $offer = $dom->createElement('offer');

                    $offer->setAttribute('group_id', Helper::getElementCode($this->products[$prodId]['ID']));
                    $offer->setAttribute('id', Helper::getElementCode($ofr['ID']));

                    $available = ($ofr['CATALOG_AVAILABLE'] === 'Y' && $ofr['ACTIVE'] === 'Y') ? 'true' : 'false';
                    $offer->setAttribute('available', $available);

                    unset($available);

                    $offer = $offers->appendChild($offer);
                    if (!empty($ofr['NAME'])) {
                        $name = self::yandexText2xml($ofr['NAME']);
                    } else {
                        $name = self::yandexText2xml($this->products[$prodId]['NAME']);
                    }

                    $offerName = $dom->createElement('name', $name);
                    $offer->appendChild($offerName);

                    // description
                    if (!empty($ofr['~DETAIL_TEXT'])) {
                        $description = TruncateText($ofr['~DETAIL_TEXT'], self::DESCRIPTION_TEXT_LENGTH);
                    } else {
                        $description = TruncateText($this->products[$prodId]['~DETAIL_TEXT'], self::DESCRIPTION_TEXT_LENGTH);
                    }

                    if (empty($description)) {
                        if (!empty($ofr['~PREVIEW_TEXT'])) {
                            $description = TruncateText($ofr['~PREVIEW_TEXT'], self::DESCRIPTION_TEXT_LENGTH);
                        } else {
                            $description = TruncateText($this->products[$prodId]['~PREVIEW_TEXT'], self::DESCRIPTION_TEXT_LENGTH);
                        }
                    }

                    if (!empty($description)) {
                        $cdataDescription = $dom->createCDATASection($description);
                        $offerDescription = $dom->createElement('description');
                        $offerDescription->appendChild($cdataDescription);
                        $offer->appendChild($offerDescription);
                    }

                    // url
                    if ($this->products[$prodId]['DETAIL_PAGE_URL']) {
                        $offerUrl = $dom->createElement('url', self::yandexText2xml($this->getProtocol() . $_SERVER['SERVER_NAME'] . $this->products[$prodId]['DETAIL_PAGE_URL']));
                        $offer->appendChild($offerUrl);
                    }

                    // prices
                    if (!empty($ofr['prices']) && $ofr['prices']['RESULT_PRICE']['BASE_PRICE'] !== $ofr['prices']['RESULT_PRICE']['DISCOUNT_PRICE']) {
                        $offerPrice = $dom->createElement('price', $ofr['prices']['RESULT_PRICE']['DISCOUNT_PRICE']);
                        $offer->appendChild($offerPrice);
                        $oldPrice = $dom->createElement('oldprice', $ofr['prices']['RESULT_PRICE']['BASE_PRICE']);
                        $offer->appendChild($oldPrice);
                    } else {
                        $offerPrice = $dom->createElement('price', $ofr['CATALOG_PRICE_' . $this->getBasePriceId()]);
                        $offer->appendChild($offerPrice);
                    }

                    $offerCurrencyId = $dom->createElement('currencyId', self::yandexText2xml($ofr['CATALOG_CURRENCY_' . $this->getBasePriceId()]));
                    $offer->appendChild($offerCurrencyId);

                    // categories
                    $productCategoryList = $this->getProductGroups($prodId);
                    foreach ($productCategoryList as $productCategoryId) {
                        $offerCategoryId = $dom->createElement('categoryId', Helper::getSectionCode($productCategoryId));
                        $offer->appendChild($offerCategoryId);
                    }

                    // picture
                    $img = $ofr['DETAIL_PICTURE'] ?: $ofr['PREVIEW_PICTURE'];
                    if (!empty($img)) {
                        $url = self::getPictureUrl($img);
                    } else {
                        $img = $this->products[$prodId]['DETAIL_PICTURE'] ?: $this->products[$prodId]['PREVIEW_PICTURE'];
                        $url = self::getPictureUrl($img);
                    }
                    if ($url) {
                        $offerPicture = $dom->createElement('picture', self::yandexText2xml($this->getProtocol() . $url));
                        $offer->appendChild($offerPicture);
                    }

                    // properties
                    $ofr['properties'] = array_merge($ofr['properties'], $this->products[$prodId]['properties']);

                    foreach ($ofr['properties'] as $property) {
                        if (!empty($property['VALUE'])) {
                            if (is_array($property['VALUE'])) {
                                $property['VALUE'] = implode('|', $property['VALUE']);
                            }

                            if (empty($property['CODE'])) {
                                $prop['CODE'] = $property['XML_ID'];
                            }

                            $property['CODE'] = str_replace('_', '', $property['CODE']);
                            $param = $dom->createElement('param', self::yandexText2xml($property['VALUE']));
                            $param->setAttribute('name', $property['CODE']);

                            $offer->appendChild($param);
                        }
                    }
                }

                if (array_key_exists($prodId, $this->products)) {
                    unset($this->products[$prodId]);
                }
            }
            $this->offers = [];

            foreach ($this->products as $product) {
                $offer = $dom->createElement('offer');
                $offer->setAttribute('id', Helper::getElementCode($product['ID']));

                $available = ($product['CATALOG_AVAILABLE'] === 'Y' && $product['ACTIVE'] === 'Y') ? 'true' : 'false';

                $offer->setAttribute('available', $available);
                unset($available);

                $offer = $offers->appendChild($offer);
                $offerName = $dom->createElement('name', self::yandexText2xml($product['NAME']));
                $offer->appendChild($offerName);

                // description
                if (!empty($product['PREVIEW_TEXT'])) {
                    $offerDescription = $dom->createElement('description', self::yandexText2xml($product['PREVIEW_TEXT']));
                    $offer->appendChild($offerDescription);
                }

                // url
                if ($product['DETAIL_PAGE_URL']) {
                    $offerUrl = $dom->createElement('url', self::yandexText2xml($this->getProtocol() . $_SERVER['SERVER_NAME'] . $product['DETAIL_PAGE_URL']));
                    $offer->appendChild($offerUrl);
                }

                if (!empty($product['prices']) && $product['prices']['RESULT_PRICE']['BASE_PRICE'] !== $product['prices']['RESULT_PRICE']['DISCOUNT_PRICE']) {
                    $offerPrice = $dom->createElement('price', $product['prices']['RESULT_PRICE']['DISCOUNT_PRICE']);
                    $offer->appendChild($offerPrice);
                    $oldPrice = $dom->createElement('oldprice', $product['prices']['RESULT_PRICE']['BASE_PRICE']);
                    $offer->appendChild($oldPrice);
                } else {
                    $offerPrice = $dom->createElement('price', $product['CATALOG_PRICE_' . $this->getBasePriceId()]);
                    $offer->appendChild($offerPrice);
                }

                $offerCurrencyId = $dom->createElement('currencyId', self::yandexText2xml($product['CATALOG_CURRENCY_' . $this->getBasePriceId()]));
                $offer->appendChild($offerCurrencyId);

                // category
                $productCategoryList = $this->getProductGroups($product['ID']);

                foreach ($productCategoryList as $productCategoryId) {
                    $offerCategoryId = $dom->createElement('categoryId', Helper::getSectionCode($productCategoryId));
                    $offer->appendChild($offerCategoryId);
                }

                // picture
                $img = $product['DETAIL_PICTURE'] ?: $product['PREVIEW_PICTURE'];
                $url = self::getPictureUrl($img);
                if ($url) {
                    $offerPicture = $dom->createElement('picture', self::yandexText2xml($this->getProtocol() . $url));
                    $offer->appendChild($offerPicture);
                }

                // property
                foreach ($product['properties'] as $property) {
                    if (!empty($property['VALUE'])) {
                        if (is_array($property['VALUE'])) {
                            $property['VALUE'] = implode('|', $property['VALUE']);
                        }

                        if (empty($property['CODE'])) {
                            $property['CODE'] = $property['XML_ID'];
                        }

                        $property['CODE'] = str_replace('_', '', $property['CODE']);
                        $param = $dom->createElement('param', self::yandexText2xml($property['VALUE']));
                        $param->setAttribute('name', $property['CODE']);

                        $offer->appendChild($param);
                    }
                }
            }
            $this->products = [];
        }

        $dom->save($_SERVER['DOCUMENT_ROOT'] . '/' . Options::getModuleOption('YML_NAME'));
    }

    public function setProtocol(): void
    {
        $this->protocol = Options::getModuleOption('PROTOCOL') === 'Y' ? 'https://' : 'http://';
    }

    /**
     * Получает протокол.
     * @return string
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * Возвращает категории.
     */
    public function getCategories()
    {
        $arSelect = [
            'ID',
            'IBLOCK_SECTION_ID',
            'NAME'
        ];

        $arFilter = [
            '=IBLOCK_ID' => $this->getIblockId(),
            '=ACTIVE' => 'Y'
        ];

        return SectionTable::getList([
            'filter' => $arFilter,
            'select' => $arSelect,
            'order' => ['SORT' => 'ASC']
        ]);
    }

    /**
     * Возвращает валюты.
     * @return mixed (CIBlockResult)
     */
    protected static function getCurrencies()
    {
        return \CCurrency::GetList(($by = 'name'), ($order = 'asc'), LANGUAGE_ID);
    }

    /**
     * Возвращает список категорий, к которому принадлежит элемент
     * @param int $productId
     * @return array
     */
    public function getProductGroups($productId)
    {
        $return = [];

        if (!empty($productId)) {
            $getElementGroups = \CIBlockElement::GetElementGroups($productId, false, ['ID', 'ACTIVE']);

            while ($item = $getElementGroups->Fetch()) {
                if ($item['ACTIVE'] === 'Y') {
                    $return[$item['ID']] = $item['ID'];
                }
            }
        }

        unset($getElementGroups, $item);

        return $return;
    }

    /**
     * Загружаем товары
     * @param integer $limit  Количество выбираемых данныхы
     * @param integer $offset Шаг смещения в запросе
     */
    public function loadProducts($limit, $offset)
    {
        $arSelect = array(
            'ID',
            'IBLOCK_ID',
            'IBLOCK_SECTION_ID',
            'DETAIL_PAGE_URL',
            'CATALOG_GROUP_' . $this->getBasePriceId(),
            'NAME',
            'DETAIL_PICTURE',
            'DETAIL_TEXT',
            'PREVIEW_PICTURE',
            'PREVIEW_TEXT',
            'ACTIVE'
        );

        $iterator = \CIBlockElement::GetList(
            [],
            ['=IBLOCK_ID' => $this->getIblockId()],
            false,
            ['nPageSize' => $limit, 'iNumPage' => $offset],
            $arSelect
        );

        $arProductId = [];

        while ($prod = $iterator->GetNext()) {
            $arProductId[] = $prod['ID'];

            $prod['prices'] = \CCatalogProduct::GetOptimalPrice($prod['ID'], 1, [], 'N', [], $this->getLid());

            if ($prod['prices']['RESULT_PRICE']['PRICE_TYPE_ID'] !== $this->getBasePriceId()) {
                $prod['prices']['RESULT_PRICE'] = $this->getResultPrice($prod);
            }

            $prod['properties'] = [];

            $this->products[$prod['ID']] = $prod;
        }

        if (!empty($addProps = $this->getCatalogPropertyCode()) && !empty($arProductId)) {

            $properties = $this->getProperties($addProps, $this->getIblockId(), $arProductId);

            foreach ($properties as $elementId => $prop) {
                $this->products[$elementId]['properties'] = $prop;
            }
        }

        unset($arSelect, $iterator, $prod, $addProps, $arProductId, $props, $prop);
    }

    /**
     * Загружает список торговых предложений
     */
    public function loadOffers()
    {
        $offersCatalogId = (int)(\CCatalog::GetList(
                [],
                ['IBLOCK_ID' => $this->getIblockId()],
                false,
                [],
                ['ID', 'IBLOCK_ID', 'OFFERS_IBLOCK_ID']
        )->Fetch())['OFFERS_IBLOCK_ID'];

        if ($offersCatalogId <= 0) {
            return;
        }

        $arSelect = [
            'ID',
            'IBLOCK_ID',
            'NAME',
            'DETAIL_PAGE_URL',
            'CATALOG_GROUP_' . $this->getBasePriceId(),
            'IBLOCK_SECTION_ID',
            'DETAIL_PICTURE',
            'PREVIEW_PICTURE',
            'ACTIVE'
        ];

        $this->offers = \CCatalogSKU::getOffersList(
                array_keys($this->products),
                $this->getIblockId(),
                [],
                $arSelect,
                []
        );

        $arOfferId = [];
        foreach ($this->offers as $productId => &$offers) {
            foreach ($offers as &$offer) {
                $arOfferId[] = $offer['ID'];

                $offer['prices'] = \CCatalogProduct::GetOptimalPrice($offer['ID'], 1, [], 'N', [],  $this->getLid());

                $offer['properties'] = [];

                if ($offer['prices']['RESULT_PRICE']['PRICE_TYPE_ID'] !== $this->getBasePriceId()) {
                    $offer['prices']['RESULT_PRICE'] = $this->getResultPrice($offer);
                }

                if (array_key_exists($productId, $this->products)) {
                    $offer['ACTIVE'] = ($this->products[$productId]['ACTIVE'] == 'N')
                            ? $this->products[$productId]['ACTIVE']
                            : $offer['ACTIVE'];

                    $offer['CATALOG_AVAILABLE'] = ($this->products[$productId]['CATALOG_AVAILABLE'] == 'N')
                            ? $this->products[$productId]['CATALOG_AVAILABLE']
                            : $offer['CATALOG_AVAILABLE'];
                }
            }
        }

        if (!empty($addProps = $this->getOffersPropertyCode()) && !empty($arOfferId)) {
            $properties = $this->getProperties($addProps, $offersCatalogId, $arOfferId);

            foreach ($this->offers as &$offers) {
                foreach ($offers as $offerId => &$offer) {
                    if (!empty($properties[$offerId])) {
                        $offer['properties'] = $properties[$offerId];
                    }
                }
            }
        }

        unset($arOfferId, $addProps, $properties, $arSelect, $offersCatalogId);
    }

    /**
     * Получение значения свойст товаров
     * @param array $propertyCode - массив кодов свойств
     * @param int $iblockId - ID инфоблока
     * @param array $productIds - ID товаров
     * @return array|false
     */
    public function getProperties($propertyCode, $iblockId, &$productIds)
    {
        $elementIndex = array_combine($productIds, $productIds);

        \CIBlockElement::GetPropertyValuesArray(
                $elementIndex,
                $iblockId,
                ['ID' => $elementIndex],
                ['CODE' => $propertyCode],
                ['GET_RAW_DATA' => 'Y']
        );

        return $elementIndex;
    }

    /**
     * Возвращает массив с id продуктов
     * @param array $prods массив с продуктами, где ключ - это id продукта
     * @return array
     */
    protected static function getProductIds($productIds)
    {
        return array_keys($productIds);
    }

    /**
     * Возвращает информацию по инфоблоку
     * @param int $iblockId
     * @return array|false
     */
    public static function getIblockInfo($iblockId)
    {
        return \CIBlock::GetArrayByID($iblockId);
    }

    /**
     * Возвращает путь до изображения от корня
     * @param string $id id изображения
     * @return string
     */
    public static function getPictureUrl($id)
    {
        $url = \CFile::GetPath($id);

        if (!$url) {
            return false;
        }

        return $_SERVER['SERVER_NAME'] . $url;
    }

    /**
     * Возвращает название сайта
     * @return string
     */
    public static function getSiteName()
    {
        $siteInfo = \CSite::GetList($by = 'sort', $order = 'asc', ['ACTIVE' => 'Y']);
        $siteInfo = $siteInfo->Fetch();

        if (!$siteInfo) {
            $siteName = 'sitename';
        } else {
            $siteName = $siteInfo['SITE_NAME'];
        }

        return !empty($siteName) ? $siteName : 'sitename';
    }

    private static function yandexText2xml($text, $bHSC = true, $bDblQuote = false)
    {
        $bHSC = true == $bHSC;
        $bDblQuote = true == $bDblQuote;

        if ($bHSC) {
            $text = htmlspecialcharsbx($text);
            if ($bDblQuote) {
                $text = str_replace('&quot;', '"', $text);
            }
        }

        $text = preg_replace('/[\x1-\x8\xB-\xC\xE-\x1F]/', '', $text);
        $text = str_replace("'", "&apos;", $text);
        $text = Encoding::convertEncoding($text, LANG_CHARSET, 'UTF-8');

        return $text;
    }

    protected function setLid()
    {
        $info = self::getIblockInfo($this->getIblockId());

        $this->lid = $info['LID'];
    }

    /**
     * Возвращает ID сайта
     * @return string
     */
    public function getLid()
    {
        return $this->lid;
    }

    protected function setBasePriceId()
    {
        $priceGroup = \Bitrix\Catalog\GroupTable::getList([
            'filter' =>  ['BASE' => 'Y'],
            'select' => ['ID'],
            'limit' => 1
        ])->fetch();

        $this->basePriceId = $priceGroup['ID'];
    }

    /**
     * Возвращает ID группы базовой цены
     * @return int
     */
    public function getBasePriceId()
    {
        return $this->basePriceId;
    }

    protected function setStepSize($stepSize)
    {
        $stepSize = (int) $stepSize;

        if ($stepSize > 0) {
            $this->stepSize = $stepSize;
        }
    }

    /**
     * Возвращает размер чанка для товаров
     * @return int
     */
    public function getStepSize()
    {
        return $this->stepSize;
    }

    protected function setIblockId($iblockId)
    {
        $this->iblockId = (int)$iblockId;
    }

    /**
     * Возвращает ID инфоблока
     * @return int
     */
    public function getIblockId()
    {
        return $this->iblockId;
    }

    protected function setProductCount()
    {
        $iter = ElementTable::getList([
            'filter' => [
                '=WF_STATUS_ID' => 1,
                '=WF_PARENT_ELEMENT_ID' => null,
                '=IBLOCK_ID' => $this->getIblockId()
            ],
            'select' => ['ID']
        ]);

        $this->productCount = $iter->getSelectedRowsCount();

        unset($iter);
    }

    /**
     * Возвращает общее количество товаров
     * @return int
     */
    public function getProductCount()
    {
        return $this->productCount;
    }

    protected function setCatalogPropertyCode($strProperty)
    {
        if (!empty($strProperty)) {
            $arProperty = explode(',', $strProperty);

            $this->catalogPropertyCode = array_map(static function ($prop) {
                return str_replace('PROPERTY_', '', $prop);
            }, $arProperty);
        }
    }

    /**
     * Возвращает массив кодов свойств для товаров
     * @return array
     */
    public function getCatalogPropertyCode()
    {
        return $this->catalogPropertyCode;
    }

    protected function setOffersPropertyCode($strProperty)
    {
        if (!empty($strProperty)) {
            $arProperty = explode(',', $strProperty);

            $this->offersPropertyCode = array_map(static function ($prop) {
                return str_replace('PROPERTY_', '', $prop);
            }, $arProperty);
        }
    }

    /**
     * Возвращает массив кодов свойств для торговых предложений
     * @return array
     */
    public function getOffersPropertyCode()
    {
        return $this->offersPropertyCode;
    }

    public function getResultPrice($element)
    {
        $arResultPrices = $element['prices']['RESULT_PRICE'];

        $iterator = PriceTable::getList([
            'select' => ['CATALOG_GROUP_ID', 'PRICE'],
            'filter' => [
                '=PRODUCT_ID' => (int)$element['ID'],
                '=CATALOG_GROUP_ID' => $this->getBasePriceId()
            ],
        ]);

        if ($price = $iterator->fetch()) {
            $arResultPrices['BASE_PRICE'] = roundEx($price['PRICE'], 2);
            $arResultPrices['UNROUND_BASE_PRICE'] = $price['PRICE'];
        }

        return $arResultPrices;
    }
}
