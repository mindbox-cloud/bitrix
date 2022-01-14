<?php

if (IsModuleInstalled('mindbox.marketing')) {
    \CModule::IncludeModule('mindbox.marketing');

    if (is_dir(dirname(__FILE__).'/install/components')) {
        $updater->CopyFiles("install/components", "components/");
    }

    $updater->CopyFiles("lib", "modules/mindbox.marketing/lib");

    try {
        $transactionTable = new \Mindbox\DataBase\MindboxTransactionTable();
        $transactionTable->createTable();
    } catch (\Exception $e) {

    }
}
