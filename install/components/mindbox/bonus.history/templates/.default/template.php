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

<?if (!$USER->IsAuthorized())
{
    $APPLICATION->AuthForm("", false, false, 'N', false);
} else {
?>
    <?if($arResult['ERROR']) {
       echo '<span>' . $arResult['ERROR'] . '</span>';
    } else {
    ?>
<p>
    <?=GetMessage('MB_BH_AVAILABLE')?> <b><?=$arResult['BALANCE']['available'] ? : 0?></b> <?=GetMessage('MB_BH_BONUSES')?>.
</p>

<p>
    <?=GetMessage('MB_BH_BLOCKED')?> <b><?=$arResult['BALANCE']['blocked'] ? : 0?></b> <?=GetMessage('MB_BH_BONUSES')?>.
</p>
<table class="table table-bordered table-striped" id="mindbox-bonus-history">
    <tr>
        <th><?=GetMessage('MB_BH_AVAILABLE')?></th>
        <th><?=GetMessage('MB_BH_CHANGE')?></th>
        <th><?=GetMessage('MB_BH_REASON')?></th>
        <th><?=GetMessage('MB_BH_EXPIRE_DATE')?></th>
    </tr>
    <?foreach ($arResult['HISTORY'] as $change):?>
    <tr>
        <td><?=$change['start']?></td>
        <td><?=$change['size']?></td>
        <td><?=$change['name']?></td>
        <td><?=$change['end']?></td>
    </tr>
    <?endforeach;?>
</table>
<?if(count($arResult['HISTORY']) !== intval($arParams['PAGE_SIZE'])):?>
<div class="more" id="mindbox-bonus-more" data-page="1">
    <div class="btn btn-primary" id="mindbox-bonus-history--load-more"><?=GetMessage('MB_BH_MORE')?></div>
</div>
<?endif;?>
<?}
}?>