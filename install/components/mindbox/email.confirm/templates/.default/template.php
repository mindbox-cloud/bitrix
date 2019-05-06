<?php
/**
 * Created by @copyright QSOFT.
 */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Page\Asset;

CJSCore::Init(['jquery', 'ajax']);
Asset::getInstance()->addJs("/bitrix/js/mindbox/jquery.inputmask.bundle.js");
Asset::getInstance()->addJs("/bitrix/js/mindbox/script.js");
Asset::getInstance()->addCss("/bitrix/css/mindbox/style.css");
?>

<? if ($USER->IsAuthorized()): ?>
    <div class="mindbox">
        <? if (empty($arResult['USER_INFO']['EMAIL'])): ?>
            <div class="row">
                <span class="col col-md-8 alert alert-info"><?= GetMessage('MB_EC_INPUT_EMAIL') ?></span>
            </div>
        <? elseif ($arResult['USER_INFO']['UF_EMAIL_CONFIRMED']): ?>
            <div class="row">
                <span class="col col-md-8 alert alert-info"><?= GetMessage('MB_EC_SUCCESS_CONFIRM', ['#EMAIL#' => $arResult['USER_INFO']['EMAIL']]) ?></span>
            </div>
        <? else: ?>
            <div class="row">
                <div id="mindbox-email-confirm-error" class="col col-md-8 alert alert-danger"
                     style="display: none"></div>
            </div>
            <div class="row">
                <div id="mindbox-email-confirm-success" class="col col-md-8 alert alert-info"
                     style="display: none"></div>
            </div>
            <div class="form-group">
		<div class="col col-md-5">
			<label for="mindbox-email-confirm--resend"><?=GetMessage('MB_EC_RESEND_LABEL')?></label>
		</div>
                <div class="col col-md-3">
                    <button type="button" class="js-auth-code btn btn-primary" id="mindbox-email-confirm--resend">
                        <span class="js-auth-code-text"><?= GetMessage('MB_EC_RESEND_BUTTON') ?></span> <span
                                class="js-auth-code-timer"></span>
                    </button>
                </div>
            </div>
        <? endif; ?>
    </div>
<? endif; ?>
