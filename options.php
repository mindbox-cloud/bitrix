<?php
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();
defined('ADMIN_MODULE_NAME') or define('ADMIN_MODULE_NAME', 'mindbox.marketing');

global $APPLICATION;

Cmodule::IncludeModule('mindbox.marketing');
Cmodule::IncludeModule('iblock');

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
    foreach ($_POST as $key => $option) {
        if (strpos($key, 'MINDBOX_') !== false) {
            COption::SetOptionString(ADMIN_MODULE_NAME, str_replace('MINDBOX_', '', $key), $option);
        }
    }
}

IncludeModuleLangFile($_SERVER[ 'DOCUMENT_ROOT' ] . '/bitrix/modules/main/options.php');
IncludeModuleLangFile(__FILE__);

$tabControl = new CAdminTabControl('tabControl', [
    [
        'DIV'   => 'edit1',
        'TAB'   => getMessage('MAIN_TAB_SET'),
        'TITLE' => getMessage('MAIN_TAB_TITLE_SET'),
    ]
]);


$arAllOptions = array(
    getMessage('DOCS_LINK'),
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
    [
        'EXTERNAL_SYSTEM',
        getMessage('EXTERNAL_SYSTEM'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'EXTERNAL_SYSTEM', ''),
        ['text']
    ],
    [
        'WEBSITE_ID',
        getMessage('WEBSITE_ID'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'WEBSITE_ID', ''),
        ['text']

    ],
    [
        'TRANSACTION_ID',
        getMessage('TRANSACTION_ID'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'TRANSACTION_ID', ''),
        ['text']
    ],
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
    getMessage('CATALOG_SETTINGS'),
    [
        'CATALOG_IBLOCK_ID',
        getMessage('CATALOG_IBLOCK_ID'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'CATALOG_IBLOCK_ID', ''),
        [
            'selectbox',
            \Mindbox\Helper::getIblocks()
        ]
    ],
    [
        'YML_NAME',
        getMessage('YML_NAME'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'YML_NAME', 'upload/mindbox.xml'),
        ['text']
    ]
);

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
