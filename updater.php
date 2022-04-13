<?php

if (IsModuleInstalled('mindbox.marketing')) {
    \CModule::IncludeModule('mindbox.marketing');

    if (is_dir(dirname(__FILE__).'/install/components')) {
        $updater->CopyFiles("install/components", "components/");
    }

    $updater->CopyFiles("lib", "modules/mindbox.marketing/lib");
    $updater->CopyFiles("logs", "modules/mindbox.marketing/logs");

    $moduleName = 'mindbox.marketing';

    CAgent::RemoveModuleAgents($moduleName);
    $now = new DateTime();
    CAgent::AddAgent(
        "\Mindbox\YmlFeedMindbox::start();",
        $moduleName,
        "N",
        86400,
        $now,
        "Y",
        $now,
        30
    );

    CAgent::AddAgent(
        "\Mindbox\QueueTable::start();",
        $moduleName,
        "N",
        60,
        $now,
        "Y",
        $now,
        30
    );

    $tomorrow = DateTime::createFromTimestamp(strtotime('tomorrow'));
    $tomorrow->setTime(3,0);

    CAgent::AddAgent(
        "\Mindbox\LogsRotation::agentRotationLogs();",
        $moduleName,
        "N",
        86400,
        $tomorrow,
        "Y",
        $tomorrow,
        30
    );
}
