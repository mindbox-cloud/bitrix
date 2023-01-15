<?php
/**
 * Created by @copyright QSOFT.
 */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) {
    die();
}

use Bitrix\Main\Page\Asset;

global $USER;

CJSCore::Init(['jquery', 'ajax']);
Asset::getInstance()->addJs("/bitrix/js/mindbox/jquery.inputmask.bundle.js");
Asset::getInstance()->addJs("/bitrix/js/mindbox/script.js");
Asset::getInstance()->addCss("/bitrix/css/mindbox/style.css");
?>
<?php if ($USER->isAuthorized()) :?>
    <?php $url = $arParams['PERSONAL_PAGE_URL'] ? $arParams["PERSONAL_PAGE_URL"] :  '/';?>
    <?php LocalRedirect($url);?>
<?php else :?>
<div class="mindbox">
    <div class="row">
        <div id="mindbox-auth-sms-error" class="col col-md-12 alert alert-danger" style="display: none"></div>
    </div>
    <div class="row">
        <div id="mindbox-auth-sms-success" class="col col-md-12 alert alert-info" style="display: none"></div>
    </div>
    <form id="mindbox-input-phone" action="" class="form form-default">
        <div class="row form-group">
            <div class="col col-md-12">
                <label for="mindbox-num"><?=GetMessage('MB_AUS_INPUT_PHONE')?></label>

                    <div>
                        <input type="text" class="form-control js-mask" name="mindbox-num" id="mindbox-num"
                               data-type="phone">
                    </div>
                </div>
            </div>

            <div class="row form-group">
                <div class="col col-md-12">
                    <button type="submit" class="btn btn-primary"><?= GetMessage('MB_AUS_NEXT_BUTTON') ?></button>
                </div>
            </div>
        </form>

        <form action="" id="mindbox-input-code" class="form-horizontal form-default" style="display: none">
            <div class="form-group">
                <div class="col col-md-4">
                    <label><?= GetMessage('MB_AUS_MOBILE_PHONE') ?></label>
                </div>

                <div id="mindbox-auth-sms--mobile-phone" class="col col-md-8">
                </div>
            </div>

            <div class="form-group">
                <div class="col col-md-4">
                    <label for="mindbox-code" class="control-label"><?= GetMessage('MB_AUS_AUTH_CODE') ?></label>
                </div>

                <div class="col col-md-8">
                    <div class="form-inline-elements">
                        <input type="number" class="form-control js-mask" name="mindbox-code" id="mindbox-code"
                               data-type="decimal">

                        <button type="button" class="btn btn-auth js-auth-code" id="mindbox-submit-code--resend">
                            <span class="js-auth-code-text"><?= GetMessage('MB_AUS_RESEND_BUTTON') ?></span> <span
                                    class="js-auth-code-timer"></span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="col col-md-8 col-md-offset-3">
                    <div class="form-inline-elements">
                        <button type="submit" class="btn btn-primary"><?= GetMessage('MB_AUS_CHECK_CODE') ?></button>

                        <button type="button" class="btn"
                                id="mindbox-submit-code--reset"><?= GetMessage('MB_AUS_RESET_BUTTON') ?></button>
                    </div>
                </div>
            </div>
        </form>

        <?php if (!empty($arParams['FILLUP_FORM_FIELDS']) && is_array($arParams['FILLUP_FORM_FIELDS'])):?>
        <form id="mindbox-fillup-profile" action="" class="form-horizontal form-default" style="display: none">

            <?php if (in_array('NAME', $arParams['FILLUP_FORM_FIELDS'])) : ?>
                <div class="form-group">
                    <div class="col col-md-4">
                        <label for="mindbox-fillup-name"
                               class="control-label"><?= GetMessage('MB_AUS_FIRST_NAME') ?></label>
                    </div>

                    <div class="col col-md-8">
                        <input type="text" class="form-control" name="mindbox-fillup-name" id="mindbox-fillup-name">
                    </div>

                    <div class="col col-md-offset-4 col-md-8">
                        <div id="mindbox-fillup-name-error" class="text text-danger"></div>
                    </div>
                </div>
            <?php endif; ?>

    <?php if (in_array('LAST_NAME', $arParams['FILLUP_FORM_FIELDS'])) : ?>
            <div class="form-group">
                <div class="col col-md-4">
                    <label for="mindbox-fillup-last-name"
                           class="control-label"><?= GetMessage('MB_AUS_LAST_NAME') ?></label>
                </div>

                <div class="col col-md-8">
                    <input type="text" class="form-control" name="mindbox-fillup-last-name"
                           id="mindbox-fillup-last-name">
                </div>

                <div class="col col-md-offset-4 col-md-8">
                    <div id="mindbox-fillup-last-name-error" class="text text-danger"></div>
                </div>
            </div>
    <?php endif; ?>

    <?php if (in_array('EMAIL', $arParams['FILLUP_FORM_FIELDS'])) : ?>
            <div class="form-group">
                <div class="col col-md-4">
                    <label for="mindbox-fillup-email" class="control-label">E-mail</label>
                </div>

                <div class="col col-md-8">
                    <input type="hidden" id="mindbox-fillup-email-original" name="mindbox-fillup-email-original" value="">
                    <input type="text" class="form-control js-mask" name="mindbox-fillup-email"
                           id="mindbox-fillup-email" data-type="email" required>
                </div>

                <div class="col col-md-offset-4 col-md-8">
                    <div id="mindbox-fillup-email-error" class="text text-danger"></div>
                </div>
            </div>
    <?php endif; ?>

    <?php if (in_array('MOBILE_PHONE', $arParams['FILLUP_FORM_FIELDS'])) : ?>
            <div class="form-group">
                <div class="col col-md-4">
                    <label for="text" class="control-label"><?= GetMessage('MB_AUS_MOBILE_PHONE') ?></label>
                </div>

                <div class="col col-md-8">
                    <input type="text" class="form-control js-mask" name="mindbox-fillup-phone"
                           id="mindbox-fillup-phone" data-type="phone">
                </div>

                <div class="col col-md-offset-4 col-md-8">
                    <div id="mindbox-fillup-phone-error" class="text text-danger"></div>
                </div>
            </div>
    <?php endif; ?>

    <?php if (in_array('PASSWORD', $arParams['FILLUP_FORM_FIELDS'])) : ?>
            <div class="form-group">
                <div class="col col-md-4">
                    <label for="mindbox-fillup-password"
                           class="control-label"><?= GetMessage('MB_AUS_PASSWORD') ?></label>
                </div>

                <div class="col col-md-8">
                    <input type="password" class="form-control" name="mindbox-fillup-password"
                           id="mindbox-fillup-password" required>
                </div>
            </div>
    <?php endif; ?>

    <?php if (in_array('BIRTH_DATE', $arParams['FILLUP_FORM_FIELDS'])) : ?>
            <div class="form-group">
                <div class="col col-md-4">
                    <label for="mindbox-fillup-date"
                           class="control-label"><?= GetMessage('MB_AUS_BIRTH_DATE') ?></label>
                </div>

                <div class="col col-md-8">
                    <input type="text" class="form-control js-mask" name="mindbox-fillup-date" id="mindbox-fillup-date"
                           data-type="date">
                </div>

                <div class="col col-md-offset-4 col-md-8">
                    <div id="mindbox-fillup-birth-date-error" class="text text-danger"></div>
                </div>
            </div>
    <?php endif; ?>

    <?php if (in_array('GENDER', $arParams['FILLUP_FORM_FIELDS'])) : ?>
            <div class="form-group">
                <div class="col col-md-4">
                    <label class="control-label"><?= GetMessage('MB_AUS_GENDER') ?></label>
                </div>

                <div class="col col-md-8">
                    <div class="radio">
                        <label>
                            <input type="radio" name="mindbox-fillup-gender" id="mindbox-gender_1" value="male" checked>
                            <?= GetMessage('MB_AUS_MALE') ?>
                        </label>
                    </div>
                    <div class="radio">
                        <label>
                            <input type="radio" name="mindbox-fillup-gender" id="mindbox-gender_2" value="female">
                            <?= GetMessage('MB_AUS_FEMALE') ?>
                        </label>
                    </div>
                </div>
            </div>
    <?php endif; ?>

        <?php if ($arResult["USE_CAPTCHA"] == "Y") : ?>
            <div class="form-group">
                <div class="col col-md-4">
                    <label for="mindbox--captcha_sid" class="control-label"><?=GetMessage('MB_AUS_CAPTCHA_TITLE')?></label>
                </div>

                    <div class="col col-md-8">
                        <input type="hidden" name="captcha_sid" id="mindbox--captcha_sid"
                               value="<?= $arResult["CAPTCHA_CODE"] ?>"/>
                        <img src="/bitrix/tools/captcha.php?captcha_sid=<?= $arResult["CAPTCHA_CODE"] ?>"
                             id="mindbox--captcha_img" width="180" height="40" alt="CAPTCHA"/>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col col-md-4">
                        <label for="mindbox--captcha_word"
                               class="control-label"><?= GetMessage('MB_AUS_CAPTCHA_PROMPT') ?></label>
                    </div>

                    <div class="col col-md-8">
                        <input type="text" name="captcha_word" id="mindbox--captcha_word" value=""/>
                    </div>
                </div>
        <?php endif; ?>

            <div class="form-group">
                <div class="col col-md-12">
                    <button type="submit" class="btn btn-primary"><?= GetMessage('MB_AUS_FILLUP_SUBMIT') ?></button>
                </div>
            </div>
        </form>
        <?php endif;?>
    </div>
<?php endif ?>