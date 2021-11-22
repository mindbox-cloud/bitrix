<?php

if (IsModuleInstalled('mindbox.marketing')) {
    \CModule::IncludeModule('mindbox.marketing');

    if (is_dir(dirname(__FILE__).'/install/components')) {
        $updater->CopyFiles("install/components", "components/");
    }

    $objEventController = new \Mindbox\EventController();
    $objEventController->installEvents();
    $objEventController->revisionHandlers();

    if (!class_exists('\Mindbox\Installer\OrderPropertiesInstaller')) {
        require_once __DIR__ . '/lib/Installer/OrderPropertiesInstaller.php';
    }

    $objInstallerOrderProperty = new \Mindbox\Installer\OrderPropertiesInstaller();
    $objInstallerOrderProperty->install();
}
