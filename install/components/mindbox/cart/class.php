<?php
/**
 * Created by @copyright QSOFT.
 */

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\DTO\V3\Requests\DiscountRequestDTO;
use Mindbox\DTO\V3\Requests\LineRequestDTO;
use Mindbox\DTO\V3\Requests\PreorderRequestDTO;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Helper;
use Mindbox\Options;
use Mindbox\Ajax;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

class Cart extends CBitrixComponent implements Controllerable
{
    protected $actions = [
        'applyCode',
        'applyBonuses',
        'getBalance'
    ];

    private $mindbox;

    public function __construct(CBitrixComponent $component = null)
    {
        parent::__construct($component);

        try {
            if (!Loader::includeModule('mindbox.marketing')) {
                ShowError(GetMessage('MB_CART_MODULE_NOT_INCLUDED', ['#MODULE#' => 'mindbox.marketing']));

                return;
            }

            if (!Loader::includeModule('sale')) {
                ShowError(GetMessage('MB_CART_MODULE_NOT_INCLUDED', ['#MODULE#' => 'sale']));

                return;
            }

            if (!Loader::includeModule('catalog')) {
                ShowError(GetMessage('MB_CART_MODULE_NOT_INCLUDED', ['#MODULE#' => 'catalog']));

                return;
            }
        } catch (LoaderException $e) {
            ShowError($e->getMessage());

            return;
        }

        $this->mindbox = Options::getConfig();
    }

    public function configureActions()
    {
        return Ajax::configureActions($this->actions);
    }

    public function getBalanceAction()
    {
        $balance = $_SESSION['ORDER_AVAILABLE_BONUSES'];

        return [
            'balance' => $balance,
            'message' => GetMessage('MB_CART_BONUSES_LIMIT', ['#LIMIT#' => $balance])
        ];
    }

    public function applyCodeAction($code)
    {
        if (!$this->mindbox) {
            return Ajax::errorResponse(GetMessage('MB_CART_BAD_MODULE_SETTING'));
        }
        $code = htmlspecialcharsEx(trim($code));
        global $USER;
        $mindbox = $this->mindbox;

        $basket = $basket = Bitrix\Sale\Basket::loadItemsForFUser(
            Bitrix\Sale\Fuser::getId(),
            Bitrix\Main\Context::getCurrent()->getSite()
        );


        $preorder = new PreorderRequestDTO();

        foreach ($basket as $basketItem) {
            if ($basketItem->getField('CAN_BUY') == 'N') {
                continue;
            }

            $bitrixBasket[$basketItem->getId()] = $basketItem;
            $productBasePrice = Helper::getBasePrice($basketItem);
            $requestedPromotions = Helper::getRequestedPromotions($basketItem, $basket);

            $arLine = [
                'basePricePerItem' => $productBasePrice,
                'quantity'         => $basketItem->getQuantity(),
                'lineId'           => $basketItem->getId(),
                'product'          => [
                    'ids' => [
                        Options::getModuleOption('EXTERNAL_SYSTEM') => Helper::getElementCode($basketItem->getProductId())
                    ]
                ],
                'status'           => [
                    'ids' => [
                        'externalId' => 'CheckedOut'
                    ]
                ],
            ];

            if (!empty($requestedPromotions)) {
                $arLine['requestedPromotions'] = $requestedPromotions;
            }


            $lines[] = $arLine;
        }

        if (empty($lines)) {
            return false;
        }

        $arOrder = [
            'ids'   => [
                Options::getModuleOption('TRANSACTION_ID') => '',
            ],
            'lines' => $lines
        ];

        if ($code) {
            $arOrder['coupons'] = [
                [
                    'ids' => [
                        "code" => $code
                    ]
                ]
            ];
        } else {
            unset($_SESSION['PROMO_CODE']);
            unset($_SESSION['PROMO_CODE_AMOUNT']);
        }

        $bonuses = $_SESSION['PAY_BONUSES'] ?: 0;
        if ($bonuses) {
            $bonusPoints = [
                'amount' => $bonuses
            ];
            $arOrder['bonusPoints'] = [
                $bonusPoints
            ];
        }

        $preorder->setField('order', $arOrder);

        $customer = new CustomerRequestDTO();
        if ($USER->IsAuthorized()) {
            $mindboxId = Helper::getMindboxId($USER->GetID());
            if ($mindboxId) {
                $customer->setId('mindboxId', $mindboxId);
                $preorder->setCustomer($customer);
            }
        }

        $response = [
            'type'    => 'success',
            'message' => GetMessage('MB_CART_PROMOCODE_SUCCESS')
        ];

        if (\COption::GetOptionString('mindbox.marketing', 'MODE') != 'standard') {
            try {
                if ($USER->IsAuthorized()) {
                    $preorderInfo = $mindbox->order()->calculateAuthorizedCart(
                        $preorder,
                        Options::getOperationName('calculateAuthorizedCart')
                    )->sendRequest()->getResult()->getField('order');
                } else {
                    $preorderInfo = $mindbox->order()->calculateUnauthorizedCart(
                        $preorder,
                        Options::getOperationName('calculateUnauthorizedCart')
                    )->sendRequest()->getResult()->getField('order');
                }

                if ($preorderInfo) {
                    $discounts = $preorderInfo->getDiscountsInfo();
                    $couponsInfo = reset($preorderInfo->getField('couponsInfo'));
                    $totalBonusPointsInfo = $preorderInfo->getField('totalBonusPointsInfo');

                    if (!empty($couponsInfo)) {
                        if ($couponsInfo['coupon']['status'] == 'NotFound') {
                            $response = Ajax::errorResponse(GetMessage('MB_CART_PROMOCODE_NOT_FOUND'));
                        }
                        if ($couponsInfo['coupon']['status'] == 'CanNotBeUsedForCurrentOrder') {
                            $response = Ajax::errorResponse(GetMessage('MB_CART_PROMOCODE_ERR'));
                        }
                        if ($couponsInfo['coupon']['status'] == 'Used') {
                            $response = Ajax::errorResponse(GetMessage('MB_CART_PROMO_USED'));
                        }
                        if ($couponsInfo['coupon']['status'] == 'CanBeUsed') {
                            $_SESSION['PROMO_CODE_AMOUNT'] = $couponsInfo['discountAmountForCurrentOrder'];
                            $_SESSION['PROMO_CODE'] = $code;
                        }
                    }

                    if (!empty($totalBonusPointsInfo)) {
                        $_SESSION['ORDER_AVAILABLE_BONUSES'] = $totalBonusPointsInfo['availableAmountForCurrentOrder'];
                        if ($_SESSION['PAY_BONUSES'] > $_SESSION['ORDER_AVAILABLE_BONUSES']) {
                            $_SESSION['PAY_BONUSES'] = 0;
                        }

                        $balance = $totalBonusPointsInfo['balance'];
                        if ($balance) {
                            setcookie('USER_AVAILABLE_BONUSES', $balance['available'], 0, '/');
                            setcookie('USER_BLOCKED_BONUSES', $balance['blocked'], 0, '/');
                        }
                    }

                    $lines = $preorderInfo->getLines();
                    $mindboxBasket = [];
                    $mindboxAdditional = [];
                    $context = $basket->getContext();

                    foreach ($lines as $line) {
                        $lineId = $line->getField('lineId');
                        $bitrixProduct = $bitrixBasket[$lineId];

                        if (isset($mindboxBasket[$lineId])) {
                            $mindboxAdditional[] = [
                                'PRODUCT_ID'             => $bitrixProduct->getProductId(),
                                'PRICE'                  => floatval($line->getDiscountedPrice()) / floatval($line->getQuantity()),
                                'CUSTOM_PRICE'           => 'Y',
                                'QUANTITY'               => $line->getQuantity(),
                                'CURRENCY'               => $context['CURRENCY'],
                                'NAME'                   => $bitrixProduct->getField('NAME'),
                                'LID'                    => SITE_ID,
                                'DETAIL_PAGE_URL'        => $bitrixProduct->getField('DETAIL_PAGE_URL'),
                                'CATALOG_XML_ID'         => $bitrixProduct->getField('CATALOG_XML_ID'),
                                'PRODUCT_XML_ID'         => $bitrixProduct->getField('PRODUCT_XML_ID'),
                                'PRODUCT_PROVIDER_CLASS' => $bitrixProduct->getProviderName()
                            ];
                        } else {
                            $mindboxPrice = floatval($line->getDiscountedPrice()) / floatval($line->getQuantity());
                            Helper::processHlbBasketRule($lineId, $mindboxPrice);
                            $mindboxBasket[$lineId] = $bitrixProduct;
                        }
                    }

                    foreach ($mindboxAdditional as $product) {
                        $item = $basket->createItem("catalog", $product["PRODUCT_ID"]);
                        unset($product["PRODUCT_ID"]);
                        $item->setFields($product);
                    }
                }
            } catch (MindboxClientException $e) {
                foreach ($basket as $basketItem) {
                    $basketItem->setField('CUSTOM_PRICE', 'N');
                    $basketItem->save();
                }

                return Ajax::errorResponse(GetMessage('MB_CART_PROMO_UNAVAILABLE'));
            }
        }

        return isset($response) ? $response : Ajax::errorResponse(GetMessage('MB_CART_PROMO_UNAVAILABLE'));
    }

    public function applyBonusesAction($bonuses)
    {
        if (!$this->mindbox) {
            return Ajax::errorResponse(GetMessage('MB_CART_BAD_MODULE_SETTING'));
        }
        $bonuses = intval($bonuses);

        $basket = $basket = Bitrix\Sale\Basket::loadItemsForFUser(
            Bitrix\Sale\Fuser::getId(),
            Bitrix\Main\Context::getCurrent()->getSite()
        );

        global $USER;
        $mindbox = $this->mindbox;

        if (!$mindbox) {
            return false;
        }
        $preorder = new PreorderRequestDTO();

        $basketItems = $basket->getBasketItems();
        $lines = [];
        $bitrixBasket = [];

        foreach ($basketItems as $basketItem) {
            if ($basketItem->getField('CAN_BUY') == 'N') {
                continue;
            }

            $bitrixBasket[$basketItem->getId()] = $basketItem;
            $productBasePrice = Helper::getBasePrice($basketItem);
            $requestedPromotions = Helper::getRequestedPromotions($basketItem, $basket);

            $arLine = [
                'basePricePerItem' => $productBasePrice,
                'quantity'         => $basketItem->getQuantity(),
                'lineId'           => $basketItem->getId(),
                'product'          => [
                    'ids' => [
                        Options::getModuleOption('EXTERNAL_SYSTEM') => Helper::getElementCode($basketItem->getProductId())
                    ]
                ],
                'status'           => [
                    'ids' => [
                        'externalId' => 'CheckedOut'
                    ]
                ],
            ];

            if (!empty($requestedPromotions)) {
                $arLine['requestedPromotions'] = [$requestedPromotions];
            }

            $lines[] = $arLine;
        }

        if (empty($lines)) {
            return false;
        }

        $arCoupons = [];
        if ($_SESSION['PROMO_CODE'] && !empty($_SESSION['PROMO_CODE'])) {
            $arCoupons['ids']['code'] = $_SESSION['PROMO_CODE'];
        }


        $arOrder = [
            'ids'   => [
                Options::getModuleOption('TRANSACTION_ID') => '',
            ],
            'lines' => $lines
        ];

        if (!empty($arCoupons)) {
            $arOrder['coupons'] = [$arCoupons];
        }

        if ($bonuses) {
            $bonusPoints = [
                'amount' => $bonuses
            ];
            $arOrder['bonusPoints'] = [
                $bonusPoints
            ];
        }


        $preorder->setField('order', $arOrder);


        $customer = new CustomerRequestDTO();
        if ($USER->IsAuthorized()) {
            $mindboxId = Helper::getMindboxId($USER->GetID());
            if ($mindboxId) {
                $customer->setId('mindboxId', intval($mindboxId));
                $preorder->setCustomer($customer);
            }
        }

        $bonuses = $_SESSION['PAY_BONUSES'] ?: 0;


        if (\COption::GetOptionString('mindbox.marketing', 'MODE') != 'standard') {
            try {
                if ($USER->IsAuthorized()) {
                    $preorderInfo = $mindbox->order()->calculateAuthorizedCart(
                        $preorder,
                        Options::getOperationName('calculateAuthorizedCart')
                    )->sendRequest()->getResult()->getField('order');
                } else {
                    $preorderInfo = $mindbox->order()->calculateUnauthorizedCart(
                        $preorder,
                        Options::getOperationName('calculateUnauthorizedCart')
                    )->sendRequest()->getResult()->getField('order');
                }


                if ($preorderInfo) {
                    $totalBonusPointsInfo = $preorderInfo->getField('totalBonusPointsInfo');


                    if (!empty($totalBonusPointsInfo)) {
                        $_SESSION['ORDER_AVAILABLE_BONUSES'] = $totalBonusPointsInfo['availableAmountForCurrentOrder'];
                        $_SESSION['PAY_BONUSES'] = $totalBonusPointsInfo['spentAmountForCurrentOrder'];
                    }


                    $lines = $preorderInfo->getLines();
                    $mindboxBasket = [];
                    $mindboxAdditional = [];
                    $context = $basket->getContext();

                    foreach ($lines as $line) {
                        $lineId = $line->getField('lineId');
                        $bitrixProduct = $bitrixBasket[$lineId];

                        if (isset($mindboxBasket[$lineId])) {
                            $mindboxAdditional[] = [
                                'PRODUCT_ID'             => $bitrixProduct->getProductId(),
                                'PRICE'                  => floatval($line->getDiscountedPrice()) / floatval($line->getQuantity()),
                                'CUSTOM_PRICE'           => 'Y',
                                'QUANTITY'               => $line->getQuantity(),
                                'CURRENCY'               => $context['CURRENCY'],
                                'NAME'                   => $bitrixProduct->getField('NAME'),
                                'LID'                    => SITE_ID,
                                'DETAIL_PAGE_URL'        => $bitrixProduct->getField('DETAIL_PAGE_URL'),
                                'CATALOG_XML_ID'         => $bitrixProduct->getField('CATALOG_XML_ID'),
                                'PRODUCT_XML_ID'         => $bitrixProduct->getField('PRODUCT_XML_ID'),
                                'PRODUCT_PROVIDER_CLASS' => $bitrixProduct->getProviderName()
                            ];
                        } else {
                            $mindboxPrice = floatval($line->getDiscountedPrice()) / floatval($line->getQuantity());
                            Helper::processHlbBasketRule($lineId, $mindboxPrice);
                            $mindboxBasket[$lineId] = $bitrixProduct;
                        }
                    }

                    foreach ($mindboxAdditional as $product) {
                        $item = $basket->createItem("catalog", $product["PRODUCT_ID"]);
                        unset($product["PRODUCT_ID"]);
                        $item->setFields($product);
                    }
                }
            } catch (MindboxClientException $e) {
                foreach ($basketItems as $basketItem) {
                    $basketItem->setField('CUSTOM_PRICE', 'N');
                    $basketItem->save();
                }

                return Ajax::errorResponse($e);
            }
        }

        return [
            'type' => 'success'
        ];
    }


    public function executeComponent()
    {
        $basket = Bitrix\Sale\Basket::loadItemsForFUser(
            Bitrix\Sale\Fuser::getId(),
            Bitrix\Main\Context::getCurrent()->getSite()
        );

        if ($basket->isEmpty()) {
            return;
        }


        if (\COption::GetOptionString('mindbox.marketing', 'MODE') != 'standard') {
            $this->calculateCart($basket);
        }

        $this->includeComponentTemplate();
    }


    protected function calculateCart($basket)
    {
        global $USER;
        $mindbox = $this->mindbox;

        if (!$mindbox) {
            return false;
        }
        $preorder = new PreorderRequestDTO();

        $basketItems = $basket->getBasketItems();
        $lines = [];
        $bitrixBasket = [];


        foreach ($basketItems as $basketItem) {
            if ($basketItem->getField('CAN_BUY') == 'N') {
                continue;
            }

            $bitrixBasket[$basketItem->getId()] = $basketItem;
            $productBasePrice = Helper::getBasePrice($basketItem);
            $requestedPromotions = Helper::getRequestedPromotions($basketItem, $basket);

            $arLine = [
                'basePricePerItem' => $productBasePrice,
                'quantity'         => $basketItem->getQuantity(),
                'lineId'           => $basketItem->getId(),
                'product'          => [
                    'ids' => [
                        Options::getModuleOption('EXTERNAL_SYSTEM') => Helper::getElementCode($basketItem->getProductId())
                    ]
                ],
                'status'           => [
                    'ids' => [
                        'externalId' => 'CheckedOut'
                    ]
                ],
            ];

            if (!empty($requestedPromotions)) {
                $arLine['requestedPromotions'] = $requestedPromotions;
            }


            $lines[] = $arLine;
        }

        if (empty($lines)) {
            return false;
        }

        $arCoupons = [];
        if ($_SESSION['PROMO_CODE'] && !empty($_SESSION['PROMO_CODE'])) {
            $arCoupons['ids']['code'] = $_SESSION['PROMO_CODE'];
        }

        $arOrder = [
            'ids'   => [
                Options::getModuleOption('TRANSACTION_ID') => '',
            ],
            'lines' => $lines
        ];

        if (!empty($arCoupons)) {
            $arOrder['coupons'] = [$arCoupons];
        }

        $bonuses = $_SESSION['PAY_BONUSES'] ?: 0;

        if ($bonuses && $USER->IsAuthorized()) {
            $bonusPoints = [
                'amount' => $bonuses
            ];
            $arOrder['bonusPoints'] = [
                $bonusPoints
            ];
        } else {
            unset($_SESSION['ORDER_AVAILABLE_BONUSES'], $_SESSION['PAY_BONUSES']);
        }

        $preorder->setField('order', $arOrder);

        //$preorder->setLines($lines);

        $customer = new CustomerRequestDTO();
        if ($USER->IsAuthorized()) {
            $mindboxId = Helper::getMindboxId($USER->GetID());
            if ($mindboxId) {
                $customer->setId('mindboxId', intval($mindboxId));
                $preorder->setCustomer($customer);
            }
        }

        try {
            if ($USER->IsAuthorized()) {
                $preorderInfo = $mindbox->order()->calculateAuthorizedCart(
                    $preorder,
                    Options::getOperationName('calculateAuthorizedCart')
                )->sendRequest()->getResult()->getField('order');
            } else {
                $preorderInfo = $mindbox->order()->calculateUnauthorizedCart(
                    $preorder,
                    Options::getOperationName('calculateUnauthorizedCart')
                )->sendRequest()->getResult()->getField('order');
            }


            $discounts = $preorderInfo->getDiscountsInfo();

            $couponsInfo = reset($preorderInfo->getField('couponsInfo'));
            $totalBonusPointsInfo = $preorderInfo->getField('totalBonusPointsInfo');


            if (!empty($totalBonusPointsInfo)) {
                $_SESSION['ORDER_AVAILABLE_BONUSES'] = $totalBonusPointsInfo['availableAmountForCurrentOrder'];
            }

            $lines = $preorderInfo->getLines();


            $mindboxBasket = [];
            $mindboxAdditional = [];
            $context = $basket->getContext();

            foreach ($lines as $line) {
                $lineId = $line->getField('lineId');
                $bitrixProduct = $bitrixBasket[$lineId];

                if (isset($mindboxBasket[$lineId])) {
                    $mindboxAdditional[] = [
                        'PRODUCT_ID'             => $bitrixProduct->getProductId(),
                        'PRICE'                  => floatval($line->getDiscountedPrice()) / floatval($line->getQuantity()),
                        'CUSTOM_PRICE'           => 'Y',
                        'QUANTITY'               => $line->getQuantity(),
                        'CURRENCY'               => $context['CURRENCY'],
                        'NAME'                   => $bitrixProduct->getField('NAME'),
                        'LID'                    => SITE_ID,
                        'DETAIL_PAGE_URL'        => $bitrixProduct->getField('DETAIL_PAGE_URL'),
                        'CATALOG_XML_ID'         => $bitrixProduct->getField('CATALOG_XML_ID'),
                        'PRODUCT_XML_ID'         => $bitrixProduct->getField('PRODUCT_XML_ID'),
                        'PRODUCT_PROVIDER_CLASS' => $bitrixProduct->getProviderName()
                    ];
                } else {
                    $mindboxPrice = floatval($line->getDiscountedPrice()) / floatval($line->getQuantity());
                    Helper::processHlbBasketRule($lineId, $mindboxPrice);
                    $mindboxBasket[$lineId] = $bitrixProduct;
                }
            }
        } catch (MindboxClientException $e) {
            foreach ($basketItems as $basketItem) {
                $basketItem->setField('CUSTOM_PRICE', 'N');
                $basketItem->save();
            }

            //die($e->getMessage());

            return;
        }
    }
}
