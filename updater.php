<?php

if (IsModuleInstalled('mindbox.marketing')) {
    \CModule::IncludeModule('mindbox.marketing');


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
}
