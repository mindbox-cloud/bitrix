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
use Mindbox\Options;
use Psr\Log\LoggerInterface;
use Mindbox\DTO\V3\Requests\CustomerRequestDTO;

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

        if ($rsUser && isset($rsUser[ 'UF_MINDBOX_ID' ]) && $rsUser['UF_MINDBOX_ID'] > 0) {
            $mindboxId = $rsUser[ 'UF_MINDBOX_ID' ];
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
                $logger->error($message, ['getCustomerInfo', [Options::getModuleOption('WEBSITE_ID') => $id], $e->getMessage()]);
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
                unset($_SESSION[ 'NEW_USER_MB_ID' ]);
            } else if ($response && $response->getResult()->getCustomer()->getProcessingStatus() === 'NotFound') {
                $message = date('d.m.Y H:i:s');
                $logger->error($message, ['getCustomerInfo', [Options::getModuleOption('WEBSITE_ID') => $id], $response->getResult()->getCustomer()->getProcessingStatus()]);
                $mindboxId = self::registerCustomer($id);
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

    private function registerCustomer($websiteUserId)
    {
        global $APPLICATION, $USER;

        $rsUser = \CUser::GetByID($websiteUserId);
        $arFields = $rsUser->Fetch();

        $logger = new \Mindbox\Loggers\MindboxFileLogger(Options::getModuleOption('LOG_PATH'));

        $message = date('d.m.Y H:i:s');
        $logger->debug($message, ['$websiteUserId' => $websiteUserId, '$arFields' => $arFields]);

        $mindbox = Options::getConfig();

        if (!isset($arFields[ 'PERSONAL_PHONE' ])) {
            $arFields[ 'PERSONAL_PHONE' ] = $arFields[ 'PERSONAL_MOBILE' ];
        }

        if (isset($arFields[ 'PERSONAL_PHONE' ])) {
            $arFields[ 'PERSONAL_PHONE' ] = Helper::formatPhone($arFields[ 'PERSONAL_PHONE' ]);
        }

        $sex = substr(ucfirst($arFields[ 'PERSONAL_GENDER' ]), 0, 1) ?: null;
        $fields = [
            'email'       => $arFields[ 'EMAIL' ],
            'lastName'    => $arFields[ 'LAST_NAME' ],
            'middleName'  => $arFields[ 'SECOND_NAME' ],
            'firstName'   => $arFields[ 'NAME' ],
            'mobilePhone' => self::normalizePhoneNumber($arFields[ 'PERSONAL_PHONE' ]),
            'birthDate'   => Helper::formatDate($arFields[ 'PERSONAL_BIRTHDAY' ]),
            'sex'         => $sex,
        ];

        $fields = array_filter($fields, function ($item) {
            return isset($item);
        });

        $fields[ 'subscriptions' ] = [
            [
                'pointOfContact' => 'Email',
                'isSubscribed'   => true,
            ],
            [
                'pointOfContact' => 'Sms',
                'isSubscribed'   => true,
            ],
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
                'UF_MINDBOX_ID'      => $mindboxId
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
     * Get product id by basket item
     * @param \Bitrix\Sale\Basket $basketItem
     *
     * @return $result
     */

    public static function getProductId($basketItem)
    {
        $result = '';
        $id = $basketItem->getField('PRODUCT_XML_ID');

        if (!$id) {
            $productId = $basketItem->getField('PRODUCT_ID');
            $arProduct = \CIBlockElement::GetByID($productId)->GetNext();
            $id = $arProduct['XML_ID'];
        }

        if (!$id) {
            $id = $basketItem->getField('PRODUCT_ID');
        }

        $result = $id;

        return $result;
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
            $arIblock[ $ar_res[ 'ID' ] ] = $ar_res[ 'NAME' ] . " (" . $ar_res[ 'ID' ] . ")";
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
            while ($iblockProperty = $iblockProperties-> Fetch()) {
                $props['PROPERTY_'.$iblockProperty['CODE']] = $iblockProperty['NAME'];
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
            while ($iblockProperty = $iblockProperties-> Fetch()) {
                $offerProps['PROPERTY_'.$iblockProperty['CODE']] = $iblockProperty['NAME'];
            }
        }

        return $offerProps;
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
        if (!$_SESSION[ 'MINDBOX_TRANSACTION_ID' ]) {
            $_SESSION[ 'MINDBOX_TRANSACTION_ID' ] = $transactionId;

            return $transactionId;
        } else {
            return $_SESSION[ 'MINDBOX_TRANSACTION_ID' ];
        }
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

    public static function processHlbBasketRule($lineId, $mindboxPrice)
    {
        if (\Bitrix\Main\Loader::includeModule('intensa.logger')) {
            $logger = new \Intensa\Logger\ILog('processHlbBasketRule');
        }

        $hlbl = 4;
        $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getById($hlbl)->fetch();
        $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();

        if ($mindboxPrice) {
            $data = [
                "UF_DISCOUNTED_PRICE"       =>  $mindboxPrice
            ];

            $arFilter = [
                "select" => ["*"],
                "order" => ["ID" => "ASC"],
                "filter" => [
                    "UF_BASKET_ID"   =>  $lineId
                ]
            ];
            $logger->log('$arFilter', $arFilter);
            $rsData = $entity_data_class::getList($arFilter);

            if ($arData = $rsData->Fetch()) {
                $result = $entity_data_class::update($arData['ID'], $data);
                $logger->log('$entity_data_class::update', [
                    '$result' => $result,
                    '$data' => $data
                ]);
            } else {
                $data = [
                    'UF_BASKET_ID'  =>  $lineId,
                    "UF_DISCOUNTED_PRICE"       =>  $mindboxPrice
                ];
                $result = $entity_data_class::add($data);
                $logger->log('$entity_data_class::add', [
                    '$result' => $result,
                    '$data' => $data
                ]);
            }
        } else {
            $arFilter = [
                "select" => ["*"],
                "order" => ["ID" => "ASC"],
                "filter" => [
                    "UF_BASKET_ID"   =>  $lineId
                ]
            ];
            $rsData = $entity_data_class::getList($arFilter);
            if ($arData = $rsData->Fetch()) {
                $result = $entity_data_class::delete($arData['ID']);
                $logger->log('$entity_data_class::delete', [
                    '$result' => $result,
                    '$arData' => $arData
                ]);
            }
        }
    }


    public static function getRequestedPromotions($basketItem, $object)
    {
        if (\Bitrix\Main\Loader::includeModule('intensa.logger')) {
            $logger = new \Intensa\Logger\ILog('getRequestedPromotions');
            $logger->log('get_class', get_class($object));
        }

        $requestedPromotions = [];
        $arDiscountList = [];
        $arActualAction = [];

        $objectClass = get_class($object);
        if ($objectClass == 'Bitrix\Sale\Basket') {
            $discounts = \Bitrix\Sale\Discount::buildFromBasket($object, new \Bitrix\Sale\Discount\Context\Fuser($object->getFUserId(true)));
        } else {
            $discounts = \Bitrix\Sale\Discount::buildFromOrder($object);
        }
        $discounts->calculate();
        $result = $discounts->getApplyResult(true);

        $logger->log('$result', $result);


        $discountList = $result['DISCOUNT_LIST'];
        foreach ($discountList as $discountId => $discountListItem) {
            $actionsDescrData = reset($discountListItem['ACTIONS_DESCR_DATA']['BASKET']);
            $arDiscountList[$discountId] = array_merge(['REAL_DISCOUNT_ID' => $discountListItem['REAL_DISCOUNT_ID']], $actionsDescrData);
        }


        foreach ($result['RESULT']['BASKET'] as $basketId => $arAction) {
            foreach ($arAction as $arActionItem) {
                if ($arActionItem['APPLY'] === 'Y') {
                    $arActualAction[$basketId][] = $arActionItem['DISCOUNT_ID'];
                }
            }
        }

        foreach ($result['FULL_DISCOUNT_LIST'] as $discountId => $arFullDiscount) {
            if (strpos($arFullDiscount['APPLICATION'], "SaleActionDiscountFromDirectory::applyProductDiscount") !== false) {
                unset($result['FULL_DISCOUNT_LIST'][$discountId]);
            }
        }

        $logger->log('FULL_DISCOUNT_LIST', $result['FULL_DISCOUNT_LIST']);

        foreach ($arDiscountList as $discountId => $arDiscount) {
            if (array_key_exists($arDiscount['REAL_DISCOUNT_ID'], $result['FULL_DISCOUNT_LIST'])) {
                $arDiscountList[$discountId]['BASKET_RULE'] = $result['FULL_DISCOUNT_LIST'][$arDiscount['REAL_DISCOUNT_ID']];
            }
        }

        $logger->log('$arDiscountList', $arDiscountList);
        $logger->log('$arActualAction', $arActualAction);

        if (array_key_exists($basketItem->getId(), $arActualAction)) {
            foreach ($arActualAction[$basketItem->getId()] as $discountId) {
                $discountPrice = 0;
                $discountPercentValue = 0;
                if (array_key_exists($discountId, $arDiscountList)) {
                    $arDiscount = $arDiscountList[$discountId];
                    if (array_key_exists('BASKET_RULE', $arDiscount)) {
                        if ($arDiscount['BASKET_RULE']['SHORT_DESCRIPTION_STRUCTURE']['TYPE'] === 'Discount' &&
                            $arDiscount['BASKET_RULE']['SHORT_DESCRIPTION_STRUCTURE']['VALUE_TYPE'] === 'P'
                        ) {
                            $discountPercentValue = $arDiscount['BASKET_RULE']['SHORT_DESCRIPTION_STRUCTURE']['VALUE'];
                        }
                    } else {
                        $discountPercentValue = $arDiscount['VALUE'];
                    }
                    if ($discountPercentValue) {
                        $discountPrice = roundEx($basketItem->getBasePrice()*($discountPercentValue/100), 2);
                    }

                    if ($discountPrice > 0) {
                        $requestedPromotions[] = [
                            'type'      => 'discount',
                            'promotion' => [
                                'ids'  => [
                                    'externalId' => $arDiscount['REAL_DISCOUNT_ID']
                                ],
                            ],
                            'amount'    => roundEx($discountPrice*$basketItem->getQuantity(), 2)
                        ];
                    }
                }
            }
        }
        return $requestedPromotions;
    }
}
