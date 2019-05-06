<?php
/**
 * Created by @copyright QSOFT.
 */
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Page\Asset;

CJSCore::Init(['jquery', 'ajax']);
Asset::getInstance()->addJs("/bitrix/js/mindbox/jquery.inputmask.bundle.js");
Asset::getInstance()->addJs("/bitrix/js/mindbox/script.js");
Asset::getInstance()->addCss("/bitrix/css/mindbox/style.css");
?>

<div class="mindbox">
    <div class="row">
        <div id="mindbox-cart-error" class="col col-md-12 alert alert-danger" style="display: none"></div>
    </div>
    <div class="row">
        <div id="mindbox-cart-success" class="col col-md-12 alert alert-info" style="display: none"></div>
    </div>
    <form action="" class="form-horizontal form-default" id="mindbox-cart">
        <div class="form-group">
            <div class="col col-md-4">
                <label for="mindbox-cart-promo" class="control-label"><?=GetMessage('MB_CART_PROMOCODE')?></label>
            </div>

            <div class="col col-md-8">
                <div class="form-inline-elements">
                    <div class="form-field-with-reset js-clear-field">
                        <input type="text" class="form-control js-clear-field-input" name="mindbox-cart-promo" id="mindbox-cart-promo" value="<?=$_SESSION['PROMO_CODE']?>">

                        <span class="form-reset js-clear-field-btn" title="<?=GetMessage('MB_CART_CLEAR')?>" id="mindbox-clear-code"></span>
                    </div>

                    <button id="mindbox-promocode-submit" type="button" class="btn btn-primary"><?=GetMessage('MB_CART_APPLY_BUTTON')?></button>
                </div>
            </div>

            <div class="col col-md-8 col-md-offset-4">
                <?if($_SESSION['PROMO_CODE_AMOUNT']):?>
                    <div class="text">
                        <?=GetMessage('MB_CART_PROMO_DISCOUNT')?> - <span class="text-success"><?=$_SESSION['PROMO_CODE_AMOUNT']?></span>.
                    </div>
                <?endif;?>
            </div>
        </div>


        <?if($_SESSION['ORDER_AVAILABLE_BONUSES'] && $arParams['USE_BONUSES'] === 'Y'):?>
        <div class="form-group">
            <div class="col col-md-4">
                <label for="mindbox-cart-bonus" class="control-label"><?=GetMessage('MB_CART_AVAILABLE_BONUSES')?> <span id="mindbox-available_bonuses"><?=$_SESSION['ORDER_AVAILABLE_BONUSES']?></span> <?=GetMessage('MB_CART_BONUSES')?></label>
            </div>

            <div class="col col-md-8">
                <div class="form-inline-elements">
                    <div class="form-field-with-reset js-clear-field">
                        <input type="text" class="form-control js-mask js-clear-field-input" name="mindbox-cart-bonus" id="mindbox-cart-bonus" data-type="decimal"
                        value="<?=$_SESSION['PAY_BONUSES'] > 0 ? $_SESSION['PAY_BONUSES'] : ''?>">

                        <span class="form-reset js-clear-field-btn" title="<?=GetMessage('MB_CART_CLEAR')?>" id="mindbox-cart-clear-bonus"></span>
                    </div>
                    <button id="mindbox-pay-bonuses" type="button" class="btn btn-primary"><?=GetMessage('MB_CART_APPLY_BONUSES')?></button>
                </div>
            </div>
        </div>
        <?endif;?>
    </form>
</div>