<?php
if (!check_bitrix_sessid()) {
    return;
}

if ($ex = $APPLICATION->GetException())
    echo CAdminMessage::ShowMessage(array(
        "TYPE" => "ERROR",
        "MESSAGE" => GetMessage("MOD_UNINST_ERR"),
        "DETAILS" => $ex->GetString(),
        "HTML" => true,
    ));
else
    echo CAdminMessage::ShowNote(GetMessage("MOD_UNINST_OK"));
?>

<form action="<?=$APPLICATION->GetCurPage(); ?>">
	<input type="hidden" name="lang" value="<?=LANGUAGE_ID?>">
	<input type="submit" name="" value="<?=GetMessage("MOD_BACK"); ?>">
<form>