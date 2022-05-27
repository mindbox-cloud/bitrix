<?php

use Mindbox\Options;
use Mindbox\YmlFeedMindbox;
use Mindbox\Helper;

defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();
defined('MINDBOX_ADMIN_MODULE_NAME') or define('MINDBOX_ADMIN_MODULE_NAME', 'mindbox.marketing');

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
            $Option[0] = 'MINDBOX_' . $Option[0];
        }
        __AdmSettingsDrawRow(MINDBOX_ADMIN_MODULE_NAME, $Option);
    }
}

$mayEmptyProps = [
    'MINDBOX_CATALOG_PROPS',
    'MINDBOX_CATALOG_OFFER_PROPS',
    'MINDBOX_ENABLE_EVENT_LIST',
    'MINDBOX_CONTINUE_USER_GROUPS'
];

if (isset($_REQUEST['save']) && check_bitrix_sessid()) {
    if (empty($_POST['MINDBOX_PROTOCOL']) || $_POST['MINDBOX_PROTOCOL'] !== 'Y') {
        $_POST['MINDBOX_PROTOCOL'] = 'N';
    }

    foreach ($_POST as $key => $option) {
        if (strpos($key, 'MINDBOX_') !== false) {
            if (is_array($option)) {
                $option = implode(',', $option);
            }

            if ($key === 'MINDBOX_LOG_LIFE_TIME' && preg_match('#\D#s'.BX_UTF_PCRE_MODIFIER, $option)) {
                CAdminMessage::ShowMessage(['MESSAGE' => GetMessage('INCORRECT_INTEGER_VALUE', ['#INPUT#' => GetMessage('LOG_LIFE_TIME')]), 'HTML' => true, 'TYPE' => 'ERROR']);
                continue;
            }

            COption::SetOptionString(MINDBOX_ADMIN_MODULE_NAME, str_replace('MINDBOX_', '', $key), $option);
        }
    }

    foreach ($mayEmptyProps as $mayEmptyProp) {
        if (!isset($_POST[$mayEmptyProp])) {
            COption::SetOptionString(MINDBOX_ADMIN_MODULE_NAME, str_replace('MINDBOX_', '', $mayEmptyProp), '');
        }
    }

    $defaultOptions = \Bitrix\Main\Config\Option::getDefaults("mindbox.marketing");
    $trackerJsFilename = $_SERVER["DOCUMENT_ROOT"] . $defaultOptions['TRACKER_JS_FILENAME'];
    $trackerJsFilenameOrig = $_SERVER["DOCUMENT_ROOT"] . $defaultOptions['TRACKER_JS_FILENAME_ORIGINAL'];
    if (file_exists($trackerJsFilenameOrig)) {
        file_put_contents(
            $trackerJsFilename,
            str_replace(
                '#endpointId#',
                COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'ENDPOINT', ''),
                file_get_contents($trackerJsFilenameOrig)
            )
        );
    }

    if ($_REQUEST['MINDBOX_LOG_PATH'] !== COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'MINDBOX_LOG_PATH', '')) {
        $mindboxLog = new \Mindbox\AccessLogs();
        $mindboxLog->setLogAccess();
    }
}

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/options.php');
IncludeModuleLangFile(__FILE__);

include("install/version.php");

$tabControl = new CAdminTabControl('tabControl', [
    [
        'DIV'   => 'edit1',
        'TAB'   => getMessage('COMMON_SETTINGS'),
        'TITLE' => getMessage('COMMON_SETTINGS'),
    ],
    [
        'DIV'   => 'edit2',
        'TAB'   => getMessage('FEED'),
        'TITLE' => getMessage('FEED'),
    ],
    [
        'DIV'   => 'edit3',
        'TAB'   => getMessage('CLIENTS'),
        'TITLE' => getMessage('CLIENTS'),
    ],
    [
        'DIV'   => 'edit4',
        'TAB'   => getMessage('ORDERS'),
        'TITLE' => getMessage('ORDERS'),
    ]
]);


$arAllOptions['COMMON'] = [
    ['', '', Helper::adminTableScripts(), ['statichtml']],
    getMessage('DOCS_LINK'),
    [
        'MODULE_VERSION',
        getMessage('MODULE_VERSION'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'MODULE_VERSION', $arModuleVersion['VERSION']),
        ['text']
    ],
    getMessage('CONNECTION_SETTINGS'),
    [
        'MODE',
        getMessage('MODE'),
        COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'MODE', 'standard'),
        [
            'selectbox',
            [
                'standard' => getMessage('STANDARD'),
                'loyalty'  => getMessage('LOYALTY'),
            ]
        ]
    ],
    [
        'ENDPOINT',
        getMessage('ENDPOINT'),
        COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'ENDPOINT', ''),
        ['text']
    ],
    [
        'SECRET_KEY',
        getMessage('SECRET_KEY'),
        COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'SECRET_KEY', ''),
        ['text']

    ],
    [
        'WEBSITE_PREFIX',
        getMessage('WEBSITE_PREFIX'),
        COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'WEBSITE_PREFIX', ''),
        ['text']
    ],
    [
        'BRAND',
        getMessage('BRAND'),
        COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'BRAND', ''),
        ['text']
    ],
    [
        'SYSTEM_NAME',
        getMessage('SYSTEM_NAME'),
        COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'SYSTEM_NAME', ''),
        ['text']
    ],
    [
        'API_DOMAIN',
        getMessage('API_DOMAIN'),
        COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'API_DOMAIN', 'ru'),
        [
            'selectbox',
            [
                'ru'    => 'api.mindbox.ru',
                'cloud' => 'api.mindbox.cloud',
                'api-ru' => 'api-ru.mindbox.cloud',
            ]
        ]
    ],
    [
        'HTTP_CLIENT',
        getMessage('HTTP_CLIENT'),
        COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'HTTP_CLIENT', 'curl'),
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
        COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'QUEUE_TIMEOUT', '30'),
        ['text']
    ],
    [
        'TIMEOUT',
        getMessage('TIMEOUT'),
        COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'TIMEOUT', '5'),
        ['text']
    ],
    [
        'LOG_PATH',
        getMessage('LOG_PATH'),
        COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'LOG_PATH', $_SERVER['DOCUMENT_ROOT'] . '/logs/'),
        ['text']
    ],
    [
        'LOG_LIFE_TIME',
        getMessage('LOG_LIFE_TIME'),
        COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'LOG_LIFE_TIME', 0),
        ['text']
    ],
];

$arAllOptions['FEED'] = [
    [
        'EXTERNAL_SYSTEM',
        getMessage('EXTERNAL_SYSTEM'),
        COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'EXTERNAL_SYSTEM', ''),
        ['text']
    ],
    [
        'CATALOG_IBLOCK_ID',
        getMessage('CATALOG_IBLOCK_ID'),
        COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'CATALOG_IBLOCK_ID', ''),
        [
            'selectbox',
            Helper::getIblocks()
        ]
    ],
    [
        'PROTOCOL',
        getMessage('SITE_PROTOCOL'),
        COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'PROTOCOL', 'N'),
        ['checkbox']
    ],
    [
        'YML_NAME',
        getMessage('YML_NAME'),
        COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'YML_NAME', 'upload/mindbox.xml'),
        ['text']
    ],
    [
        'YML_CHUNK_SIZE',
        getMessage('YML_CHUNK_SIZE'),
        COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'YML_CHUNK_SIZE', 1000),
        ['text']
    ],
    'CATALOG_PROPS_UPGRADE'       => '',
    'CATALOG_PROPS'               => '',
    'CATALOG_OFFER_PROPS_UPGRADE' => '',
    'CATALOG_OFFER_PROPS'         => '',
];

$arAllOptions['CLIENTS'] = [
    [
        'WEBSITE_ID',
        getMessage('WEBSITE_ID'),
        COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'WEBSITE_ID', ''),
        ['text']

    ],
    getMessage('ADDITIONAL_FIELDS_SETTINGS'),
    [
        'USER_BITRIX_FIELDS',
        getMessage('BITRIX_FIELDS'),
        COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'USER_BITRIX_FIELDS', ''),
        [
            'selectbox',
            Helper::getUserFields()
        ]
    ],
    [
        'USER_MINDBOX_FIELDS',
        getMessage('MINDBOX_FIELDS'),
        COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'USER_MINDBOX_FIELDS', ''),
        ['text']
    ],
    ['', '', Helper::getAddOrderMatchButton('user_module_button_add'), ['statichtml']],
    ['', '', Helper::getUserMatchesTable(), ['statichtml']],
    [
        'USER_FIELDS_MATCH',
        '',
        COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'USER_FIELDS_MATCH', ''),
        ['text']
    ],
];

$arAllOptions['ORDERS'] = [
    [
        'TRANSACTION_ID',
        getMessage('TRANSACTION_ID'),
        COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'TRANSACTION_ID', ''),
        ['text']
    ],
    getMessage('ADDITIONAL_FIELDS_SETTINGS'),
    [
        'ORDER_BITRIX_FIELDS',
        getMessage('BITRIX_FIELDS'),
        COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'ORDER_BITRIX_FIELDS', ''),
        [
            'selectbox',
            Helper::getOrderFields()
        ]
    ],
    [
        'ORDER_MINDBOX_FIELDS',
        getMessage('MINDBOX_FIELDS'),
        COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'ORDER_MINDBOX_FIELDS', ''),
        ['text']
    ],
    ['', '', Helper::getAddOrderMatchButton('order_module_button_add'), ['statichtml']],
    ['', '', Helper::getOrderMatchesTable(), ['statichtml']],
    [
        'ORDER_FIELDS_MATCH',
        '',
        COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'ORDER_FIELDS_MATCH', '{}'),
        ['text']
    ],
    getMessage('ORDER_STATUS_SETTINGS'),
    [
        'ORDER_STATUS_BITRIX_LIST',
        getMessage('ORDER_STATUS_BITRIX_LIST'),
        COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'ORDER_STATUS_BITRIX_LIST', ''),
        [
            'selectbox',
            Helper::getBitrixOrderStatusList()
        ]
    ],
    [
        'ORDER_STATUS_MINDBOX_LIST',
        getMessage('ORDER_STATUS_MINDBOX_LIST'),
        COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'ORDER_STATUS_MINDBOX_LIST', ''),
        [
            'selectbox',
            [
                'CheckedOut' => 'CheckedOut',
                'Delivered' => 'Delivered',
                'Paid' => 'Paid',
                'Cancelled' => 'Cancelled',
                'Returned' => 'Returned'
            ]
        ]
    ],
    [
        'ORDER_STATUS_MINDBOX_ADDITIONAL',
        '',
        COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'ORDER_STATUS_MINDBOX_ADDITIONAL', ''),
        ['text']
    ],
    ['', '', Helper::getAddOrderMatchButton('order_status_module_button_add'), ['statichtml']],
    [
        'ORDER_STATUS_FIELDS_MATCH',
        '',
        COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'ORDER_STATUS_FIELDS_MATCH', '{}'),
        ['text']
    ],
    ['', '', Helper::getOrderStatusMatchesTable(), ['statichtml']],

];

if (!empty(COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'CATALOG_IBLOCK_ID', ''))) {
    $arAllOptions['FEED']['CATALOG_PROPS'] = [
        'CATALOG_PROPS',
        getMessage('CATALOG_PROPS'),
        COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'CATALOG_PROPS', ''),
        [
            'multiselectbox',
            \Mindbox\Helper::getProps()
        ]
    ];
}

if (!empty(\Mindbox\Helper::getOffersCatalogId(COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'CATALOG_IBLOCK_ID', '')))) {
    $arAllOptions['FEED']['CATALOG_OFFER_PROPS'] = [
        'CATALOG_OFFER_PROPS',
        getMessage('CATALOG_OFFER_PROPS'),
        COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'CATALOG_OFFER_PROPS', ''),
        [
            'multiselectbox',
            \Mindbox\Helper::getOffersProps()
        ]
    ];
}

$arAllOptions['COMMON'][] = getMessage('EVENT_LIST_GROUP');

$eventList = \Mindbox\EventController::getOptionEventList();
$optionEventCode = \Mindbox\EventController::getOptionEventCode();
$arAllOptions['COMMON'][] = [
    $optionEventCode,
    getMessage($optionEventCode),
    COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, $optionEventCode, ''),
    [
        'multiselectbox',
        $eventList,
    ]
];


$arAllOptions['COMMON'][] = [
    'CONTINUE_USER_GROUPS',
    getMessage('CONTINUE_USER_GROUPS'),
    COption::GetOptionString(MINDBOX_ADMIN_MODULE_NAME, 'CONTINUE_USER_GROUPS', ''),
    [
        'multiselectbox',
        \Mindbox\Helper::getGroups(),
    ]
];
?>

<form name='minboxoptions' method='POST'
      action='<?php echo $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($mid)
        ?>&amp;lang=<?php echo LANG ?>'>
    <?= bitrix_sessid_post() ?>
    <?php
    $tabControl->Begin();

    $tabControl->BeginNextTab();
    ShowParamsHTMLByArray($arAllOptions['COMMON']);
    $tabControl->EndTab();

    $tabControl->BeginNextTab();
    ShowParamsHTMLByArray($arAllOptions['FEED']);
    $tabControl->EndTab();

    $tabControl->BeginNextTab();
    ShowParamsHTMLByArray($arAllOptions['CLIENTS']);
    $tabControl->EndTab();

    $tabControl->BeginNextTab();
    ShowParamsHTMLByArray($arAllOptions['ORDERS']);
    $tabControl->EndTab();


    $tabControl->Buttons(); ?>
    <input type='submit' class='adm-btn-save' name='save' value='<?= getMessage('SAVE') ?>'>
    <?= bitrix_sessid_post(); ?>
    <?php $tabControl->End(); ?>

</form>
<style>
    select {
        min-width: 300px;
        width: 300px;
    }

    input[type="text"] {
        min-width: 288px;
        width: 288px;
    }

    input[name=MINDBOX_MODULE_VERSION] {
        pointer-events: none !important;
        background-color: #fff !important;
        border-color: #ccc !important;
        opacity: 0.4 !important;
    }

    .mindbox-help--icon:before {
        cursor: help;
        content: url("/bitrix/js/main/core/images/hint.gif");
        display: inline-block;
        margin-right: 10px;
        width: 5px;
        height: 5px;
    }

    .mindbox-help--tooltip {
        position: fixed;
        padding: 10px 20px;
        border: 1px solid #b3c9ce;
        border-radius: 4px;
        text-align: center;
        font: italic 14px/1.3 sans-serif;
        color: #333;
        background: #fff;
        max-width: 600px;
        box-shadow: 3px 3px 3px rgba(0, 0, 0, .3);
        display: none;
    }

    .mindbox-help--icon:hover ~ .mindbox-help--tooltip {
        display: block;
    }

    select option:checked {
        background-color: rgb(206, 206, 206);
    }
</style>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const settingForm = document.querySelector('form[name="minboxoptions"]');

        const nodeLabelSettingGroup = settingForm.querySelector('select[name="MINDBOX_CONTINUE_USER_GROUPS[]"]').closest("tr").firstElementChild;;
        createTooltip(nodeLabelSettingGroup, "<?= getMessage('CONTINUE_USER_GROUPS_TOOLTIP')?>");

        const nodeLabelLogLifeTime = settingForm.querySelector('input[name="MINDBOX_LOG_LIFE_TIME"]').closest("tr").firstElementChild;
        createTooltip(nodeLabelLogLifeTime, "<?= getMessage('LOG_LIFE_TIME_TOOLTIP')?>");
    });

    function createTooltip(label, message) {
        label.classList.add('mindbox-help');

        // Добавили иконку для тултипа
        iconNode = document.createElement('span');
        iconNode.className = 'mindbox-help--icon ';
        label.prepend(iconNode);

        iconNode.onmouseover = function (event) {
            const iconNode = event.target;
            const label = iconNode.parentNode;

            // Добавляем тултип
            tooltipElem = document.createElement('div');
            tooltipElem.className = 'mindbox-help--tooltip ';
            tooltipElem.innerHTML = message;
            label.append(tooltipElem);

            // спозиционируем его сверху от аннотируемого элемента (top-center)
            let coords = label.getBoundingClientRect();
            let left = coords.left + (label.offsetWidth - tooltipElem.offsetWidth) / 2;
            let top = coords.top - tooltipElem.offsetHeight - 5;

            if (left < 0) {
                left = 0; // не заезжать за левый край окна
            }

            if (top < 0) {
                // если подсказка не помещается сверху, то отображать её снизу
                top = coords.top + label.offsetHeight + 5;
            }

            tooltipElem.style.left = left + 'px';
            tooltipElem.style.top = top + 'px';
            tooltipElem.style.display = 'block';
        }

        iconNode.onmouseout = function (event) {
            const iconNode = event.target;
            const tooltipElem = iconNode.parentNode.querySelector('.mindbox-help--tooltip');
            tooltipElem.remove();
        }
    }
</script>