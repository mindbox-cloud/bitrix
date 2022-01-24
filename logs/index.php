<?php
define('NEED_AUTH', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
$mindboxFilename = __DIR__ . DIRECTORY_SEPARATOR . 'mindbox.log';
global $USER, $APPLICATION;
$arGroups = $USER->GetUserGroupArray();
if ($USER->IsAuthorized() && in_array(1, $arGroups)) {
    $APPLICATION->RestartBuffer();
    echo "<pre>".htmlspecialchars(file_get_contents($mindboxFilename)) . "</pre>";
    exit;
} else {
    ShowError("” вас нет прав дл€ доступа к данному разделу.");
}
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
