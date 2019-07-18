<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentParameters = [
    "PARAMETERS" => [
        "PAGE_SIZE" => [
            "PARENT" => "BASE",
            "NAME" => GetMessage('PAGE_SIZE'),
            "TYPE" => "STRING",
            "DEFAULT" => 5
        ]
    ]
];

