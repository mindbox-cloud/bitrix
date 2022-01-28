<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION;
if (!empty($arResult['INTEGRATION_KEY'])) {
    $APPLICATION->ShowViewContent($arResult['INTEGRATION_KEY']);
}
