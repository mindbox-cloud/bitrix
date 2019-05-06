<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentParameters = [
    "PARAMETERS" => [
        "PERSONAL_PAGE_URL" => [
            "PARENT" => "BASE",
            "NAME" => GetMessage('PERSONAL_PAGE_URL'),
            "TYPE" => "STRING",
            "DEFAULT" => SITE_DIR . '/'
        ]
    ]
];

