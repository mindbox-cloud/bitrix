<?php
if (!check_bitrix_sessid()) {
    return;
}

IncludeModuleLangFile(__FILE__);

if ($ex = $APPLICATION->GetException()) {
    echo CAdminMessage::ShowMessage(Array(
        "TYPE" => "ERROR",
        "MESSAGE" => GetMessage("MOD_INST_ERR"),
        "DETAILS" => $ex->GetString(),
        "HTML" => true,
    ));
} else {
    echo CAdminMessage::ShowNote(GetMessage("MOD_INST_OK"));
    echo CAdminMessage::ShowMessage(array(
        "TYPE" => "WARNING",
        "MESSAGE" => GetMessage("SETTINGS_REMINDER"),
        "DETAILS" => '',
        "HTML" => true,
    ));
}
?>
<div style="font-size: 12px;"></div>
<br>
<form action="<? echo $APPLICATION->GetCurPage() ?>">
    <input type="hidden" name="lang" value="<?= LANG ?>">
    <input type="submit" name="" value="<?= GetMessage('MOD_BACK') ?>">
    <a href="/bitrix/admin/settings.php?lang=ru&mid=mindbox.marketing">
        <input type="button" value="<?= GetMessage('GOTO_SETTINGS') ?>">
    </a>
</form>