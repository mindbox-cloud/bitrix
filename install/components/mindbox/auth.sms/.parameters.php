<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentParameters = [
    "PARAMETERS" => [
        "USE_BONUSES" => [
            "PARENT" => "BASE",
            "NAME" => GetMessage('PERSONAL_PAGE_URL'),
            "TYPE" => "STRING",
            "DEFAULT" => SITE_DIR . '/'
        ]
    ]
];

