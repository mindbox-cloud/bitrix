<?php

use Mindbox\Options;
use Mindbox\YmlFeedMindbox;
use Mindbox\Helper;

defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();
defined('ADMIN_MODULE_NAME') or define('ADMIN_MODULE_NAME', 'mindbox.marketing');

global $APPLICATION;

Cmodule::IncludeModule('mindbox.marketing');
Cmodule::IncludeModule('iblock');

if ($_GET['order_match_action'] === 'add') {
    Helper::setOrderFieldsMatch($_GET['bitrix_code'], $_GET['mindbox_code']);
} elseif ($_GET['order_match_action'] === 'delete') {
    Helper::setOrderFieldsMatch($_GET['bitrix_code'], '');
}

if (!$USER->isAdmin()) {
    $APPLICATION->authForm('Nope');
}
function ShowParamsHTMLByarray($arParams)
{
    foreach ($arParams as $Option) {
        if (is_array($Option)) {
            $Option[ 0 ] = 'MINDBOX_' . $Option[ 0 ];
        }
        __AdmSettingsDrawRow(ADMIN_MODULE_NAME, $Option);
    }
}


if (isset($_REQUEST['save']) && check_bitrix_sessid()) {
    if (empty($_POST['MINDBOX_PROTOCOL']) || $_POST['MINDBOX_PROTOCOL'] !== 'Y') {
        $_POST['MINDBOX_PROTOCOL'] = 'N';
    }

    foreach ($_POST as $key => $option) {
        if (strpos($key, 'MINDBOX_') !== false) {
            if (is_array($option)) {
                $option = implode(',', $option);
            }
            COption::SetOptionString(ADMIN_MODULE_NAME, str_replace('MINDBOX_', '', $key), $option);
        }
    }
}

IncludeModuleLangFile($_SERVER[ 'DOCUMENT_ROOT' ] . '/bitrix/modules/main/options.php');
IncludeModuleLangFile(__FILE__);

include("install/version.php");

$tabControl = new CAdminTabControl('tabControl', [
    [
        'DIV'   => 'edit1',
        'TAB'   => getMessage('MAIN_TAB_SET'),
        'TITLE' => getMessage('MAIN_TAB_TITLE_SET'),
    ]
]);


$arAllOptions = array(
    getMessage('DOCS_LINK'),
    [
        'MODULE_VERSION',
        getMessage('MODULE_VERSION'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'MODULE_VERSION', $arModuleVersion['VERSION']),
        ['text'],
        'Y'
    ],
    //'<span style="font-size: 13px; font-weight: normal; color: #000;">' . getMessage('MODULE_VERSION') .  . '</span>',
    getMessage('MAINOPTIONS'),
    [
        'MODE',
        getMessage('MODE'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'MODE', 'standard'),
        [
            'selectbox',
            [
                'standard' => getMessage('STANDARD'),
                'loyalty'   =>  getMessage('LOYALTY'),
            ]
        ]
    ],
    [
        'ENDPOINT',
        getMessage('ENDPOINT'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'ENDPOINT', ''),
        ['text']
    ],
    [
        'SECRET_KEY',
        getMessage('SECRET_KEY'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'SECRET_KEY', ''),
        ['text']

    ],
    [
        'WEBSITE_PREFIX',
        getMessage('WEBSITE_PREFIX'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'WEBSITE_PREFIX', ''),
        ['text']
    ],
    [
        'BRAND',
        getMessage('BRAND'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'BRAND', ''),
        ['text']
    ],
    [
        'SYSTEM_NAME',
        getMessage('SYSTEM_NAME'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'SYSTEM_NAME', ''),
        ['text']
    ],
    getMessage('IDENTIFIERS'),
    getMessage('CONNECTION_SETTINGS'),
    [
        'LOG_PATH',
        getMessage('LOG_PATH'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'LOG_PATH', $_SERVER[ 'DOCUMENT_ROOT' ] . '/logs/'),
        ['text']
    ],
    [
        'HTTP_CLIENT',
        getMessage('HTTP_CLIENT'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'HTTP_CLIENT', 'curl'),
        [
            'selectbox',
            [
                'stream' => 'Stream',
                'curl'   => 'Curl'
            ]
        ]
    ],
    [
        'QUEUE_TIMEOUT',
        getMessage('QUEUE_TIMEOUT'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'QUEUE_TIMEOUT', '30'),
        ['text']
    ],
    [
        'TIMEOUT',
        getMessage('TIMEOUT'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'TIMEOUT', '5'),
        ['text']
    ],
    getMessage('CLIENTS'),
    [
        'WEBSITE_ID',
        getMessage('WEBSITE_ID'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'WEBSITE_ID', ''),
        ['text']

    ],
    getMessage('ORDER_SETTINGS'),
    [
        'TRANSACTION_ID',
        getMessage('TRANSACTION_ID'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'TRANSACTION_ID', ''),
        ['text']
    ],
    [
        'ORDER_FIELDS_MATCH',
        '',
        COption::GetOptionString(ADMIN_MODULE_NAME, 'ORDER_FIELDS_MATCH', ''),
        ['text']
    ],
    ['', '', Helper::getOrderMatchesTable(), ['statichtml']],
    [
        'ORDER_BITRIX_FIELDS',
        getMessage('BITRIX_FIELDS'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'ORDER_BITRIX_FIELDS', ''),
        [
            'selectbox',
            Helper::getOrderFields()
        ]
    ],
    [
        'ORDER_MINDBOX_FIELDS',
        getMessage('MINDBOX_FIELDS'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'ORDER_MINDBOX_FIELDS', ''),
        ['text']
    ],
    ['', '', Helper::getAddOrderMatchButton(), ['statichtml']],
    getMessage('PRODUCT_SETTINGS'),
    [
        'EXTERNAL_SYSTEM',
        getMessage('EXTERNAL_SYSTEM'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'EXTERNAL_SYSTEM', ''),
        ['text']
    ],
    [
        'EXTERNAL_SYSTEM',
        getMessage('EXTERNAL_SYSTEM'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'EXTERNAL_SYSTEM', ''),
        ['text']
    ],
    [
        'CATALOG_IBLOCK_ID',
        getMessage('CATALOG_IBLOCK_ID'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'CATALOG_IBLOCK_ID', ''),
        [
            'selectbox',
            Helper::getIblocks()
        ]
    ],
    [
        'PROTOCOL',
        getMessage('SITE_PROTOCOL'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'PROTOCOL', 'N'),
        ['checkbox']
    ],
    [
        'YML_NAME',
        getMessage('YML_NAME'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'YML_NAME', 'upload/mindbox.xml'),
        ['text']
    ]
);

if (!empty(COption::GetOptionString(ADMIN_MODULE_NAME, 'CATALOG_IBLOCK_ID', ''))) {
    if (YmlFeedMindbox::getIblockInfo(Options::getModuleOption("CATALOG_IBLOCK_ID"))['VERSION'] === '1') {
        $arAllOptions[] = ['note' => getMessage(
            'NEED_TABLE_UPGRADE',
            [
                '#LINK#' => '/bitrix/admin/iblock_edit.php?type=' . YmlFeedMindbox::getIblockInfo(Options::getModuleOption("CATALOG_IBLOCK_ID"))['IBLOCK_TYPE_ID'] . '&ID=' . YmlFeedMindbox::getIblockInfo(Options::getModuleOption("CATALOG_IBLOCK_ID"))['ID']
            ]
        )];
    }
    $arAllOptions[] = [
        'CATALOG_PROPS',
        getMessage('CATALOG_PROPS'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'CATALOG_PROPS', ''),
        [
            'multiselectbox',
            \Mindbox\Helper::getProps()
        ]
    ];
}

if (!empty(\Mindbox\Helper::getOffersCatalogId(COption::GetOptionString(ADMIN_MODULE_NAME, 'CATALOG_IBLOCK_ID', '')))) {
    if (YmlFeedMindbox::getIblockInfo(Options::getModuleOption("CATALOG_IBLOCK_ID"))['VERSION'] === '1') {
        $arAllOptions[] = ['note' => getMessage(
            'NEED_TABLE_UPGRADE',
            [
                '#LINK#' => '/bitrix/admin/iblock_edit.php?type=' . YmlFeedMindbox::getIblockInfo(Options::getModuleOption("CATALOG_IBLOCK_ID"))['IBLOCK_TYPE_ID'] . '&ID=' . YmlFeedMindbox::getIblockInfo(Options::getModuleOption("CATALOG_IBLOCK_ID"))['ID']
            ]
        )];
    }
    $arAllOptions[] = [
        'CATALOG_OFFER_PROPS',
        getMessage('CATALOG_OFFER_PROPS'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'CATALOG_OFFER_PROPS', ''),
        [
            'multiselectbox',
            \Mindbox\Helper::getOffersProps()
        ]
    ];
}

?>

<form name='minboxoptions' method='POST' action='<? echo $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($mid)
?>&amp;lang=<? echo LANG ?>'>
    <?= bitrix_sessid_post() ?>
    <?
    $tabControl->Begin();
    $tabControl->BeginNextTab();

    ShowParamsHTMLByArray($arAllOptions);

    $tabControl->EndTab();

    $tabControl->Buttons(); ?>
    <input type='submit' class='adm-btn-save' name='save' value='<?=getMessage('SAVE')?>'>
    <?= bitrix_sessid_post(); ?>
    <? $tabControl->End(); ?>

    <? $tabControl->End(); ?>
</form>
