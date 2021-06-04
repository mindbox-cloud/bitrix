<?
if (IsModuleInstalled('mindbox.marketing')) {

    $optionsEvent = \COption::GetOptionString('mindbox.marketing', 'ENABLE_EVENT_LIST', '');
    $objEventController = new \Mindbox\EventController();

    if (empty($optionsEvent)) {
        $objEventController->unInstallEvents();
        $objEventController->installEvents();
    }

    $objEventController->revisionHandlers();

    $objHlInstaller = new \Mindbox\Installer\CartRulesInstaller();
    $objHlInstaller->install();
}