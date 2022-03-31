<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) {
    die();
}
global $APPLICATION;
//CJSCore::Init(['fx', 'ajax']);
?>
<div class="mindbox-product-price">
    <?=$arResult['MINDBOX_OLD_PRICE']?>
    <span></span>
    <?=$arResult['MINDBOX_PRICE']?>
</div>
