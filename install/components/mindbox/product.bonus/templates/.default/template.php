<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION;
?>
<div class="mindbox-product-bonus">
    <?if (isset($arParams['LABEL']) && !empty($arParams['LABEL'])):?>
        <span class="mindbox-product-bonus__label"><?=$arParams['LABEL']?></span>
    <?endif;?>
    <span class="mindbox-product-bonus__value"><?=$arResult['MINDBOX_BONUS']?></span>
</div>



