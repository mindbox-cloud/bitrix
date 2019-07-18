<?php
/**
 * Created by @copyright QSOFT.
 */
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Mindbox\Helper;
use Bitrix\Main\Page\Asset;
CJSCore::Init(['jquery', 'ajax']);
Asset::getInstance()->addJs("/bitrix/js/mindbox/jquery.inputmask.bundle.js");
Asset::getInstance()->addJs("/bitrix/js/mindbox/script.js");
Asset::getInstance()->addCss("/bitrix/css/mindbox/style.css");
?>

<div class="mindbox">
    <? if (empty($arResult['ORDERS'])) {
        ShowMessage(GetMessage('MB_OH_ERROR_MESSAGE'));
    } ?>
        <div id='mindbox-orders-history'>
            <? foreach ($arResult['ORDERS'] as $order): ?>
                <?=GetMessage('MB_OH_ORDER_HEADER', ['#ID#' => $order['id'], '#CREATED#' => $order['created'] ])?>

                <p>
                    <? if ($order['spentBonuses']): ?>
                        <?=GetMessage('MB_OH_ORDER_SPENT', ['#SPENT#' => $order['spentBonuses'], '#END#' => Helper::getNumEnding($order['spentBonuses'],
                        GetMessage('MB_OH_ENDINGS_ARRAY'))]);?>
                    <? endif; ?>
                    <? if ($order['acuiredBonuses']): ?>
                         <?=GetMessage('MB_OH_ORDER_ACUIRED', ['#ACUIRED#' => $order['acuiredBonuses'],'#END#' => Helper::getNumEnding($order['acuiredBonuses'],
                        GetMessage('MB_OH_ENDINGS_ARRAY'))]);?>
                    <? endif; ?>
                </p>

                <table class="table table-bordered table-striped">
                    <tr>
                        <th><?=GetMessage('MB_OH_ITEM_NAME')?></th>
                        <th><?=GetMessage('MB_OH_ITEM_PRICE')?></th>
                    </tr>
                    <? foreach ($order['lines'] as $line): ?>
                        <?=GetMessage('MB_OH_ORDER_LINE', ['#LINK#' => $line['link'], '#NAME#' => $line['name'], '#PRICE#' => $line['price']]);?>
                    <? endforeach; ?>
                </table>
            <? endforeach; ?>
        </div>
    <? if (count($arResult['ORDERS']) === $arParams['PAGE_SIZE']): ?>
        <div class="more" id="mindbox-order-more" data-page="1">
            <div class="btn btn-primary" id="mindbox-order-history--load-more"><?=GetMessage('MB_OH_MORE')?></div>
        </div>
    <? endif; ?>
</div>