<?
if(IsModuleInstalled('mindbox.marketing'))
{
	if (is_dir(dirname(__FILE__).'/install/components'))
	{
		$updater->CopyFiles("install/components", "components/");
	}
	if (is_dir(dirname(__FILE__).'/install/js'))
	{
		$updater->CopyFiles("install/js", "js/mindbox.marketing/");
	}
	if (is_dir(dirname(__FILE__).'install/css/'))
	{
		$updater->CopyFiles("install/css", "css/mindbox.marketing/");
	}
	if (is_dir(dirname(__FILE__).'install/images/'))
        {
                $updater->CopyFiles("install/images", "images/mindbox.marketing/");
        }
}
