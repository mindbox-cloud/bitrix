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

<? if ($USER->IsAuthorized()): ?>
    <div class="mindbox">
        <form action="" class="form-horizontal form-default" id="mindbox-sub-edit-form">
            <div class="form-group">
                <div class="col col-md-4">
                    <label class="control-label"><?=GetMessage('MB_SE_SUBSCRIBES')?></label>
                </div>

                <div class="col col-md-8">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="minfbox-sub_email"
                                   id="mindbox-sub_email" <? if ($arResult['SUBSCRIPTIONS']['Email']) echo 'checked = "true"' ?>>
                            <?=GetMessage('MB_SE_EMAIL')?>
                        </label>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="mindbox-sub_sms"
                                   id="mindbox-sub_sms" <? if ($arResult['SUBSCRIPTIONS']['Sms']) echo 'checked = "true"' ?>>
                            <?=GetMessage('MB_SE_SMS')?>
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="col col-md-12">
                    <button type="submit" class="btn btn-primary"><?=GetMessage('MB_SE_SAVE')?></button>
                </div>
            </div>
        </form>
        <div class="row">
            <div id="mindbox-message" class="col col-md-8 alert alert-info" style="display: none"></div>
        </div>
    </div>
<? endif; ?>