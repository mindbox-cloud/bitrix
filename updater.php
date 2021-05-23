<?
if (IsModuleInstalled('mindbox.marketing')) {
    $obj = CModule::CreateModuleObject('mindbox.marketing');
    $curVersion = $obj->MODULE_VERSION;
    $allowUpdateVersion = '2.3.0';

    if (CheckVersion($curVersion, $allowUpdateVersion) === false) {
        echo '<p style="color: red">Ошибка при обновлении модуля. Необходима версия >= 2.3.0</p>';
        die();
    }
}

