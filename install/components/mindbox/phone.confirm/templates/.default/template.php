<?php
/**
 * Created by @copyright QSOFT.
 */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) {
    die();
}

use Bitrix\Main\Page\Asset;

CJSCore::Init(['jquery', 'ajax']);
Asset::getInstance()->addJs("/bitrix/js/mindbox/jquery.inputmask.bundle.js");
Asset::getInstance()->addJs("/bitrix/js/mindbox/script.js");
Asset::getInstance()->addCss("/bitrix/css/mindbox/style.css");
?>

<?php if ($USER->IsAuthorized()) : ?>
    <div class="mindbox">
        <?php if (empty($arResult['USER_INFO']['PERSONAL_PHONE'])) : ?>
            <div class="row">
                <span class="col col-md-8 alert alert-info"><?= GetMessage('MB_PC_INPUT_PHONE')?></span>
            </div>
        <?php elseif ($arResult['USER_INFO']['UF_PHONE_CONFIRMED']) : ?>
            <div class="row">
                <span class="col col-md-8 alert alert-info"><?= GetMessage('MB_PC_SUCCESS_CONFIRM', ['#PHONE#' => $arResult['USER_INFO']['PERSONAL_PHONE']])?></span>
            </div>
        <?php else : ?>
            <div class="row">
                <div id="mindbox-phone-confirm-error" class="col col-md-8 alert alert-danger" style="display: none"></div>
            </div>
            <div class="row">
                <div id="mindbox-phone-confirm-success" class="col col-md-8 alert alert-info" style="display: none"></div>
            </div>
            <div class="form-horizontal form-default" id="mindbox-phone-confirm">
                <div class="form-group">
                    <div class="col col-md-3">
                        <label for="mindbox-code" class="control-label"><?= GetMessage('MB_PC_CONFIRM_CODE')?></label>
                    </div>

                    <div class="col col-md-5">
                        <div class="form-inline-elements">
                            <input type="number" class="form-control js-mask" name="mindbox-code" id="mindbox-code" data-type="decimal">

                            <button type="button" class="btn btn-auth js-auth-code" id="mindbox-phone-confirm--resend">
                                <span class="js-auth-code-text"><?= GetMessage('MB_PC_RESEND_BUTTON')?></span>
                                <span class="js-auth-code-timer"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col col-md-5 col-md-offset-3">
                        <div class="form-inline-elements">
                            <button id="submit-button" class="btn btn-primary"><?= GetMessage('MB_PC_CHECK_CODE')?></button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>