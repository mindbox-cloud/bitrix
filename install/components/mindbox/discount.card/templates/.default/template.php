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

<? if (!$USER->IsAuthorized()) {
    $APPLICATION->AuthForm("", false, false, 'N', false);
} else {
    ?>
    <div class="mindbox">
        <div class="row">
            <div id="mindbox-card-error" class="col col-md-8 alert alert-danger" style="display: none"></div>
        </div>
        <div class="row">
            <div id="mindbox-card-success" class="col col-md-8 alert alert-info" style="display: none"></div>
        </div>
        <form action="" class="form-horizontal form-default" id="mindbox-card-input">
            <div class="form-group">
                <div class="col col-md-3">
                    <label for="mindbox-card" class="control-label"><?=GetMessage('MB_DC_CARD_NUMBER')?></label>
                </div>

                <div class="col col-md-5">
                    <input type="text" class="form-control js-mask" name="mindbox-card" id="mindbox-card" data-type="decimal" required>
                </div>
            </div>

            <div class="form-group">
                <div class="col col-md-8">
                    <button type="submit" class="btn btn-primary"><?=GetMessage('MB_DC_BIND_BUTTON')?></button>
                </div>
            </div>
        </form>
        <form action="" class="form-horizontal form-default" id="mindbox-code-confirm" style="display: none">
            <div class="form-group">
                <div class="col col-md-9">
                    <label><?=GetMessage('MB_DC_SUCCESS_MESSAGE')?></label>
                </div>
            </div>

            <div class="form-group">
                <p id="mindbox-code-error" class="form-error"></p>
                <div class="col col-md-3">
                    <p class="control-label"><?=GetMessage('MB_DC_AUTH_CODE')?></p>
                </div>

                <div class="col col-md-5">
                    <div class="form-inline-elements">
                        <input type="number" class="form-control js-mask" name="mindbox-code" id="mindbox-code" data-type="decimal"
                               required>

                        <button type="button" class="btn btn-auth js-auth-code" data-last="false">
                            <span class="js-auth-code-text" id="mindbox-code-resend"><?=GetMessage('MB_DC_RESEND_BUTTON')?></span> <span
                                    class="js-auth-code-timer"></span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="col col-md-5 col-md-offset-3">
                    <div class="form-inline-elements">
                        <button type="submit" class="btn btn-primary"><?=GetMessage('MB_DC_CHECK_CODE')?></button>

                        <button type="button" class="btn" id="mindbox-cancel-button"><?=GetMessage('MB_DC_CANCEL_BUTTON')?></button>
                    </div>
                </div>
            </div>
        </form>
    </div>
<? } ?>