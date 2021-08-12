<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentParameters = [
    "PARAMETERS" => [
        "PERSONAL_PAGE_URL"        => [
            "PARENT"  => "BASE",
            "NAME"    => GetMessage('PERSONAL_PAGE_URL'),
            "TYPE"    => "STRING",
            "DEFAULT" => SITE_DIR . '/'
        ],
        "FILLUP_FORM_FIELDS" => [
            "PARENT"  => "BASE",
            "NAME"    => GetMessage('FILLUP_FORM_FIELDS'),
            "TYPE"    => "LIST",
            "MULTIPLE"  =>  "Y",
            "VALUES"  => [
                'NAME'         => GetMessage('NAME'),
                'LAST_NAME'    => GetMessage('LAST_NAME'),
                'EMAIL'        => GetMessage('EMAIL'),
                'MOBILE_PHONE' => GetMessage('MOBILE_PHONE'),
                'PASSWORD'     => GetMessage('PASSWORD'),
                'BIRTH_DATE'   => GetMessage('BIRTH_DATE'),
                'GENDER'       => GetMessage('GENDER')
            ],
            "DEFAULT" => [
                'NAME',
                'LAST_NAME',
                'EMAIL',
                'MOBILE_PHONE',
                'PASSWORD',
                'BIRTH_DATE',
                'GENDER'
            ]
        ]
    ]
];

