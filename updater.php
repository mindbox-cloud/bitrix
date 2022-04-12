<?php

if (IsModuleInstalled('mindbox.marketing')) {
    \CModule::IncludeModule('mindbox.marketing');

    if (is_dir(dirname(__FILE__).'/install/components')) {
        $updater->CopyFiles("install/components", "components/");
    }

    $updater->CopyFiles("lib", "modules/mindbox.marketing/lib");
    $updater->CopyFiles("logs", "modules/mindbox.marketing/logs");

    $eventController = new \Mindbox\EventController();
    $eventController->unRegisterEventHandler([
        'bitrixModule' => 'main',
        'bitrixEvent' => 'OnBeforeUserRegister',
        'class' => '\Mindbox\Event',
        'method' => 'OnBeforeUserRegisterHandler',
    ]);
    $eventController->unRegisterEventHandler([
        'bitrixModule' => 'main',
        'bitrixEvent' => 'OnAfterUserRegister',
        'class' => '\Mindbox\Event',
        'method' => 'OnAfterUserRegisterHandler',
    ]);

    $mindboxLog = new \Mindbox\AccessLogs();
    $mindboxLog->setLogAccess();
}
