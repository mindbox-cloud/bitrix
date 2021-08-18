<?php

namespace Mindbox;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserTable;
use Bitrix\Sale;
use Bitrix\Main;
use CCatalog;
use CIBlock;
use COption;
use CPHPCache;
use CSaleOrderProps;
use Mindbox\DTO\DTO;
use Mindbox\DTO\DTOCollection;
use Mindbox\DTO\V3\Requests\CustomerIdentityRequestDTO;
use Mindbox\Options;
use Mindbox\Templates\AdminLayouts;
use Psr\Log\LoggerInterface;
use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\DTO\V3\Requests\ProductListItemRequestCollection;
use Mindbox\DTO\V3\Requests\ProductListItemRequestDTO;
use Mindbox\DTO\V3\Requests\ProductRequestDTO;
use Mindbox\DTO\V3\Requests\SubscriptionRequestCollection;

class Helper
{
    use AdminLayouts;

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
        $logger = new \Mindbox\Loggers\MindboxFileLogger(Options::getModuleOption('LOG_PATH'));

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

        if ($rsUser && isset($rsUser['UF_MINDBOX_ID']) && $rsUser['UF_MINDBOX_ID'] > 0) {
            $mindboxId = $rsUser['UF_MINDBOX_ID'];
        }

        if (!$mindboxId && \COption::GetOptionString('mindbox.marketing', 'MODE') != 'standard') {
            $mindbox = Options::getConfig();
            $request = $mindbox->getClientV3()->prepareRequest(
                'POST',
                Options::getOperationName('getCustomerInfo'),
                new DTO([
                    'customer' => [
                        'ids' => [
                            Options::getModuleOption('WEBSITE_ID') => $id
                        ]
                    ]
                ])
            );

            try {
                $response = $request->sendRequest();
            } catch (Exceptions\MindboxClientException $e) {
                // mindbox not available
                $message = date('d.m.Y H:i:s');
                $logger->error(
                    $message,
                    ['getCustomerInfo', [Options::getModuleOption('WEBSITE_ID') => $id], $e->getMessage()]
                );
            }

            if ($response && $response->getResult()->getCustomer()->getProcessingStatus() === 'Found') {
                $mindboxId = $response->getResult()->getCustomer()->getId('mindboxId');
                $fields = [
                    'UF_EMAIL_CONFIRMED' => $response->getResult()->getCustomer()->getIsEmailConfirmed(),
                    'UF_MINDBOX_ID'      => $mindboxId
                ];

                $user = new \CUser;
                $user->Update(
                    $id,
                    $fields
                );
                unset($_SESSION['NEW_USER_MB_ID']);
            } else {
                if ($response && $response->getResult()->getCustomer()->getProcessingStatus() === 'NotFound') {
                    $message = date('d.m.Y H:i:s');
                    $logger->error($message, [
                        'getCustomerInfo',
                        [Options::getModuleOption('WEBSITE_ID') => $id],
                        $response->getResult()->getCustomer()->getProcessingStatus()
                    ]);
                    $mindboxId = self::registerCustomer($id);
                }
            }
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

    private static function registerCustomer($websiteUserId)
    {
        global $APPLICATION, $USER;

        $rsUser = \CUser::GetByID($websiteUserId);
        $arFields = $rsUser->Fetch();

        $logger = new \Mindbox\Loggers\MindboxFileLogger(Options::getModuleOption('LOG_PATH'));

        $message = date('d.m.Y H:i:s');
        $logger->debug($message, ['$websiteUserId' => $websiteUserId, '$arFields' => $arFields]);

        $mindbox = Options::getConfig();

        if (!isset($arFields['PERSONAL_PHONE'])) {
            $arFields['PERSONAL_PHONE'] = $arFields['PERSONAL_MOBILE'];
        }

        if (isset($arFields['PERSONAL_PHONE'])) {
            $arFields['PERSONAL_PHONE'] = Helper::formatPhone($arFields['PERSONAL_PHONE']);
        }

        $sex = substr(ucfirst($arFields['PERSONAL_GENDER']), 0, 1) ?: null;
        $fields = [
            'email'       => $arFields['EMAIL'],
            'lastName'    => $arFields['LAST_NAME'],
            'middleName'  => $arFields['SECOND_NAME'],
            'firstName'   => $arFields['NAME'],
            'mobilePhone' => self::normalizePhoneNumber($arFields['PERSONAL_PHONE']),
            'birthDate'   => Helper::formatDate($arFields['PERSONAL_BIRTHDAY']),
            'sex'         => $sex,
        ];

        $fields = array_filter($fields, function ($item) {
            return isset($item);
        });

        $fields['subscriptions'] = [
            [
                'brand' => Options::getModuleOption('BRAND'),
                'isSubscribed'   => true,
            ]
        ];

        $customer = Helper::iconvDTO(new CustomerRequestDTO($fields));

        unset($fields);

        try {
            $registerResponse = $mindbox->customer()->register(
                $customer,
                Options::getOperationName('register'),
                true,
                Helper::isSync()
            )->sendRequest()->getResult();
        } catch (Exceptions\MindboxUnavailableException $e) {
            $lastResponse = $mindbox->customer()->getLastResponse();
            if ($lastResponse) {
                $request = $lastResponse->getRequest();
                QueueTable::push($request);
            }
        } catch (Exceptions\MindboxClientException $e) {
            $request = $mindbox->customer()->getRequest();
            if ($request) {
                QueueTable::push($request);
            }
        }

        if ($registerResponse) {
            $registerResponse = Helper::iconvDTO($registerResponse, false);
            $status = $registerResponse->getStatus();


            if ($status === 'ValidationError') {
                $errors = $registerResponse->getValidationMessages();
                $logger->error($message, ['ValidationError' => $errors]);

                return false;
            }

            $customer = $registerResponse->getCustomer();


            if (!$customer) {
                return false;
            }

            $mindboxId = $customer->getId('mindboxId');

            $logger->debug($message, ['$mindboxId' => $mindboxId]);

            $fields = [
                'UF_MINDBOX_ID' => $mindboxId
            ];

            $user = new \CUser;
            $user->Update(
                $websiteUserId,
                $fields
            );

            return $mindboxId;
        }

        return false;
    }

    private function normalizePhoneNumber($in)
    {
        $in = substr($in, 0, 11);
        $out = preg_replace(
            '/^(\d)(\d{3})(\d{3})(\d{2})(\d{2})$/',
            '+\1 (\2) \3 \4 \5',
            (string)$in
        );

        return $out;
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
     * Get element code by id
     * @param $elementId
     *
     * @return $productId
     */

    public static function getElementCode($elementId)
    {
        $arProduct = \CIBlockElement::GetByID($elementId)->GetNext();
        if ($arProduct['XML_ID']) {
            $elementId = $arProduct['XML_ID'];
        }

        return $elementId;
    }

    /**
     * Get section code by id
     * @param $sectionId
     *
     * @return $sectionId
     */

    public static function getSectionCode($sectionId)
    {
        $arSection = \CIBlockSection::GetByID($sectionId)->GetNext();
        if ($arSection['XML_ID']) {
            $sectionId = $arSection['XML_ID'];
        }

        return $sectionId;
    }

    /**
     * Get all iblocks
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
            $arIblock[$ar_res['ID']] = $ar_res['NAME'] . " (" . $ar_res['ID'] . ")";
        }

        return $arIblock;
    }

    /**
     * @return array
     */
    public static function getProps()
    {
        $props = [];

        $catalogId = COption::GetOptionString(ADMIN_MODULE_NAME, 'CATALOG_IBLOCK_ID', '0');
        if (!empty($catalogId) && $catalogId !== '0') {
            $iblockProperties = CIBlock::GetProperties($catalogId);
            while ($iblockProperty = $iblockProperties->Fetch()) {
                $props['PROPERTY_' . $iblockProperty['CODE']] = $iblockProperty['NAME'];
            }
        }

        return $props;
    }

    /**
     * @param string $catalogId
     * @return string
     */
    public static function getOffersCatalogId($catalogId)
    {
        if (!Loader::includeModule('sale') || !Loader::includeModule('catalog')) {
            return '';
        }

        if (!empty($catalogId) && $catalogId !== '0') {
            $select = ['ID', 'IBLOCK_ID', 'OFFERS_IBLOCK_ID'];
            $filter = ['IBLOCK_ID' => $catalogId];

            return CCatalog::GetList([], $filter, false, [], $select)->Fetch()['OFFERS_IBLOCK_ID'];
        }

        return '';
    }

    /**
     * @return array
     * @throws \Bitrix\Main\LoaderException
     */
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
            while ($iblockProperty = $iblockProperties->Fetch()) {
                $offerProps['PROPERTY_' . $iblockProperty['CODE']] = $iblockProperty['NAME'];
            }
        }

        return $offerProps;
    }

    /**
     * @return array
     */
    public static function getUserFields()
    {
        $dbFields = \CUserTypeEntity::GetList([], ['ENTITY_ID' => 'USER']);

        $userFields = [];
        while ($field = $dbFields->Fetch()) {
            $userFields[$field['FIELD_NAME']] = $field['FIELD_NAME'];
        }

        return $userFields;
    }

    /**
     * Get order fields
     *
     * @return array $orderFields
     */
    public static function getOrderFields()
    {
        \CModule::IncludeModule('sale');

        $dbProps = CSaleOrderProps::GetList(
            ['SORT' => 'ASC'],
            [],
            false,
            false,
            []
        );
        $orderProps = [];
        while ($prop = $dbProps->Fetch()) {
            $orderProps[$prop['CODE']] = $prop['NAME'];
        }

        return $orderProps;
    }

    public static function getMatchByCode($code, $matches = [])
    {
        if (empty($matches)) {
            $matches = self::getOrderFieldsMatch();
        }
        $matches = array_change_key_case($matches, CASE_UPPER);
        $code = mb_strtoupper($code);

        if (!empty($matches[$code])) {
            return $matches[$code];
        }

        return '';
    }

    /**
     * @return array
     */
    public static function getOrderFieldsMatch()
    {
        $fields = \COption::GetOptionString('mindbox.marketing', 'ORDER_FIELDS_MATCH', '{[]}');

        return json_decode($fields, true);
    }

    /**
     * @return array
     */
    public static function getUserFieldsMatch()
    {
        $fields = \COption::GetOptionString('mindbox.marketing', 'USER_FIELDS_MATCH', '{[]}');

        return json_decode($fields, true);
    }

    /**
     * @return array
     */
    public static function getCustomFieldsForUser($userId, $userFields = [])
    {
        if (empty($userFields)) {
            $customFields = [];
            $by = 'id';
            $order = 'asc';
            $userFields = \CUser::GetList($by, $order, ['ID' => $userId], ['SELECT' => ['UF_*']])->Fetch();
        }

        $fields = array_filter($userFields, function ($fields, $key) {
            return strpos($key, 'UF_') !== false;
        }, ARRAY_FILTER_USE_BOTH);

        foreach ($fields as $code => $value) {
            if (!empty($value) && !empty($customName = self::getMatchByCode($code, self::getUserFieldsMatch()))) {
                $customFields[self::sanitizeNamesForMindbox($customName)] = $value;
            }
        }

        return $customFields;
    }

    /**
     * @param string $name
     * @return string
     */
    public static function sanitizeNamesForMindbox($name)
    {
        $regexNotChars = '/[^a-zA-Z0-9]/m';
        $regexFirstLetter = '/^[a-zA-Z]/m';

        $name = preg_replace($regexNotChars, '', $name);
        if (!empty($name) && preg_match($regexFirstLetter, $name) === 1) {
            return $name;
        }

        return '';
    }

    /**
     * Is operations sync?
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
     * What is mode?
     *
     * @return boolean
     */
    public static function isStandardMode()
    {
        return COption::GetOptionString('mindbox.marketing', 'MODE') === 'standard';
    }


    /**
     * Check if order is unauthorized
     *
     * @return boolean
     */
    public static function isUnAuthorizedOrder($arUser)
    {
        return date('dmYHi', time()) === date('dmYHi', strtotime($arUser['DATE_REGISTER']));
    }

    /**
     *
     * Return transaction id
     *
     * @return int
     */
    public static function getTransactionId()
    {
        $transactionId = \Bitrix\Sale\Fuser::getId() . date('dmYHi');
        if (!$_SESSION['MINDBOX_TRANSACTION_ID']) {
            $_SESSION['MINDBOX_TRANSACTION_ID'] = $transactionId;

            return $transactionId;
        } else {
            return $_SESSION['MINDBOX_TRANSACTION_ID'];
        }
    }

    public static function processHlbBasketRule($lineId, $mindboxPrice)
    {

        $result = \Bitrix\Highloadblock\HighloadBlockTable::getList(['filter' => ['=NAME' => "Mindbox"]]);
        if ($row = $result->fetch()) {
            $hlbl = $row["ID"];
        }
        $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getById($hlbl)->fetch();
        $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
        $entityDataClass = $entity->getDataClass();

        if ($mindboxPrice) {
            $data = [
                "UF_DISCOUNTED_PRICE" => $mindboxPrice
            ];

            $arFilter = [
                "select" => ["*"],
                "order"  => ["ID" => "ASC"],
                "filter" => [
                    "UF_BASKET_ID" => $lineId
                ]
            ];
            $rsData = $entityDataClass::getList($arFilter);

            if ($arData = $rsData->Fetch()) {
                $result = $entityDataClass::update($arData['ID'], $data);
            } else {
                $data = [
                    'UF_BASKET_ID'        => $lineId,
                    "UF_DISCOUNTED_PRICE" => $mindboxPrice
                ];
                $result = $entityDataClass::add($data);
            }
        } else {
            $arFilter = [
                "select" => ["*"],
                "order"  => ["ID" => "ASC"],
                "filter" => [
                    "UF_BASKET_ID" => $lineId
                ]
            ];
            $rsData = $entityDataClass::getList($arFilter);
            if ($arData = $rsData->Fetch()) {
                $result = $entityDataClass::delete($arData['ID']);
            }
        }
    }


    public static function getRequestedPromotions($basketItem, $object)
    {

        $requestedPromotions = [];
        $arDiscountList = [];
        $arActualAction = [];

        if ($object instanceof \Bitrix\Sale\Basket) {
            $discounts = \Bitrix\Sale\Discount::buildFromBasket(
                $object,
                new \Bitrix\Sale\Discount\Context\Fuser($object->getFUserId(true))
            );
        } elseif ($object instanceof \Bitrix\Sale\Order) {
            $discounts = \Bitrix\Sale\Discount::buildFromOrder($object);
        }
        $discounts->calculate();
        $result = $discounts->getApplyResult(true);

        $arDiscountList = $result['DISCOUNT_LIST'];

        $arPriceTypeDiscount = self::getDiscountByPriceType($basketItem);

        if (!empty($arPriceTypeDiscount['BASKET'])) {
            $result['RESULT']['BASKET'][$basketItem->getId()][] = $arPriceTypeDiscount['BASKET'];
        }

        if (!empty($arPriceTypeDiscount['DISCOUNT'])) {
            $arDiscountList[$arPriceTypeDiscount['DISCOUNT']['REAL_DISCOUNT_ID']] = $arPriceTypeDiscount['DISCOUNT'];
        }

        foreach ($result['RESULT']['BASKET'] as $basketId => $arAction) {
            foreach ($arAction as $arActionItem) {
                if ($arActionItem['APPLY'] === 'Y') {
                    $arActualAction[$basketId][] = $arActionItem['DISCOUNT_ID'];
                }
            }
        }

        if (array_key_exists($basketItem->getId(), $arActualAction)) {
            foreach ($arActualAction[$basketItem->getId()] as $discountId) {
                $discountPrice = 0;
                $discountPercentValue = 0;
                $externalId = '';
                if (array_key_exists($discountId, $arDiscountList)) {
                    $arDiscount = $arDiscountList[$discountId];
                    $arActionDescrData = $arDiscount['ACTIONS_DESCR_DATA']['BASKET'][0];
                    if (!isset($arActionDescrData['VALUE'])) {
                        continue;
                    }
                    if ($arDiscount['MODULE_ID'] === 'sale') {
                        if ($arActionDescrData['VALUE_ACTION'] === 'D' &&
                            $arActionDescrData['VALUE_TYPE'] === 'P'
                        ) {
                            $discountPercentValue = $arActionDescrData['VALUE'];
                            $externalId = "SCR-" . $arDiscount['REAL_DISCOUNT_ID'];
                            $discountPrice = $basketItem->getBasePrice() * ($discountPercentValue / 100);
                        }
                    } elseif ($arDiscount['MODULE_ID'] === 'catalog') {
                        if (array_key_exists('VALUE_EXACT', $arActionDescrData)) {
                            $discountPrice = $arActionDescrData['VALUE_EXACT'];
                        } else {
                            $discountPercentValue = $arActionDescrData['VALUE'];
                            $discountPrice = $basketItem->getBasePrice() * ($discountPercentValue / 100);
                        }
                        $externalId = "PD-" . $arDiscount['REAL_DISCOUNT_ID'];
                    }

                    if ($discountPrice > 0 && !empty($externalId)) {
                        $requestedPromotions[] = [
                            'type'      => 'discount',
                            'promotion' => [
                                'ids' => [
                                    'externalId' => $externalId
                                ],
                            ],
                            'amount'    => roundEx($discountPrice * $basketItem->getQuantity(), 2)
                        ];
                    }
                }
            }
        }

        return $requestedPromotions;
    }

    private static function getDiscountByPriceType($basketItem)
    {
        \Bitrix\Main\Loader::includeModule("catalog");

        $arDiscount = [];
        $arProductPrices = self::getProductPrices($basketItem->getProductId());
        $allProductPrices = $arProductPrices['PRICES'];
        $basePriceGroupId = $arProductPrices['BASE_PRICE_CATALOG_GROUP_ID'];

        if (!empty($allProductPrices)) {
            $catalogGroupId = 0;
            foreach ($allProductPrices as $allProductPricesItem) {
                if (roundEx($allProductPricesItem['PRICE'], 2) === roundEx($basketItem->getBasePrice(), 2)) {
                    $catalogGroupId = $allProductPricesItem['CATALOG_GROUP_ID'];
                }
            }

            foreach ($allProductPrices as $allProductPricesItem) {
                if ($allProductPricesItem['CATALOG_GROUP_ID'] === $basePriceGroupId &&
                    $allProductPricesItem['PRICE'] > $basketItem->getBasePrice() &&
                    $catalogGroupId > 0
                ) {
                    $realDiscountId = 'CATALOG-GROUP-' . $catalogGroupId;
                    $arDiscount['BASKET'] = [
                        'DISCOUNT_ID' => $realDiscountId,
                        'APPLY'       => 'Y',
                        'DESCR'       => 'Discount by price type'
                    ];
                    $arDiscount['DISCOUNT'] = [
                        'MODULE_ID'        => 'catalog',
                        'REAL_DISCOUNT_ID' => $realDiscountId,
                    ];
                    $arDiscount['DISCOUNT']['ACTIONS_DESCR_DATA']['BASKET'][] = [
                        'VALUE'        => 100 - (($basketItem->getBasePrice() / $allProductPricesItem['PRICE']) * 100),
                        'VALUE_EXACT'  => $allProductPricesItem['PRICE'] - $basketItem->getBasePrice(),
                        'VALUE_TYPE'   => 'P',
                        'VALUE_ACTION' => 'D'
                    ];
                }
            }
        }

        return $arDiscount;
    }

    public static function getBasePrice($basketItem)
    {
        $arProductPrices = self::getProductPrices($basketItem->getProductId());
        if (!empty($arProductPrices['PRICES'])) {
            foreach ($arProductPrices['PRICES'] as $arProductPrice) {
                if ($arProductPrice['CATALOG_GROUP_ID'] === $arProductPrices['BASE_PRICE_CATALOG_GROUP_ID']) {
                    return $arProductPrice['PRICE'];
                }
            }
        }

        return $basketItem->getBasePrice();
    }

    private static function getProductPrices($productId)
    {
        $basePriceGroupId = 1;
        $rsGroup = \Bitrix\Catalog\GroupTable::getList();
        while ($arGroup = $rsGroup->fetch()) {
            if ($arGroup['BASE'] === 'Y') {
                $basePriceGroupId = $arGroup['ID'];
                break;
            }
        }

        $allProductPrices = \Bitrix\Catalog\PriceTable::getList([
            "select" => ["*"],
            "filter" => [
                "=PRODUCT_ID" => $productId,
            ],
            "order"  => ["CATALOG_GROUP_ID" => "ASC"]
        ])->fetchAll();

        return [
            'PRICES'                      => $allProductPrices,
            'BASE_PRICE_CATALOG_GROUP_ID' => $basePriceGroupId
        ];
    }

    public static function getPriceByType($element)
    {
        $productId = $element['ID'];
        $arResultPrices = $element['prices']['RESULT_PRICE'];
        $arProductPrices = self::getProductPrices($productId);
        foreach ($arProductPrices['PRICES'] as $arProductPrice) {
            if ($arProductPrice['CATALOG_GROUP_ID'] === $arProductPrices['BASE_PRICE_CATALOG_GROUP_ID']) {
                $arResultPrices['BASE_PRICE'] = roundEx($arProductPrice['PRICE'], 2);
                $arResultPrices['UNROUND_BASE_PRICE'] = $arProductPrice['PRICE'];
            }
        }

        return $arResultPrices;
    }

    /**
     * @param $id
     * @return bool
     */
    private static function isAnonym($id)
    {
        $mindboxId = Helper::getMindboxId($id);

        if (!$mindboxId) {
            return true;
        }

        return false;
    }


    /**
     * @param $basketItems
     */
    public static function setCartMindbox($basketItems)
    {
        global $USER;

        $mindbox = static::mindbox();
        if (!$mindbox) {
            return;
        }

        $arLines = [];
        $arAllLines = [];
        foreach ($basketItems as $basketItem) {
            $arAllLines[$basketItem->getProductId()] = $basketItem->getProductId();
            if ($basketItem->getField('DELAY') === 'Y') {
                continue;
            }

            $productId = $basketItem->getProductId();
            $arLines[$productId]['basketItem'] = $basketItem;
            $arLines[$productId]['quantity'] += $basketItem->getQuantity();
            $arLines[$productId]['priceOfLine'] += $basketItem->getPrice() * $basketItem->getQuantity();
        }

        $lines = [];
        foreach ($arLines as $arLine) {
            $product = new ProductRequestDTO();
            $product->setId(
                Options::getModuleOption('EXTERNAL_SYSTEM'),
                Helper::getElementCode($arLine['basketItem']->getProductId())
            );

            $line = new ProductListItemRequestDTO();
            $line->setProduct($product);
            $line->setCount($arLine['quantity']);
            $line->setPriceOfLine($arLine['priceOfLine']);
            $lines[] = $line;
        }

        if (is_object($USER) && $USER->IsAuthorized() && !empty($USER->GetEmail())) {
            $fields = [
                'email' => $USER->GetEmail()
            ];
            $customer = Helper::iconvDTO(new CustomerIdentityRequestDTO($fields));
        }

        if (empty($arAllLines) && count($_SESSION['MB_WISHLIST_COUNT'])) {
            self::clearWishList();
        }

        if (empty($arLines)) {
            if (!isset($_SESSION['MB_CLEAR_CART'])) {
                self::clearCart();
            }

            return;
        }

        try {
            $mindbox->productList()->setProductList(
                new ProductListItemRequestCollection($lines),
                Options::getOperationName('setProductList'),
                $customer
            )->sendRequest();
        } catch (Exceptions\MindboxClientErrorException $e) {
        } catch (Exceptions\MindboxClientException $e) {
            $lastResponse = $mindbox->productList()->getLastResponse();
            if ($lastResponse) {
                $request = $lastResponse->getRequest();
                QueueTable::push($request);
            }
        }
    }

    public static function setWishList()
    {
        $mindbox = static::mindbox();
        if (!$mindbox) {
            return false;
        }

        $basket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), Main\Context::getCurrent()->getSite());
        $basketItems = $basket->getBasketItems();
        $arLines = [];
        foreach ($basketItems as $basketItem) {
            if ($basketItem->getField('DELAY') === 'N') {
                continue;
            }
            $productId = $basketItem->getProductId();
            $arLines[ $productId ]['basketItem'] = $basketItem;
            $arLines[ $productId ]['quantity'] += $basketItem->getQuantity();
            $arLines[ $productId ]['priceOfLine'] += $basketItem->getPrice();
        }

        $lines = [];
        foreach ($arLines as $arLine) {
            $product = new ProductRequestDTO();
            $product->setId(Options::getModuleOption('EXTERNAL_SYSTEM'), Helper::getElementCode($arLine['basketItem']->getProductId()));
            $line = new ProductListItemRequestDTO();
            $line->setProduct($product);
            $line->setCount($arLine['quantity']);
            $line->setPriceOfLine($arLine['priceOfLine']);
            $lines[] = $line;
        }

        if (empty($lines)) {
            return false;
        }

        try {
            $mindbox->productList()->setWishList(
                new ProductListItemRequestCollection($lines),
                Options::getOperationName('setWishList')
            )->sendRequest();
            $_SESSION['MB_WISHLIST_COUNT'] = count($_SESSION['MB_WISHLIST']);
            self::setCartMindbox($basketItems);
        } catch (Exceptions\MindboxClientErrorException $e) {
            $lastResponse = $mindbox->productList()->getLastResponse();
            if ($lastResponse) {
                $request = $lastResponse->getRequest();
                QueueTable::push($request);
            }
        } catch (Exceptions\MindboxClientException $e) {
            $lastResponse = $mindbox->productList()->getLastResponse();
            if ($lastResponse) {
                $request = $lastResponse->getRequest();
                QueueTable::push($request);
            }
        }
    }

    public static function clearWishList()
    {
        $mindbox = static::mindbox();
        if (!$mindbox) {
            return false;
        }

        $basket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), Main\Context::getCurrent()->getSite());
        $basketItems = $basket->getBasketItems();

        try {
            $mindbox->productList()->clearWishList(Options::getOperationName('clearWishList'))->sendRequest();
            unset($_SESSION['MB_WISHLIST_COUNT']);
            self::setCartMindbox($basketItems);
        } catch (Exceptions\MindboxClientErrorException $e) {
            $lastResponse = $mindbox->productList()->getLastResponse();
            if ($lastResponse) {
                $request = $lastResponse->getRequest();
                QueueTable::push($request);
            }
        } catch (Exceptions\MindboxClientException $e) {
            $lastResponse = $mindbox->productList()->getLastResponse();
            if ($lastResponse) {
                $request = $lastResponse->getRequest();
                QueueTable::push($request);
            }
        }
    }

    private static function clearCart()
    {

        $mindbox = static::mindbox();
        if (!$mindbox) {
            return false;
        }

        $_SESSION['MB_CLEAR_CART'] = 'Y';

        try {
            $mindbox->productList()->clearCart(Options::getOperationName('clearCart'))->sendRequest();
        } catch (Exceptions\MindboxClientErrorException $e) {
            $lastResponse = $mindbox->productList()->getLastResponse();
            if ($lastResponse) {
                $request = $lastResponse->getRequest();
                QueueTable::push($request);
            }
        } catch (Exceptions\MindboxClientException $e) {
            $lastResponse = $mindbox->productList()->getLastResponse();
            if ($lastResponse) {
                $request = $lastResponse->getRequest();
                QueueTable::push($request);
            }
        }
    }

    /**
     * @param $errors
     * @return string
     */
    public static function formatValidationMessages($errors)
    {
        Loc::loadMessages(__FILE__);

        $strError = '';
        foreach ($errors as $error) {
            $strError .= Loc::getMessage($error->getLocation()) . ': ' . $error->getMessage() . PHP_EOL;
        }

        $strError = rtrim($strError, PHP_EOL);

        return $strError;
    }

    /**
     * @return Mindbox
     */
    private static function mindbox()
    {
        $mindbox = Options::getConfig();

        return $mindbox;
    }

    /**
     * Check if order is new
     *
     * @return boolean
     */
    public static function isNewOrder($values)
    {
        $isNewOrder = false;
        if (array_key_exists('LID', $values) && empty($values['LID'])                       &&
            array_key_exists('USER_ID', $values) && empty($values['USER_ID'])               &&
            array_key_exists('PRICE', $values) && empty($values['PRICE'])                   &&
            array_key_exists('DELIVERY_ID', $values) && empty($values['DELIVERY_ID'])       &&
            array_key_exists('PAY_SYSTEM_ID', $values) && empty($values['PAY_SYSTEM_ID'])
        ) {
            $isNewOrder = true;
        }

        return $isNewOrder;
    }
}
