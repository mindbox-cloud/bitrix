<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION;
//CJSCore::Init(['fx', 'ajax']);
?>
<div class="mindbox-product-price">
    <span class="mindbox-product-price__discount"><?=$arResult['MINDBOX_OLD_PRICE']?></span>
    <span class="mindbox-product-price__price"><?=$arResult['MINDBOX_PRICE']?></span>
    <?if (isset($arParams['CURRENCY']) && !empty($arParams['CURRENCY'])):?>
        <?=$arParams['CURRENCY']?>
    <?endif;?>
</div>
