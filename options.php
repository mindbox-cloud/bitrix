<?php
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();
defined('ADMIN_MODULE_NAME') or define('ADMIN_MODULE_NAME', 'qsoftm.mindbox');

global $APPLICATION;

Cmodule::IncludeModule('qsoftm.mindbox');

if (!$USER->isAdmin()) {
    $APPLICATION->authForm('Nope');
}
function ShowParamsHTMLByarray($arParams)
{
    foreach ($arParams as $Option) {
        if (is_array($Option)) {
            $Option[0] = 'MINDBOX_' . $Option[0];
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

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/options.php');
IncludeModuleLangFile(__FILE__);

$tabControl = new CAdminTabControl('tabControl', [
    [
        'DIV' => 'edit1',
        'TAB' => getMessage('MAIN_TAB_SET'),
        'TITLE' => getMessage('MAIN_TAB_TITLE_SET'),
    ]
]);

$arAllOptions = array(
    getMessage('MAINOPTIONS'),
    getMessage('DOCS_LINK'),
    [
        'ENDPOINT',
        getMessage('ENDPOINT'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'ENDPOINT', ''),
        ['text']
    ],
    [
        'DOMAIN',
        getMessage('DOMAIN'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'DOMAIN', ''),
        ['text']
    ],
    [
        'POINT_OF_CONTACT',
        getMessage('POINT_OF_CONTACT'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'POINT_OF_CONTACT', ''),
        ['text']
    ],
    [
        'SECRET_KEY',
        getMessage('SECRET_KEY'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'SECRET_KEY', ''),
        ['text']

    ],
    [
        'WEBSITE_ID',
        getMessage('WEBSITE_ID'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'WEBSITE_ID', 'websiteId'),
        ['text']

    ],
    [
        'LOG_PATH',
        getMessage('LOG_PATH'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'LOG_PATH', $_SERVER['DOCUMENT_ROOT'] .'/logs/'),
        ['text']
    ],
    [
        'HTTP_CLIENT',
        getMessage('HTTP_CLIENT'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'HTTP_CLIENT', 'stream'),
        [
            'selectbox',
            [
                'stream' => 'Stream',
                'curl' => 'Curl'
            ]
        ]
    ],
    [
        'TIMEOUT',
        getMessage('TIMEOUT'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'TIMEOUT', '5'),
        ['text']
    ],
    [
        'QUEUE_TIMEOUT',
        getMessage('QUEUE_TIMEOUT'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'QUEUE_TIMEOUT', '30'),
        ['text']
    ],
    [
        'WEBSITE_PREFIX',
        getMessage('WEBSITE_PREFIX'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'WEBSITE_PREFIX', 'Website'),
        ['text']
    ],
    [
        'TRANSACTION_ID',
        getMessage('TRANSACTION_ID'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'TRANSACTION_ID', 'websiteTransactionId'),
        ['text']
    ],
    [
        'CATALOG_IBLOCK_ID',
        getMessage('CATALOG_IBLOCK_ID'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'CATALOG_IBLOCK_ID', '0'),
        ['text']
    ],
    [
        'YML_NAME',
        getMessage('YML_NAME'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'YML_NAME', 'test.xml'),
        ['text']
    ],
    [
        'EXTERNAL_SYSTEM',
        getMessage('EXTERNAL_SYSTEM'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'EXTERNAL_SYSTEM', 'sap'),
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
    <input type='submit' class='adm-btn-save' name='save' value='Сохранить'>
    <?= bitrix_sessid_post(); ?>
    <? $tabControl->End(); ?>

    <? $tabControl->End(); ?>
</form>
