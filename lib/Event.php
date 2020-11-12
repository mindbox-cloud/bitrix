<?php
/**
 * Содержит события
 */

namespace Mindbox;

use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use CUser;
use DateTime;
use DateTimeZone;
use Mindbox\DTO\DTO;
use Mindbox\DTO\V2\Requests\DiscountRequestDTO;
use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\DTO\V2\Requests\CustomerRequestDTO as CustomerRequestV2DTO;
use Mindbox\DTO\V2\Requests\LineRequestDTO;
use Mindbox\DTO\V2\Requests\OrderCreateRequestDTO;
use Mindbox\DTO\V2\Requests\OrderUpdateRequestDTO;
use Mindbox\DTO\V2\Requests\PreorderRequestDTO;
use Mindbox\DTO\V3\Requests\ProductListItemRequestCollection;
use Mindbox\DTO\V3\Requests\ProductListItemRequestDTO;
use Mindbox\DTO\V3\Requests\ProductRequestDTO;
use Mindbox\DTO\V3\Requests\SubscriptionRequestCollection;


Loader::includeModule('catalog');
Loader::includeModule('sale');
Loader::includeModule('main');

/**
 * Class Event
 * @package Mindbox
 */
class Event
{
    protected $mindbox;

    /**
     * @param $arUser
     * @return bool
     */
    public function OnAfterUserAuthorizeHandler($arUser)
    {

        if(empty($arUser['user_fields']['LAST_LOGIN'])) {
            return true;
        }

        $mindbox = static::mindbox();
        if (!$mindbox) {
            return true;
        }

        if (isset($_SESSION[ 'NEW_USER_MINDBOX' ]) && $_SESSION[ 'NEW_USER_MINDBOX' ] === true) {
            unset($_SESSION[ 'NEW_USER_MINDBOX' ]);

            return true;
        }
        if ($_SESSION[ 'AUTH_BY_SMS' ] === true) {
            $_SESSION[ 'AUTH_BY_SMS' ] = false;

            return true;
        }

        //$mindboxId = Helper::getMindboxId($arUser['user_fields']['ID']);

        $mindboxId = $arUser[ 'user_fields' ][ 'ID' ];

        if (empty($mindboxId)) {
            $request = $mindbox->getClientV3()->prepareRequest('POST',
                Options::getOperationName('getCustomerInfo'),
                new DTO([
                    'customer' => [
                        'ids' => [
                            Options::getModuleOption('WEBSITE_ID') => $arUser[ 'user_fields' ][ 'ID' ]
                        ]
                    ]
                ]));

            try {
                $response = $request->sendRequest();
            } catch (Exceptions\MindboxClientException $e) {
                return false;
            }

            if ($response->getResult()->getCustomer()->getProcessingStatus() === 'Found') {
                $fields = [
                    'UF_EMAIL_CONFIRMED' => $response->getResult()->getCustomer()->getIsEmailConfirmed(),
                    'UF_MINDBOX_ID'      => $response->getResult()->getCustomer()->getId('mindboxId')
                ];

                $user = new CUser;
                $user->Update(
                    $arUser[ 'user_fields' ][ 'ID' ],
                    $fields
                );
                $dbUser[ 'UF_MINDBOX_ID' ] = $fields[ 'UF_MINDBOX_ID' ];
            } else {
                return true;
            }
        }

        $customer = new CustomerRequestDTO([
            'ids' => [
                Options::getModuleOption('WEBSITE_ID') => $mindboxId
            ]
        ]);

        try {
            $mindbox->customer()->authorize($customer,
                Options::getOperationName('authorize'))->sendRequest();
        } catch (Exceptions\MindboxUnavailableException $e) {
            $lastResponse = $mindbox->customer()->getLastResponse();

            if ($lastResponse) {
                $request = $lastResponse->getRequest();
                QueueTable::push($request);
            }
        } catch (Exceptions\MindboxClientException $e) {
            return false;
        }

        return true;
    }

    public function OnBeforeUserRegisterHandler(&$arFields)
    {

        if (\COption::GetOptionString('mindbox.marketing', 'MODE') == 'standard') {
            return $arFields;
        }

        return $arFields;

        global $APPLICATION, $USER;

        $mindbox = static::mindbox();
        if (!$mindbox) {
            return $arFields;
        }

        if (!isset($arFields[ 'PERSONAL_PHONE' ])) {
            $arFields[ 'PERSONAL_PHONE' ] = $arFields[ 'PERSONAL_MOBILE' ];
        }

        if (isset($arFields[ 'PERSONAL_PHONE' ])) {
            $arFields[ 'PERSONAL_PHONE' ] = Helper::formatPhone($arFields[ 'PERSONAL_PHONE' ]);
        }

        if (isset($_SESSION[ 'OFFLINE_REGISTER' ]) && $_SESSION[ 'OFFLINE_REGISTER' ]) {
            return $arFields;
        }

        if (!$USER->CheckFields($arFields)) {
            $APPLICATION->ThrowException($USER->LAST_ERROR);

            return false;
        }

        $sex = substr(ucfirst($arFields[ 'PERSONAL_GENDER' ]), 0, 1) ?: null;
        $fields = [
            'email'       => $arFields[ 'EMAIL' ],
            'lastName'    => $arFields[ 'LAST_NAME' ],
            'middleName'  => $arFields[ 'SECOND_NAME' ],
            'firstName'   => $arFields[ 'NAME' ],
            'mobilePhone' => $arFields[ 'PERSONAL_PHONE' ],
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
                'valueByDefault' => true
            ],
            [
                'pointOfContact' => 'Sms',
                'isSubscribed'   => true,
                'valueByDefault' => true
            ],
        ];

        $customer = Helper::iconvDTO(new CustomerRequestDTO($fields));

        unset($fields);

        try {
            $registerResponse = $mindbox->customer()->register($customer,
                Options::getOperationName('register'), true, Helper::isSync())->sendRequest()->getResult();
        } catch (Exceptions\MindboxClientException $e) {
            $APPLICATION->ThrowException(Loc::getMessage('MB_USER_REGISTER_ERROR'));
            return false;
        }

        $registerResponse = Helper::iconvDTO($registerResponse, false);
        $status = $registerResponse->getStatus();


        if ($status === 'ValidationError') {
            $errors = $registerResponse->getValidationMessages();

            $APPLICATION->ThrowException(self::formatValidationMessages($errors));

            return false;
        }

        $customer = $registerResponse->getCustomer();


        if (!$customer) {
            return false;
        }

        $mindBoxId = $customer->getId('mindboxId');
        $_SESSION[ 'NEW_USER_MB_ID' ] = $mindBoxId;
        $_SESSION[ 'NEW_USER_MINDBOX' ] = true;

    }

    public function OnAfterUserRegisterHandler(&$arFields)
    {
        // all for standard mode

        global $APPLICATION;
        $mindbox = static::mindbox();
        if (!$mindbox) {
            return $arFields;
        }

        $mindBoxId = $_SESSION[ 'NEW_USER_MB_ID' ];
        unset($_SESSION[ 'NEW_USER_MB_ID' ]);

        if (!$mindBoxId) {
            //return false;
        }

        $fields = [
            'UF_EMAIL_CONFIRMED' => '0',
            'UF_MINDBOX_ID'      => $mindBoxId
        ];

        $user = new CUser;
        $user->Update(
            $arFields[ 'USER_ID' ],
            $fields
        );


        if ($arFields[ 'USER_ID' ]) {
            $sex = substr(ucfirst($arFields[ 'PERSONAL_GENDER' ]), 0, 1) ?: null;
            $fields = [
                'email'       => $arFields[ 'EMAIL' ],
                'lastName'    => $arFields[ 'LAST_NAME' ],
                'middleName'  => $arFields[ 'SECOND_NAME' ],
                'firstName'   => $arFields[ 'NAME' ],
                'mobilePhone' => $arFields[ 'PERSONAL_PHONE' ],
                'birthDate'   => Helper::formatDate($arFields[ 'PERSONAL_BIRTHDAY' ]),
                'sex'         => $sex,
                'ids'         => [Options::getModuleOption('WEBSITE_ID') => $arFields[ 'USER_ID' ]]
            ];

            $fields = array_filter($fields, function ($item) {
                return isset($item);
            });

            if (!isset($fields)) {
                return true;
            }

            $customer = new CustomerRequestDTO($fields);

            unset($fields);

            $subscriptions = [
                'subscription' => [
                    'brand' =>  Options::getModuleOption('BRAND'),
                    'pointOfContact' => 'Email',
                    'isSubscribed'   => true,
                    'valueByDefault' => true
                ]
            ];
            $customer->setSubscriptions($subscriptions);


            try {
                $mindbox->customer()->register($customer, Options::getOperationName('register'), true,
                    Helper::isSync())->sendRequest();
            } catch (Exceptions\MindboxClientException $e) {
                $APPLICATION->ThrowException(Loc::getMessage('MB_USER_EDIT_ERROR'));
                return false;
            }
        }

        //  authorize
        sleep(1);

        if (isset($_SESSION[ 'NEW_USER_MINDBOX' ]) && $_SESSION[ 'NEW_USER_MINDBOX' ] === true) {
            unset($_SESSION[ 'NEW_USER_MINDBOX' ]);

            return true;
        }
        if ($_SESSION[ 'AUTH_BY_SMS' ] === true) {
            $_SESSION[ 'AUTH_BY_SMS' ] = false;

            return true;
        }

        $mindboxId = $arFields[ 'USER_ID' ];


        if (!empty($mindboxId)) {

            $request = $mindbox->getClientV3()->prepareRequest('POST',
                Options::getOperationName('getCustomerInfo'),
                new DTO([
                    'customer' => [
                        'ids' => [
                            Options::getModuleOption('WEBSITE_ID') => $arFields[ 'USER_ID' ]
                        ]
                    ]
                ]));

            try {
                $response = $request->sendRequest();
            } catch (Exceptions\MindboxClientException $e) {
                $APPLICATION->ThrowException($e->getMessage());
                return false;
            }

            if ($response->getResult()->getCustomer()->getProcessingStatus() === 'Found') {
                $fields = [
                    'UF_EMAIL_CONFIRMED' => $response->getResult()->getCustomer()->getIsEmailConfirmed(),
                    'UF_MINDBOX_ID'      => $response->getResult()->getCustomer()->getId('mindboxId')
                ];

                $user = new CUser;
                $user->Update(
                    $arFields[ 'USER_ID' ],
                    $fields
                );
            } else {
                return true;
            }

            $customer = new CustomerRequestDTO([
                'ids' => [
                    Options::getModuleOption('WEBSITE_ID') => $mindboxId
                ]
            ]);

            try {
                $mindbox->customer()->authorize($customer,
                    Options::getOperationName('authorize'))->sendRequest();
            } catch (Exceptions\MindboxUnavailableException $e) {
                $lastResponse = $mindbox->customer()->getLastResponse();

                if ($lastResponse) {
                    $request = $lastResponse->getRequest();
                    QueueTable::push($request);
                }
            } catch (Exceptions\MindboxClientException $e) {
                return false;
            }
        }



        return $arFields;
    }

    public function OnBeforeUserUpdateHandler(&$arFields)
    {
        global $APPLICATION;



        $mindbox = static::mindbox();

        if (!$mindbox) {
            return $arFields;
        }

        $dbUser = UserTable::getList(
            [
                'select' => ['EMAIL', 'PERSONAL_PHONE'],
                'filter' => ['ID' => $arFields[ 'ID' ]]
            ]
        )->fetch();

        if (!$dbUser) {
            return false;
        }

        if (!isset($arFields[ 'PERSONAL_PHONE' ])) {
            $arFields[ 'PERSONAL_PHONE' ] = $arFields[ 'PERSONAL_MOBILE' ];
        }

        if (isset($arFields[ 'EMAIL' ]) && $dbUser[ 'EMAIL' ] != $arFields[ 'EMAIL' ]) {
            $arFields[ 'UF_EMAIL_CONFIRMED' ] = '0';
        }

        if (isset($arFields[ 'PERSONAL_PHONE' ])) {
            $arFields[ 'PERSONAL_PHONE' ] = Helper::formatPhone($arFields[ 'PERSONAL_PHONE' ]);
        }

        if (isset($_SESSION[ 'OFFLINE_REGISTER' ]) && $_SESSION[ 'OFFLINE_REGISTER' ]) {
            unset($_SESSION[ 'OFFLINE_REGISTER' ]);

            return true;
        }

        $mindboxId = $arFields[ 'ID' ];

        if (!empty($mindboxId)) {
            $sex = substr(ucfirst($arFields[ 'PERSONAL_GENDER' ]), 0, 1) ?: null;

            $fields = [
                'birthDate'   => Helper::formatDate($arFields[ "PERSONAL_BIRTHDAY" ]),
                'firstName'   => $arFields[ 'NAME' ],
                'middleName'  => $arFields[ 'SECOND_NAME' ],
                'lastName'    => $arFields[ "LAST_NAME" ],
                'mobilePhone' => $arFields[ 'PERSONAL_PHONE' ],
                'email'       => $arFields[ 'EMAIL' ],
                'sex'         => $sex
            ];

            $fields = array_filter($fields, function ($item) {
                return isset($item);
            });

            if (!isset($fields)) {
                return true;
            }

            $fields[ 'ids' ][ Options::getModuleOption('WEBSITE_ID')] = $mindboxId;
            $customer = new CustomerRequestDTO($fields);
            $customer = Helper::iconvDTO($customer);
            unset($fields);



            try {
                $updateResponse = $mindbox->customer()->edit($customer, Options::getOperationName('edit'), true,
                    Helper::isSync())->sendRequest()->getResult();
            } catch (Exceptions\MindboxClientException $e) {
                $APPLICATION->ThrowException(Loc::getMessage('MB_USER_EDIT_ERROR'));

                return false;
            }

            $updateResponse = Helper::iconvDTO($updateResponse, false);
            $status = $updateResponse->getStatus();

            if ($status === 'ValidationError') {
                $errors = $updateResponse->getValidationMessages();

                $APPLICATION->ThrowException(self::formatValidationMessages($errors));

                return false;
            }
        }

        return true;
    }

    public function OnSaleOrderSavedHandler($order)
    {

        $mindbox = static::mindbox();
        if (!$mindbox) {
            return new Main\EventResult(Main\EventResult::SUCCESS);
        }

        /** @var \Bitrix\Sale\Basket $basket */
        $basket = $order->getBasket();
        global $USER;

        $rsUser = \CUser::GetByID($USER->GetID());
        $arUser = $rsUser->Fetch();


        $orderDTO = new OrderCreateRequestDTO();
        $basketItems = $basket->getBasketItems();
        $lines = [];

        foreach ($basketItems as $basketItem) {
            $line = new LineRequestDTO();
            $catalogPrice = \CPrice::GetBasePrice($basketItem->getProductId());
            $catalogPrice = $catalogPrice[ 'PRICE' ] ?: 0;
            $lines[] = [
                  'basePricePerItem' => $catalogPrice,
                  'quantity'         => $basketItem->getQuantity(),
                  'lineId'           => $basketItem->getId(),
                  'product' =>  [
                      'ids' =>  [
                          Options::getModuleOption('EXTERNAL_SYSTEM') =>  Helper::getProductId($basketItem)
                      ]
                  ]
              ];
        }

        if (empty($lines)) {
            return new Main\EventResult(Main\EventResult::SUCCESS);
        }


        $orderDTO->setField('order', [
                'ids' => [
                    Options::getModuleOption('TRANSACTION_ID') => $order->getId()
                ],
                'lines' =>  $lines
            ]
        );


        $customer = new CustomerRequestV2DTO();

        $mindboxId = Helper::getMindboxId($order->getUserId());

        $propertyCollection = $order->getPropertyCollection();
        $ar = $propertyCollection->getArray();
        foreach ($ar['properties'] as $arProperty) {
            $arOrderProperty[$arProperty['CODE']] = array_pop($arProperty['VALUE']);
        }

        $customer->setEmail($arOrderProperty['EMAIL']);
        $customer->setLastName($arOrderProperty['FIO']);
        $customer->setFirstName($arOrderProperty['NAME']);
        $customer->setMobilePhone($arOrderProperty['PHONE']);
        $customer->setId(Options::getModuleOption('WEBSITE_ID'), $order->getUserId());

        $subscriptions = [
            'subscription' => [
                'brand' =>  Options::getModuleOption('BRAND'),
                'pointOfContact' => 'Email',
                'isSubscribed'   => true,
                'valueByDefault' => true
            ]
        ];
        $customer->setSubscriptions($subscriptions);


        $orderDTO->setCustomer($customer);


        $discounts = [];
        $bonuses = $_SESSION[ 'PAY_BONUSES' ];
        if (!empty($bonuses)) {
            $discounts[] = new DiscountRequestDTO([
                'type'        => 'balance',
                'amount'      => $bonuses,
                'balanceType' => [
                    'ids' => ['systemName' => 'Main']
                ]
            ]);
        }

        $code = $_SESSION[ 'PROMO_CODE' ];
        if ($code) {
            $discounts[] = new DiscountRequestDTO([
                'type'   => 'promoCode',
                'id'     => $code,
                'amount' => $_SESSION[ 'PROMO_CODE_AMOUNT' ] ?: 0
            ]);
        }

        if (!empty($discounts)) {
            $orderDTO->setDiscounts($discounts);
        }

        try {

            if (\COption::GetOptionString('mindbox.marketing', 'MODE') == 'standard') {
                if (\Mindbox\Helper::isUnAuthorizedOrder($arUser) || !$USER->IsAuthorized()) {
                    $createOrderResult = $mindbox->order()->CreateUnauthorizedOrder($orderDTO,
                        Options::getOperationName('createUnauthorizedOrder'))->sendRequest();
                } else {
                    $createOrderResult = $mindbox->order()->CreateAuthorizedOrder($orderDTO,
                        Options::getOperationName('createAuthorizedOrder'))->sendRequest();
                }
            } else {
                $createOrderResult = $mindbox->order()->createOrder($orderDTO,
                    Options::getOperationName('createOrder'))->sendRequest();
            }

            if ($createOrderResult->getValidationErrors()) {
                return new Main\EventResult(Main\EventResult::ERROR);
            }

            $createOrderResult = $createOrderResult->getResult()->getField('order');
            $_SESSION[ 'MINDBOX_ORDER' ] = $createOrderResult ? $createOrderResult->getId('mindbox') : false;
        } catch (Exceptions\MindboxClientErrorException $e) {
            return new Main\EventResult(Main\EventResult::ERROR);
        } catch (Exceptions\MindboxUnavailableException $e) {
            $orderDTO = new OrderUpdateRequestDTO();

            $_SESSION[ 'PAY_BONUSES' ] = 0;
            unset($_SESSION[ 'PROMO_CODE' ]);
            unset($_SESSION[ 'PROMO_CODE_AMOUNT' ]);

            if ($_SESSION[ 'MINDBOX_ORDER' ]) {
                $orderDTO->setId('mindbox', $_SESSION[ 'MINDBOX_ORDER' ]);
            }

            $now = new DateTime();
            $now = $now->setTimezone(new DateTimeZone("UTC"))->format("Y-m-d H:i:s");
            $orderDTO->setUpdatedDateTimeUtc($now);

            $customer = new CustomerRequestV2DTO();
            $mindboxId = Helper::getMindboxId($order->getUserId());

            if ($mindboxId) {
                $customer->setId('mindbox', $mindboxId);
            }

            $customer->setEmail($USER->GetEmail());
            $phone = $_SESSION[ 'ANONYM' ][ 'PHONE' ];
            unset($_SESSION[ 'ANONYM' ][ 'PHONE' ]);

            if ($phone) {
                $customer->setMobilePhone(Helper::formatPhone($phone));
            }
            if ($USER->IsAuthorized()) {
                $customer->setField('isAuthorized', true);
            } else {
                $customer->setField('isAuthorized', false);
            }

            $orderDTO->setCustomer($customer);
            $orderDTO->setPointOfContact(Options::getModuleOption('POINT_OF_CONTACT'));

            $lines = [];
            foreach ($basketItems as $basketItem) {
                $basketItem->setField('CUSTOM_PRICE', 'N');
                $basketItem->save();
                $line = new LineRequestDTO();
                $line->setField('lineId', $basketItem->getId());
                $line->setQuantity($basketItem->getQuantity());
                $catalogPrice = \CPrice::GetBasePrice($basketItem->getProductId())[ 'PRICE' ];
                $line->setProduct([
                    'productId'        => Helper::getProductId($basketItem),
                    'basePricePerItem' => $catalogPrice
                ]);

                $lines[] = $line;
            }

            $orderDTO->setLines($lines);
            $orderDTO->setField('totalPrice', $basket->getPrice());

            $_SESSION[ 'OFFLINE_ORDER' ] = [
                'DTO' => $orderDTO,
            ];

            return new Main\EventResult(Main\EventResult::SUCCESS);
        } catch (Exceptions\MindboxClientException $e) {
            $lastResponse = $mindbox->order()->getLastResponse();

            if ($lastResponse) {
                $request = $lastResponse->getRequest();
                QueueTable::push($request);
            }
        }

        return new Main\EventResult(Main\EventResult::SUCCESS);
    }


    public function OnSaleBasketBeforeSavedHadler($basket)
    {
        global $USER;
        $mindbox = static::mindbox();
        if (!$mindbox) {
            return new Main\EventResult(Main\EventResult::SUCCESS);
        }

        $preorder = new PreorderRequestDTO();

        /** @var Basket $basket */
        $basketItems = $basket->getBasketItems();
        self::setCartMindbox($basketItems);
        $lines = [];
        $bitrixBasket = [];

        $preorder = new \Mindbox\DTO\V3\Requests\PreorderRequestDTO();


        foreach ($basketItems as $basketItem) {
            $bitrixBasket[$basketItem->getId()] = $basketItem;
            $catalogPrice = $basketItem->getBasePrice();
            $lines[] = [
                'basePricePerItem' => $catalogPrice,
                'quantity'         => $basketItem->getQuantity(),
                'lineId'           => $basketItem->getId(),
                'product' =>  [
                    'ids' =>  [
                        Options::getModuleOption('EXTERNAL_SYSTEM') =>  Helper::getProductId($basketItem)
                    ]
                ],
                'status'    =>  [
                    'ids'   =>  [
                        'externalId'    =>  'CheckedOut'
                    ]
                ]
            ];
        }

        if (empty($lines)) {
            return false;
        }

        $preorder->setField('order', [
                'ids' => [
                    Options::getModuleOption('TRANSACTION_ID') => '',
                ],
                'lines' =>  $lines
            ]
        );

        //$preorder->setLines($lines);

        $customer = new CustomerRequestDTO();
        if ($USER->IsAuthorized()) {
            $mindboxId = Helper::getMindboxId($USER->GetID());
            if ($mindboxId) {
                $customer->setId('mindboxId', $mindboxId);
            }
        }

        $preorder->setCustomer($customer);
        //$preorder->setPointOfContact(Options::getModuleOption('POINT_OF_CONTACT'));

        $bonuses = $_SESSION['PAY_BONUSES'] ?: 0;


        $discounts[] = new DiscountRequestDTO([
            'type' => 'balance',
            'amount' => $bonuses,
            'balanceType' => [
                'ids' => ['systemName' => 'Main']
            ]
        ]);


        if ($code = $_SESSION['PROMO_CODE']) {
            $discounts[] = new DiscountRequestDTO([
                'type' => 'promoCode',
                'id' => $code
            ]);
        }

        if ($discounts) {
            //$preorder->setDiscounts($discounts);
        }

        if (\COption::GetOptionString('mindbox.marketing', 'MODE') != 'standard') {
            try {

                if($USER->IsAuthorized()) {
                    $preorderInfo = $mindbox->order()->calculateAuthorizedCart($preorder,
                        Options::getOperationName('calculateAuthorizedCart'))->sendRequest()->getResult()->getField('order');
                } else {

                }



                if (!$preorderInfo) {
                    return new Main\EventResult(Main\EventResult::SUCCESS);
                }

                $discounts = $preorderInfo->getDiscountsInfo();
                foreach ($discounts as $discount) {
                    if ($discount->getType() === 'balance') {
                        $balance = $discount->getField('balance');
                        if ($balance[ 'balanceType' ][ 'ids' ][ 'systemName' ] === 'Main') {
                            $_SESSION[ 'ORDER_AVAILABLE_BONUSES' ] = $discount->getField('availableAmountForCurrentOrder');
                        }
                    }

                    if ($discount->getType() === 'promoCode') {
                        $_SESSION[ 'PROMO_CODE_AMOUNT' ] = $discount[ 'availableAmountForCurrentOrder' ];
                    }
                }


                $lines = $preorderInfo->getLines();

                if (\Bitrix\Main\Loader::includeModule('intensa.logger')) {
                    $logger = new \Intensa\Logger\ILog('OnSaleBasketBeforeSavedHadler');
                    $logger->log('$lines', $lines);
                }




                $mindboxBasket = [];
                $mindboxAdditional = [];
                $context = $basket->getContext();

                foreach ($lines as $line) {
                    $lineId = $line->getField('lineId');
                    $bitrixProduct = $bitrixBasket[ $lineId ];

                    if (isset($mindboxBasket[ $lineId ])) {
                        $mindboxAdditional[] = [
                            'PRODUCT_ID'             => $bitrixProduct->getProductId(),
                            'PRICE'                  => floatval($line->getDiscountedPrice()) / floatval($line->getQuantity()),
                            'CUSTOM_PRICE'           => 'Y',
                            'QUANTITY'               => $line->getQuantity(),
                            'CURRENCY'               => $context[ 'CURRENCY' ],
                            'NAME'                   => $bitrixProduct->getField('NAME'),
                            'LID'                    => SITE_ID,
                            'DETAIL_PAGE_URL'        => $bitrixProduct->getField('DETAIL_PAGE_URL'),
                            'CATALOG_XML_ID'         => $bitrixProduct->getField('CATALOG_XML_ID'),
                            'PRODUCT_XML_ID'         => $bitrixProduct->getField('PRODUCT_XML_ID'),
                            'PRODUCT_PROVIDER_CLASS' => $bitrixProduct->getProviderName(),
                            'CAN_BUY'                => 'Y'
                        ];
                    } else {
                        $mindboxPrice = floatval($line->getDiscountedPrice()) / floatval($line->getQuantity());
                        $bitrixProduct->setField('CUSTOM_PRICE', 'Y');
                        $bitrixProduct->setFieldNoDemand('PRICE', $mindboxPrice);
                        $bitrixProduct->setFieldNoDemand('QUANTITY', $line->getQuantity());
                        $bitrixProduct->save();

                        $mindboxBasket[ $lineId ] = $bitrixProduct;
                    }
                }

                foreach ($mindboxAdditional as $product) {
                    $item = $basket->createItem("catalog", $product[ "PRODUCT_ID" ]);
                    unset($product[ "PRODUCT_ID" ]);
                    $item->setFields($product);
                }

            } catch (Exceptions\MindboxClientException $e) {
                return new Main\EventResult(Main\EventResult::SUCCESS);
            }
        }


        return new Main\EventResult(Main\EventResult::SUCCESS);
    }

    public function OnBeforeUserAddHandler(&$arFields)
    {

        if (\COption::GetOptionString('mindbox.marketing', 'MODE') == 'standard') {
            return $arFields;
        }

        global $APPLICATION;

        $mindbox = static::mindbox();
        if (!$mindbox) {
            return $arFields;
        }

        if (!array_key_exists('location_type', $_POST)) {
            return $arFields;
        }

        $arFields[ 'PERSONAL_PHONE' ] = Helper::formatPhone($arFields[ 'PERSONAL_PHONE' ]);

        $customerDTO = new CustomerRequestDTO([
            'mobilePhone' => $arFields[ 'PERSONAL_PHONE' ],
            'email'       => $arFields[ 'EMAIL' ]
        ]);

        try {
            $response = $mindbox->customer()->checkByPhone($customerDTO, Options::getOperationName('check'), false)
                ->sendRequest()->getResult();
        } catch (Exceptions\MindboxClientException $e) {
            $_SESSION[ 'ANONYM' ][ 'PHONE' ] = $arFields[ 'PERSONAL_PHONE' ];

            return $arFields;
        }


        $customer = $response->getCustomer();
        if ($customer && $customer->getProcessingStatus() === 'Found') {
            $mindboxId = $customer->getId('mindboxId');
            $customerDTO->setFirstName($arFields[ 'NAME' ]);
            $customerDTO->setLastName($arFields[ 'LAST_NAME' ]);
            $customerDTO->setId('mindboxId', $mindboxId);

            $customerDTO = Helper::iconvDTO($customerDTO);
            try {
                $updateResponse = $mindbox->customer()->edit($customerDTO, Options::getOperationName('edit'), true,
                    Helper::isSync())->sendRequest()->getResult();
            } catch (Exceptions\MindboxClientException $e) {
                $_SESSION[ 'ANONYM' ][ 'PHONE' ] = $arFields[ 'PERSONAL_PHONE' ];

                return $arFields;
            }

            $updateResponse = Helper::iconvDTO($updateResponse, false);
            $status = $updateResponse->getStatus();

            if ($status === 'ValidationError') {
                $errors = $updateResponse->getValidationMessages();

                $APPLICATION->ThrowException(self::formatValidationMessages($errors));

                return false;
            }

            $arFields[ 'UF_MINDBOX_ID' ] = $mindboxId;

            return $arFields;
        }


        $anonym = new CustomerRequestDTO([
            'email'       => $arFields[ 'EMAIL' ],
            'firstName'   => $arFields[ 'NAME' ],
            'lastName'    => $arFields[ 'LAST_NAME' ],
            'mobilePhone' => $arFields[ 'PERSONAL_PHONE' ]
        ]);

        $subscriptions = [
            [
                'pointOfContact' => 'Email',
                'isSubscribed'   => true,
                'valueByDefault' => true
            ],
            [
                'pointOfContact' => 'Sms',
                'isSubscribed'   => true,
                'valueByDefault' => true
            ],
        ];
        $anonym->setSubscriptions($subscriptions);


        $anonym = Helper::iconvDTO($anonym);
        try {
            $response = $mindbox->customer()
                ->register($anonym, Options::getOperationName('registerFromAnonymousOrder'), true, Helper::isSync())
                ->sendRequest()->getResult();
        } catch (Exceptions\MindboxClientException $e) {
            $_SESSION[ 'ANONYM' ][ 'PHONE' ] = $arFields[ 'PERSONAL_PHONE' ];

            return $arFields;
        }

        $response = Helper::iconvDTO($response, false);
        $status = $response->getStatus();

        if ($status === 'ValidationError') {
            $errors = $response->getValidationMessages();

            $APPLICATION->ThrowException(self::formatValidationMessages($errors));

            return false;
        }

        $customer = $response->getCustomer();
        if (!$customer) {
            $APPLICATION->ThrowException('');

            return false;
        }

        $mindboxId = $customer->getId('mindboxId');
        $arFields[ 'UF_MINDBOX_ID' ] = $mindboxId;

        if (!$mindboxId) {
            $APPLICATION->ThrowException('');

            return false;
        }

        return $arFields;
    }

    public function OnAfterUserAddHandler(&$arFields)
    {

        $mindBoxId = $arFields[ 'UF_MINDBOX_ID' ];

        if ($mindBoxId) {
            $mindbox = static::mindbox();
            if (!$mindbox) {
                return $arFields;
            }
            $customer = new CustomerRequestDTO();
            $customer->setId('mindboxId', $mindBoxId);
            $customer->setId(Options::getModuleOption('WEBSITE_ID'), $arFields[ "ID" ]);

            try {
                $mindbox->customer()->edit($customer, Options::getOperationName('edit'), true,
                    Helper::isSync())->sendRequest();
            } catch (Exceptions\MindboxClientException $e) {
                $lastResponse = $mindbox->customer()->getLastResponse();
                if ($lastResponse) {
                    $request = $lastResponse->getRequest();

                    QueueTable::push($request);
                }
            }
        }
    }

    /**
     * @return Mindbox
     */
    private static function mindbox()
    {
        $mindbox = Options::getConfig();

        return $mindbox;
    }

    private static function isAnonym($id)
    {
        $mindboxId = Helper::getMindboxId($id);

        if (!$mindboxId) {
            return true;
        }

        return false;
    }


    private static function setCartMindbox($basketItems)
    {
        $mindbox = static::mindbox();
        if (!$mindbox) {
            return;
        }

        $lines = [];
        foreach ($basketItems as $basketItem) {
            $product = new ProductRequestDTO();
            $product->setId(Options::getModuleOption('EXTERNAL_SYSTEM'), Helper::getProductId($basketItem));


            $line = new ProductListItemRequestDTO();
            $line->setProduct($product);
            $line->setCount($basketItem->getQuantity());
            $line->setPricePerItem($basketItem->getPrice());
            $lines[] = $line;
        }

        try {
            $mindbox->productList()->setProductList(new ProductListItemRequestCollection($lines),
                Options::getOperationName('setProductList'))->sendRequest();
        } catch (Exceptions\MindboxClientErrorException $e) {
        } catch (Exceptions\MindboxClientException $e) {
            $lastResponse = $mindbox->productList()->getLastResponse();
            if ($lastResponse) {
                $request = $lastResponse->getRequest();
                QueueTable::push($request);
            }
        }
    }

    private static function formatValidationMessages($errors)
    {
        Loc::loadMessages(__FILE__);

        $strError = '';
        foreach ($errors as $error) {
            $strError .= Loc::getMessage($error->getLocation()) . ': ' . $error->getMessage() . PHP_EOL;
        }

        $strError = rtrim($strError, PHP_EOL);

        return $strError;
    }

}
