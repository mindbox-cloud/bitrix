<?php

if(IsModuleInstalled('mindbox.marketing'))
{
    if (is_dir(dirname(__FILE__).'/install/components'))
        $updater->CopyFiles("install/components", "components/");

    $objEventController = new \Mindbox\EventController();
    $objEventController->installEvents();
}