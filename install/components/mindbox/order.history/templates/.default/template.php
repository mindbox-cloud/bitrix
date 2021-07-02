<?php
/**
 * Created by @copyright QSOFT.
 */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) {
    die();
}

use Mindbox\Helper;
use Bitrix\Main\Page\Asset;

CJSCore::Init(['jquery', 'ajax']);
Asset::getInstance()->addJs("/bitrix/js/mindbox/jquery.inputmask.bundle.js");
Asset::getInstance()->addJs("/bitrix/js/mindbox/script.js");
Asset::getInstance()->addCss("/bitrix/css/mindbox/style.css");
?>

<div class="mindbox">
    <?php if (empty($arResult['ORDERS'])) {
        ?><span><?=GetMessage('MB_OH_EMPTY_MESSAGE')?></span>
        <?php
    } ?>
        <div id='mindbox-orders-history'>
            <?php foreach ($arResult['ORDERS'] as $order) : ?>
                <?=GetMessage('MB_OH_ORDER_HEADER', ['#ID#' => $order['id'], '#CREATED#' => $order['created'] ])?>

                <p>
                    <?php if ($order['spentBonuses']) : ?>
                        <?=GetMessage('MB_OH_ORDER_SPENT', ['#SPENT#' => $order['spentBonuses'], '#END#' => Helper::getNumEnding(
                            $order['spentBonuses'],
                            GetMessage('MB_OH_ENDINGS_ARRAY')
                        )]);?>
                    <?php endif; ?>
                    <?php if ($order['acuiredBonuses']) : ?>
                         <?=GetMessage('MB_OH_ORDER_ACUIRED', ['#ACUIRED#' => $order['acuiredBonuses'],'#END#' => Helper::getNumEnding(
                             $order['acuiredBonuses'],
                             GetMessage('MB_OH_ENDINGS_ARRAY')
                         )]);?>
                    <?php endif; ?>
                </p>

                <table class="table table-bordered table-striped">
                    <tr>
                        <th><?=GetMessage('MB_OH_ITEM_NAME')?></th>
                        <th><?=GetMessage('MB_OH_ITEM_PRICE')?></th>
                    </tr>
                    <?php foreach ($order['lines'] as $line) : ?>
                        <?=GetMessage('MB_OH_ORDER_LINE', ['#LINK#' => $line['link'], '#NAME#' => $line['name'], '#PRICE#' => $line['price']]);?>
                    <?php endforeach; ?>
                </table>
            <?php endforeach; ?>
        </div>
    <?php if (count($arResult['ORDERS']) === $arParams['PAGE_SIZE']) : ?>
        <div class="more" id="mindbox-order-more" data-page="1">
            <div class="btn btn-primary" id="mindbox-order-history--load-more"><?=GetMessage('MB_OH_MORE')?></div>
        </div>
    <?php endif; ?>
</div>