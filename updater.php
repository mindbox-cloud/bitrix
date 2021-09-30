<?
if (IsModuleInstalled('mindbox.marketing')) {
    if (is_dir(dirname(__FILE__).'/install/components'))
        $updater->CopyFiles("install/components", "components/");

    if (is_dir(dirname(__FILE__).'/install/js'))
        $updater->CopyFiles("install/js", "js/mindbox.marketing/");
}