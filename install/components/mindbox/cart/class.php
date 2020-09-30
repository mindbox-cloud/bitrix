<?php
/**
 * Created by @copyright QSOFT.
 */

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Mindbox\DTO\V2\Requests\CustomerRequestDTO;
use Mindbox\DTO\V2\Requests\CustomerRequestDTO as CustomerRequestV2DTO;
use Mindbox\DTO\V2\Requests\DiscountRequestDTO;
use Mindbox\DTO\V2\Requests\LineRequestDTO;
use Mindbox\DTO\V2\Requests\PreorderRequestDTO;
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
            if(!Loader::includeModule('qsoftm.mindbox')) {
                ShowError(GetMessage('MB_CART_MODULE_NOT_INCLUDED', ['#MODULE#' => 'qsoftm.mindbox']));;
                return;
            }

            if(!Loader::includeModule('sale')) {
                ShowError(GetMessage('MB_CART_MODULE_NOT_INCLUDED', ['#MODULE#' => 'sale']));;
                return;
            }

            if(!Loader::includeModule('catalog')) {
                ShowError(GetMessage('MB_CART_MODULE_NOT_INCLUDED', ['#MODULE#' => 'catalog']));;
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
        $balance = $_SESSION['ORDER_AVAILABLE_BONUSES'] - $_SESSION['PAY_BONUSES'];

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

        $basket = $basket = Bitrix\Sale\Basket::loadItemsForFUser(Bitrix\Sale\Fuser::getId(),
            Bitrix\Main\Context::getCurrent()->getSite());

        $preorder = new PreorderRequestDTO();

        /** @var Basket $basket */
        $basketItems = $basket->getBasketItems();
        $lines = [];
        $bitrixBasket = [];
        $skuId = Options::getModuleOption('USE_SKU') ? 'skuId' : 'productId';
        foreach ($basketItems as $basketItem) {
            $bitrixBasket[$basketItem->getId()] = $basketItem;
            $line = new LineRequestDTO();
            $line->setField('lineId', $basketItem->getId());
            $line->setQuantity($basketItem->getQuantity());
            $catalogPrice = CPrice::GetBasePrice($basketItem->getProductId());
            $catalogPrice = $catalogPrice['PRICE'] ?: 0;
            $line->setSku([
                $skuId => Helper::getProductId($basketItem->getField('PRODUCT_XML_ID')),
                'basePricePerItem' => $catalogPrice
            ]);

            $lines[] = $line;
        }

        $preorder->setLines($lines);

        $customer = new CustomerRequestDTO();
        if ($USER->IsAuthorized()) {
            $customer->setField('isAuthorized', true);

            $mindboxId = Helper::getMindboxId($USER->GetID());
            if ($mindboxId) {
                $customer->setId('mindbox', $mindboxId);
            }
        } else {
            $customer->setField('isAuthorized', false);
        }

        $preorder->setCustomer($customer);
        $preorder->setPointOfContact(Options::getModuleOption('POINT_OF_CONTACT'));

        $discounts = [];
        $bonuses = $_SESSION['PAY_BONUSES'] ? : 0;
        if ($bonuses) {
            $discounts[] = new DiscountRequestDTO([
                'type' => 'balance',
                'amount' => $bonuses,
                'balanceType' => [
                    'ids' => ['systemName' => 'Main']
                ]
            ]);
        }

        if ($code) {
            $discounts[] = new DiscountRequestDTO([
                'type' => 'promoCode',
                'id' => $code
            ]);
        } else {
            unset($_SESSION['PROMO_CODE']);
            unset($_SESSION['PROMO_CODE_AMOUNT']);
        }

        if(!empty($discounts)) {
            $preorder->setDiscounts($discounts);
        }

        $response = [
            'type' => 'success',
            'message' => GetMessage('MB_CART_PROMOCODE_SUCCESS')
        ];

        if (\COption::GetOptionString('qsoftm.mindbox', 'MODE') != 'standard') {
            try {
                $preorderInfo = $mindbox->order()->calculateCart($preorder,
                    Options::getOperationName('calculateCart'))->sendRequest()->getResult()->getField('order');

                if($preorderInfo) {
                    $discounts = $preorderInfo->getDiscountsInfo();
                    foreach ($discounts as $discount) {
                        if ($discount->getType() === 'balance') {
                            $_SESSION['ORDER_AVAILABLE_BONUSES'] = $discount->getField('availableAmountForCurrentOrder');
                            if ($_SESSION['PAY_BONUSES'] > $_SESSION['ORDER_AVAILABLE_BONUSES']) {
                                $_SESSION['PAY_BONUSES'] = 0;
                            }

                            $balance = $discount->getField('balance');
                            if ($balance && $balance['balanceType']['ids']['systemName'] === 'Main') {
                                setcookie('USER_AVAILABLE_BONUSES', $balance['available'], 0, '/');
                                setcookie('USER_BLOCKED_BONUSES', $balance['blocked'], 0, '/');
                            }
                        }

                        if ($discount->getType() === 'promoCode') {
                            $status = $discount->getField('promoCode')['status'];
                            if ($status !== 'CanBeUsed') {
                                $response = Ajax::errorResponse(GetMessage('MB_CART_PROMOCODE_ERR'));
                            } else {
                                $_SESSION['PROMO_CODE_AMOUNT'] = $discount->getField('availableAmountForCurrentOrder');
                                $_SESSION['PROMO_CODE'] = $code;
                            }
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
                                'PRODUCT_ID' => $bitrixProduct->getProductId(),
                                'PRICE' => floatval($line->getDiscountedPrice()) / floatval($line->getQuantity()),
                                'CUSTOM_PRICE' => 'Y',
                                'QUANTITY' => $line->getQuantity(),
                                'CURRENCY' => $context['CURRENCY'],
                                'NAME' => $bitrixProduct->getField('NAME'),
                                'LID' => SITE_ID,
                                'DETAIL_PAGE_URL' => $bitrixProduct->getField('DETAIL_PAGE_URL'),
                                'CATALOG_XML_ID' => $bitrixProduct->getField('CATALOG_XML_ID'),
                                'PRODUCT_XML_ID' => $bitrixProduct->getField('PRODUCT_XML_ID'),
                                'PRODUCT_PROVIDER_CLASS' => $bitrixProduct->getProviderName()
                            ];
                        } else {
                            $mindboxPrice = floatval($line->getDiscountedPrice()) / floatval($line->getQuantity());
                            $bitrixProduct->setField('CUSTOM_PRICE', 'Y');
                            $bitrixProduct->setFieldNoDemand('PRICE', $mindboxPrice);
                            $bitrixProduct->setFieldNoDemand('QUANTITY', $line->getQuantity());
                            $bitrixProduct->save();

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

        global $USER;
        $mindbox = $this->mindbox;
        $basket = $basket = Bitrix\Sale\Basket::loadItemsForFUser(Bitrix\Sale\Fuser::getId(),
            Bitrix\Main\Context::getCurrent()->getSite());

        $preorder = new PreorderRequestDTO();

        /** @var Basket $basket */
        $basketItems = $basket->getBasketItems();
        $lines = [];
        $bitrixBasket = [];
        $skuId = Options::getModuleOption('USE_SKU') ? 'skuId' : 'productId';
        foreach ($basketItems as $basketItem) {
            $bitrixBasket[$basketItem->getId()] = $basketItem;
            $line = new LineRequestDTO();
            $line->setField('lineId', $basketItem->getId());
            $line->setQuantity($basketItem->getQuantity());
            $catalogPrice = CPrice::GetBasePrice($basketItem->getProductId());
            $catalogPrice = $catalogPrice['PRICE'] ?: 0;
            $line->setSku([
                $skuId => Helper::getProductId($basketItem->getField('PRODUCT_XML_ID')),
                'basePricePerItem' => $catalogPrice
            ]);

            $lines[] = $line;
        }

        $preorder->setLines($lines);

        $customer = new CustomerRequestDTO();
        if ($USER->IsAuthorized()) {
            $customer->setField('isAuthorized', true);

            $dbUser = Bitrix\Main\UserTable::getList(
                [
                    'select' => ['UF_MINDBOX_ID'],
                    'filter' => ['ID' => $USER->GetID()],
                    'limit' => 1
                ]
            )->fetch();

            if ($dbUser) {
                $customer->setId('mindbox', $dbUser['UF_MINDBOX_ID']);
            }
        } else {
            $customer->setField('isAuthorized', false);
        }

        $preorder->setCustomer($customer);
        $preorder->setPointOfContact(Options::getModuleOption('POINT_OF_CONTACT'));

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

        $preorder->setDiscounts($discounts);

        if (\COption::GetOptionString('qsoftm.mindbox', 'MODE') != 'standard') {
            try {
                $preorderInfo = $mindbox->order()->calculateCart($preorder,
                    Options::getOperationName('calculateCart'))->sendRequest()->getResult()->getField('order');

                if ($preorderInfo) {
                    $discounts = $preorderInfo->getDiscountsInfo();
                    foreach ($discounts as $discount) {
                        if ($discount->getType() === 'balance') {
                            $balance = $discount->getField('balance');
                            if ($balance['balanceType']['ids']['systemName'] === 'Main') {
                                $_SESSION['ORDER_AVAILABLE_BONUSES'] = $discount->getField('availableAmountForCurrentOrder');
                            }
                            $_SESSION['PAY_BONUSES'] = $bonuses;
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
                                'PRODUCT_ID' => $bitrixProduct->getProductId(),
                                'PRICE' => floatval($line->getDiscountedPrice()) / floatval($line->getQuantity()),
                                'CUSTOM_PRICE' => 'Y',
                                'QUANTITY' => $line->getQuantity(),
                                'CURRENCY' => $context['CURRENCY'],
                                'NAME' => $bitrixProduct->getField('NAME'),
                                'LID' => SITE_ID,
                                'DETAIL_PAGE_URL' => $bitrixProduct->getField('DETAIL_PAGE_URL'),
                                'CATALOG_XML_ID' => $bitrixProduct->getField('CATALOG_XML_ID'),
                                'PRODUCT_XML_ID' => $bitrixProduct->getField('PRODUCT_XML_ID'),
                                'PRODUCT_PROVIDER_CLASS' => $bitrixProduct->getProviderName()
                            ];
                        } else {
                            $mindboxPrice = floatval($line->getDiscountedPrice()) / floatval($line->getQuantity());
                            $bitrixProduct->setField('CUSTOM_PRICE', 'Y');
                            $bitrixProduct->setFieldNoDemand('PRICE', $mindboxPrice);
                            $bitrixProduct->setFieldNoDemand('QUANTITY', $line->getQuantity());
                            $bitrixProduct->save();

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


        $basket = Bitrix\Sale\Basket::loadItemsForFUser(Bitrix\Sale\Fuser::getId(),
            Bitrix\Main\Context::getCurrent()->getSite());

        if ($basket->isEmpty()) {
            return;
        }

        if (\COption::GetOptionString('qsoftm.mindbox', 'MODE') != 'standard') {
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
        $skuId = Options::getModuleOption('USE_SKU') ? 'skuId' : 'productId';
        foreach ($basketItems as $basketItem) {
            $bitrixBasket[$basketItem->getId()] = $basketItem;
            $line = new LineRequestDTO();
            $line->setField('lineId', $basketItem->getId());
            $line->setQuantity($basketItem->getQuantity());
            $catalogPrice = \CPrice::GetBasePrice($basketItem->getProductId())['PRICE'];
            $line->setSku([
                $skuId => Helper::getProductId($basketItem->getField('PRODUCT_XML_ID')),
                'basePricePerItem' => $catalogPrice
            ]);

            $lines[] = $line;
        }
        if (empty($lines)) {
            return false;
        }
        $preorder->setLines($lines);

        $customer = new CustomerRequestV2DTO();
        if ($USER->IsAuthorized()) {
            $customer->setField('isAuthorized', true);

            $mindboxId = Helper::getMindboxId($USER->GetID());

            if ($mindboxId) {
                $customer->setId('mindbox', $mindboxId);
            }
        } else {
            $customer->setField('isAuthorized', false);
        }

        $preorder->setCustomer($customer);
        $preorder->setPointOfContact(Options::getModuleOption('POINT_OF_CONTACT'));

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
            $preorder->setDiscounts($discounts);
        }

        try {
            $preorderInfo = $mindbox->order()->calculateCart($preorder,
                Options::getOperationName('calculateCart'))->sendRequest()->getResult()->getField('order');

            $discounts = $preorderInfo->getDiscountsInfo();
            foreach ($discounts as $discount) {
                if ($discount->getType() === 'balance') {
                    $balance = $discount->getField('balance');
                    if ($balance['balanceType']['ids']['systemName'] === 'Main') {
                        $_SESSION['ORDER_AVAILABLE_BONUSES'] = $discount->getField('availableAmountForCurrentOrder');
                    }
                }

                if ($discount->getType() === 'promoCode') {
                    $status = $discount->getField('promoCode')['status'];
                    if ($status !== 'CanBeUsed') {
                        unset($_SESSION['PROMO_CODE']);
                        unset($_SESSION['PROMO_CODE_AMOUNT']);
                    } else {
                        $_SESSION['PROMO_CODE_AMOUNT'] = $discount->getField('availableAmountForCurrentOrder');
                        $_SESSION['PROMO_CODE'] = $code;
                    }
                }
            }


            $lines = $preorderInfo->getLines();
            $mindboxBasket = [];
            $mindboxAdditional = [];
            $context = $basket->getContext();

            foreach ($lines as $line) {
                $lineId = $line->getField('lineId');
                $bitrixProduct = $bitrixBasket[$lineId];

                if(isset($mindboxBasket[$lineId])) {
                    $mindboxAdditional[] = [
                        'PRODUCT_ID' => $bitrixProduct->getProductId(),
                        'PRICE' => floatval($line->getDiscountedPrice()) / floatval($line->getQuantity()),
                        'CUSTOM_PRICE' => 'Y',
                        'QUANTITY' => $line->getQuantity(),
                        'CURRENCY' => $context['CURRENCY'],
                        'NAME' => $bitrixProduct->getField('NAME'),
                        'LID'=> SITE_ID,
                        'DETAIL_PAGE_URL' => $bitrixProduct->getField('DETAIL_PAGE_URL'),
                        'CATALOG_XML_ID' => $bitrixProduct->getField('CATALOG_XML_ID'),
                        'PRODUCT_XML_ID' => $bitrixProduct->getField('PRODUCT_XML_ID'),
                        'PRODUCT_PROVIDER_CLASS' => $bitrixProduct->getProviderName()
                    ];
                } else {
                    $mindboxPrice = floatval($line->getDiscountedPrice()) / floatval($line->getQuantity());
                    $bitrixProduct->setField('CUSTOM_PRICE', 'Y');
                    $bitrixProduct->setFieldNoDemand('PRICE', $mindboxPrice);
                    $bitrixProduct->setFieldNoDemand('QUANTITY', $line->getQuantity());
                    $bitrixProduct->save();

                    $mindboxBasket[$lineId] = $bitrixProduct;
                }
            }
        } catch (MindboxClientException $e) {
            foreach ($basketItems as $basketItem) {
                $basketItem->setField('CUSTOM_PRICE', 'N');
                $basketItem->save();
            }
            return;
        }
    }
}