<?php
/**
 * Created by @copyright QSOFT.
 */

namespace Mindbox;


use Bitrix\Main\UserTable;
use CPHPCache;
use CSaleOrderProps;
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

        if(!$mindboxId) {
            $mindbox = Options::getConfig();
            $request = $mindbox->getClientV3()->prepareRequest('POST',
                Options::getOperationName('getCustomerInfo'),
                new DTO([
                    'customer' => [
                        'ids' => [
                            Options::getModuleOption('WEBSITE_ID') => $id
                        ]
                    ]
                ]));

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
            $registerResponse = $mindbox->customer()->register($customer,
                Options::getOperationName('register'), true, Helper::isSync())->sendRequest()->getResult();
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

        if($registerResponse) {
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

        if(!$id) {
            $productId = $basketItem->getField('PRODUCT_ID');
            $arProduct = \CIBlockElement::GetByID($productId)->GetNext();
            $id = $arProduct['XML_ID'];
        }

        if(!$id) {
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
        $result = \CIBlock::GetList(
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

    /**
     * @param string $bitrixFieldCode
     * @param string $mindboxFieldCode
     * @param bool $append
     */
    public static function setOrderFieldsMatch($bitrixFieldCode, $mindboxFieldCode, $append = true)
    {
        if (!$append) {
            $matches = [$bitrixFieldCode => $mindboxFieldCode];
        } else {
            $matches = self::getOrderFieldsMatch();
            $matches[$bitrixFieldCode] = $mindboxFieldCode;
        }

        \COption::SetOptionString(ADMIN_MODULE_NAME, 'ORDER_FIELDS_MATCH', json_encode($matches));
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
     * @return string
     */
    public static function getOrderMatchesTable()
    {
        $matches = self::getOrderFieldsMatch();

        $styles = <<<HTML
    <style type="text/css">
        .th {
            background-color: #e0e8ea;
            padding: 15px;
            text-align: center;
            min-width: 400px;
        }
        .th-empty {
            background-color: #e0e8ea;
            padding: 15px;
            text-align: center;
        }
        .td {
            border-top: 1px solid #87919c;
            padding: 15px;
            text-align: center;
        }
        .tr {}
        .table {
            margin: 0 auto !important;
            border-collapse: collapse;
        }
        tr.heading:nth-last-child(-n+8) td {
            display: none;
        }
    </style>
HTML;
        $escapeTable = '</td></tr><tr><td colspan="2"><table class="table">';
        $tableHead = '<tr class="tr"><th class="th">'.getMessage("BITRIX_FIELDS").'</th><th class="th">'.getMessage("MINDBOX_FIELDS").'</th><th class="th-empty"></th></tr>';

        $result = $styles.$escapeTable.$tableHead;

        foreach ($matches as $bitrixCode => $mindboxCode) {
            if (!empty($mindboxCode)) {
                $result .= '<tr class="tr"><td class="td">' . $bitrixCode . '</td>';
                $result .= '<td class="td">' . $mindboxCode . '</td>';
                $result .= '<td class="td"><a class="module_button_delete" data-bitrix="'.$bitrixCode.'" href="javascript:void(0)">' . getMessage("BUTTON_DELETE") . '</a></td></tr>';
            }
        }

        $bottomPadding = '</table></tr></td><tr><td>&nbsp;</td></tr>';
        $result .= $bottomPadding;

        $script = <<<HTML
    <script>
        document.querySelectorAll('.module_button_delete').forEach((element) => {
            element.onclick = (e) => {
                let url = new URL(window.location.href);
                url.searchParams.delete('order_match_action');
                url.searchParams.append('order_match_action', 'delete');
                url.searchParams.append('bitrix_code', e.target.dataset.bitrix);
                window.location.href = url;
            };
        });
    </script>
HTML;
        $result .= $script;


        return $result;
    }

    public static function getAddOrderMatchButton()
    {
        $escapeTable = '</td></tr><tr><td>';
        $styles = <<<HTML
    <style type="text/css">
        .module_button {
            border: 1px solid black;
            border-radius: 5%;
            padding: 8px 25px;
            background-color: #e0e8ea;
            color: black;
            text-decoration: none;
            float: right;
        }
    </style>
HTML;

        $button = '<a class="module_button module_button_add" href="javascript:void(0)">'.getMessage("BUTTON_ADD").'</a>';

        $script = <<<HTML
    <script>
        document.querySelector('.module_button_add').onclick = () => {
            let url = new URL(window.location.href);
            url.searchParams.delete('order_match_action');
            url.searchParams.append('order_match_action', 'add');
            url.searchParams.append('bitrix_code', document.querySelector('[name="MINDBOX_ORDER_BITRIX_FIELDS"]').selectedOptions[0].value);
            url.searchParams.append('mindbox_code', document.querySelector('[name="MINDBOX_ORDER_MINDBOX_FIELDS"]').value);
            window.location.href = url;
        };
    </script>
HTML;


        return $styles.$escapeTable.$button.$script;
    }

    /**
     * Is operations sync?
     *
     * @return $isSync
     */
    public static function isSync()
    {
        if (\COption::GetOptionString('mindbox.marketing', 'MODE') == 'standard') {
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
    public static function isUnAuthorizedOrder($arUser) {
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

    /**
     * @param string $name
     * @return string
     */
    public static function sanitzeNamesForMindbox($name)
    {
        $regexNotChars = '/[^a-zA-Z0-9]/m';
        $regexFirstLetter = '/^[a-zA-Z]/m';

        $name = preg_replace($regexNotChars, '', $name);
        if (!empty($name) && preg_match($regexFirstLetter, $name) === 1) {
            return $name;
        }

        return '';
    }
}