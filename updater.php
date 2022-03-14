<?php

if (IsModuleInstalled('mindbox.marketing')) {
    \CModule::IncludeModule('mindbox.marketing');

    if (is_dir(dirname(__FILE__).'/install/components')) {
        $updater->CopyFiles("install/components", "components/");
    }

    $updater->CopyFiles("lib", "modules/mindbox.marketing/lib");

    try {
        (new \Mindbox\EventController())->installDeliveryRulesHandler();
    } catch (\Exception $e) {
    }

    try {
        (new \Mindbox\Installer\DeliveryCartRuleInstaller())->install();
    } catch (\Exception $e) {
    }
}
