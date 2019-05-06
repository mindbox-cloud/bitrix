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
    <form action="" class="form-horizontal form-default" id="mindbox-subscribe-form">
        <div class="row form-group">
            <div class="col col-md-8">
                <div class="form-inline-elements">
                    <input type="email" class="form-control js-mask" name="mindbox-sub-email" id="mindbox-sub-email" data-type="email" required>

                    <button type="submit" class="btn btn-primary" style="flex-shrink: 0"><?=GetMessage('MB_SU_SUBSCRIBE')?></button>
                </div>
            </div>
        </div>
    </form>
    <div class="row">
        <div id="mindbox-message" class="col col-md-8 alert alert-info" style="display: none"></div>
    </div>
</div>
