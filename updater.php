<?php

if (IsModuleInstalled('mindbox.marketing')) {
    \CModule::IncludeModule('mindbox.marketing');

    if (is_dir(dirname(__FILE__).'/install/components')) {
        $updater->CopyFiles("install/components", "components/");
    }

    $eventManager = \Bitrix\Main\EventManager::getInstance();
    $eventManager->registerEventHandler(
        'main',
        'OnBeforeProlog',
        'mindbox.marketing',
        '\Mindbox\Event',
        'OnBeforePrologHandler',
        1000
    );
}
