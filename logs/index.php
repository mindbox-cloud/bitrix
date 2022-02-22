<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
?>

<?php
if (\Bitrix\Main\Loader::includeModule('mindbox.marketing')) {
    $mindboxLog = new \Mindbox\AccessLogs();
    $mindboxLog->checkLogAccess($_GET['path']);
}
?>

<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';