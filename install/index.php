<?

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Entity\Base;
use \Bitrix\Main\Type\DateTime;

IncludeModuleLangFile(__FILE__);

class mindbox_marketing extends CModule {
	var $MODULE_ID = "mindbox.marketing";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $PATH;

	var $PATH_INST = "/";

	private $userFields = [
		"UF_MINDBOX_ID",
		"UF_PHONE_CONFIRMED",
		"UF_EMAIL_CONFIRMED",
		"UF_IS_SUBSCRIBED"
	];

	function mindbox_marketing()
	{
		$arModuleVersion = [];

		include($this->getPath() . "/version.php");

		$this->PARTNER_NAME = "Mindbox";
		$this->PARTNER_URI = "https://mindbox.ru/";

		if (is_array($arModuleVersion) && isset($arModuleVersion["VERSION"])) {
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
			$this->MODULE_NAME = $arModuleVersion["MODULE_NAME"];
			$this->MODULE_DESCRIPTION = $arModuleVersion["MODULE_DESCRIPTION"];
		} else {
			$this->MODULE_VERSION = "0.0.1";
			$this->MODULE_VERSION_DATE = "1970-01-01 00:00:00";
			$this->MODULE_NAME = "mindbox";
			$this->MODULE_DESCRIPTION = "ModuleDescription";
		}
	}

	/**
	 * Устанавливаем модуль
	 */
	function DoInstall()
	{
		$this->InstallEvents();

		$this->InstallFiles();

		RegisterModule($this->MODULE_ID);

		$this->InstallAgents();
		$this->InstallDB();
		$this->InstallUserFields();

		$GLOBALS["APPLICATION"]->IncludeAdminFile(GetMessage("MINDBOX_INSTALL_TITLE"), __DIR__ . "/step1.php");
	}

	/**
	 * Удаляем модуль
	 */
	function DoUninstall()
	{
		global $APPLICATION;

		$context = Application::getInstance()->getContext();
		$request = $context->getRequest();

		if ($request["step"] < 2) {
			$APPLICATION->IncludeAdminFile(GetMessage("MINDBOX_UNINSTALL_TITLE"), __DIR__ . "/unstep1.php");
		} elseif ($request["step"] == 2) {
			$this->UnInstallEvents();
			$this->UnInstallFiles();

			$this->UnInstallAgents();

			if ($request["savedata"] != "Y") {
				$this->UnInstallUserFields();
				$this->UnInstallDB();
			}

			UnRegisterModule($this->MODULE_ID);
			$APPLICATION->IncludeAdminFile(GetMessage("MINDBOX_UNINSTALL_TITLE"), __DIR__ . "/unstep2.php");
		} else {
			var_dump($request['step']); die();
		}
	}

	function InstallAgents()
	{
		$now = new DateTime();
		CAgent::AddAgent(
			"\Mindbox\YmlFeedMindbox::start(1);",
			$this->MODULE_ID,
			"N",
			86400,
			$now,
			"Y",
			$now,
			30);

		CAgent::AddAgent(
			"\Mindbox\QueueTable::start();",
			$this->MODULE_ID,
			"N",
			15 * 60,
			$now,
			"Y",
			$now,
			30);

		return true;
	}

	function UnInstallAgents()
	{
		$agents = CAgent::GetList(['ID' => 'DESC'], ['NAME' => '\Mindbox\YmlFeedMindbox::start(%']);

		$existingAgents = [];

		while ($agent = $agents->Fetch()) {
			$existingAgents[] = $agent['NAME'];
		}

		foreach ($existingAgents as $agent) {
			CAgent::RemoveAgent($agent, $this->MODULE_ID);
		}

		CAgent::RemoveAgent(
			"\Mindbox\QueueTable::start();",
			$this->MODULE_ID
		);
	}

	function InstallDB()
	{
		Loader::includeModule($this->MODULE_ID);

		if (!Application::getConnection(\Mindbox\QueueTable::getConnectionName())->isTableExists(
			Base::getInstance("\Mindbox\QueueTable")->getDBTableName()
		)) {
			Base::getInstance("\Mindbox\QueueTable")->createDbTable();
		}

		COption::SetOptionString('mindbox.marketing', 'PROTOCOL', (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] != 'off') ? 'Y' : 'N');
		COption::SetOptionString('main', 'new_user_email_uniq_check', 'Y');
	}

	function UnInstallDB()
	{
		Loader::includeModule($this->MODULE_ID);

		Application::getConnection(\Mindbox\QueueTable::getConnectionName())->
		queryExecute("drop table if exists " . Base::getInstance("\Mindbox\QueueTable")->getDBTableName());

		Option::delete($this->MODULE_ID);
	}

	/**
	 * Добавляем события
	 * @return bool
	 */
	function InstallEvents()
	{
		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->registerEventHandlerCompatible("main", "OnAfterUserAuthorize", $this->MODULE_ID,
			"\Mindbox\Event", "OnAfterUserAuthorizeHandler", 1000);
		$eventManager->registerEventHandlerCompatible("main", "OnBeforeUserRegister", $this->MODULE_ID,
			"\Mindbox\Event", "OnBeforeUserRegisterHandler", 1000);
		$eventManager->registerEventHandlerCompatible("main", "OnAfterUserRegister", $this->MODULE_ID,
			"\Mindbox\Event", "OnAfterUserRegisterHandler", 1000);
		$eventManager->registerEventHandlerCompatible("main", "OnBeforeUserUpdate", $this->MODULE_ID,
			"\Mindbox\Event", "OnBeforeUserUpdateHandler", 1000);
		$eventManager->registerEventHandlerCompatible("main", "OnBeforeUserAdd", $this->MODULE_ID,
		"\Mindbox\Event", "OnBeforeUserAddHandler", 1000);
		$eventManager->registerEventHandlerCompatible("main", "OnAfterUserAdd", $this->MODULE_ID,
			"\Mindbox\Event", "OnAfterUserAddHandler", 1000);
		$eventManager->registerEventHandlerCompatible("sale", "OnSaleBasketBeforeSaved", $this->MODULE_ID,
			"\Mindbox\Event",
			"OnSaleBasketBeforeSavedHadler", 1000);
		$eventManager->registerEventHandlerCompatible("sale", "OnSaleBasketItemRefreshData", $this->MODULE_ID,
			"\Mindbox\Event",
			"OnSaleBasketItemRefreshDataHandler", 1000);
		$eventManager->registerEventHandlerCompatible("sale", "OnSaleOrderBeforeSaved", $this->MODULE_ID,
			"\Mindbox\Event", "OnSaleOrderBeforeSavedHandler", 1000);
		$eventManager->registerEventHandlerCompatible("sale", "OnSaleOrderSaved", $this->MODULE_ID,
			"\Mindbox\Event", "OnSaleOrderSavedHandler", 1000);

		return true;
	}

	/**
	 * Удаляем события
	 * @return bool
	 */
	function UnInstallEvents()
	{
		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->unRegisterEventHandler("main", "OnAfterUserAuthorize", $this->MODULE_ID);
		$eventManager->unRegisterEventHandler("main", "OnAfterUserRegister", $this->MODULE_ID);
		$eventManager->unRegisterEventHandler("main", "OnBeforeUserRegister", $this->MODULE_ID);
		$eventManager->unRegisterEventHandler("main", "OnBeforeUserUpdate", $this->MODULE_ID);
		$eventManager->unRegisterEventHandler("main", "OnBeforeUserAdd", $this->MODULE_ID);
		$eventManager->unRegisterEventHandler("main", "OnAfterUserAdd", $this->MODULE_ID);
		$eventManager->unRegisterEventHandler("sale", "OnSaleBasketBeforeSaved", $this->MODULE_ID);
		$eventManager->unRegisterEventHandler("sale", "OnSaleBasketSaved", $this->MODULE_ID);
		$eventManager->unRegisterEventHandler("sale", "OnBasketAdd", $this->MODULE_ID);
		$eventManager->unRegisterEventHandler("sale", "OnBasketDelete", $this->MODULE_ID);
		$eventManager->unRegisterEventHandler("sale", "OnBasketUpdate", $this->MODULE_ID);

		return true;
	}


	function InstallFiles()
	{
		mkdir($_SERVER["DOCUMENT_ROOT"] . "/bitrix/js/mindbox");
		mkdir($_SERVER["DOCUMENT_ROOT"] . "/bitrix/css/mindbox");
		mkdir($_SERVER["DOCUMENT_ROOT"] . "/bitrix/images/mindbox");

		if (!CopyDirFiles(__DIR__ . "/components", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components", true, true)) {
			return false;
		}

		if (!CopyDirFiles(__DIR__ . "/js", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/js/mindbox", true,
			true)) {
			return false;
		}

		if (!CopyDirFiles(__DIR__ . "/css", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/css/mindbox", true,
			true)) {
			return false;
		}

		if (!CopyDirFiles(__DIR__ . "/images", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/images/mindbox",
			true,
			true)) {
			return false;
		}

		return true;
	}

	function UnInstallFiles()
	{
		if (!DeleteDirFilesEx("/bitrix/components/mindbox")) {
			return false;
		}

		if (!DeleteDirFilesEx("/bitrix/js/mindbox")) {
			return false;
		}
		if (!DeleteDirFilesEx("/bitrix/css/mindbox")) {
			return false;
		}
		if (!DeleteDirFilesEx("/bitrix/images/mindbox")) {
			return false;
		}

		return true;
	}

	function InstallUserFields()
	{
		$existFields = [];
		$oUserTypeEntity = new CUserTypeEntity();
		$dbUserFields = $oUserTypeEntity::GetList([], ["ENTITY_ID" => "USER", "FIELD_NAME" => $this->userFields]);

		while ($userField = $dbUserFields->Fetch()) {
			$existFields[$userField['FIELD_NAME']] = $userField['ID'];
		}

		$oUserTypeEntity = new CUserTypeEntity();
		$aUserFields = [
			"ENTITY_ID" => "USER",
			"FIELD_NAME" => "UF_MINDBOX_ID",
			"USER_TYPE_ID" => "string",
			"XML_ID" => "MINDBOX_ID",
			"SORT" => 500,
			"MULTIPLE" => "N",
			"MANDATORY" => "N",
			"SHOW_FILTER" => "N",
			"SHOW_IN_LIST" => "",
			"EDIT_IN_LIST" => "",
			"IS_SEARCHABLE" => "N",
			"SETTINGS" => [
				"DEFAULT_VALUE" => "",
				"SIZE" => "20",
				"ROWS" => "1",
				"MIN_LENGTH" => "0",
				"MAX_LENGTH" => "0",
				"REGEXP" => "",
			],
			"EDIT_FORM_LABEL" => [
				"ru" => "Mindbox ID",
				"en" => "Mindbox ID",
			],
			"LIST_COLUMN_LABEL" => [
				"ru" => "Mindbox ID",
				"en" => "Mindbox ID",
			],
			"LIST_FILTER_LABEL" => [
				"ru" => "Mindbox ID",
				"en" => "Mindbox ID",
			],
		];

		if (empty($existFields[$aUserFields['FIELD_NAME']]) && !$oUserTypeEntity->Add($aUserFields)) {
			return false;
		}

		$aUserFields = [
			"ENTITY_ID" => "USER",
			"FIELD_NAME" => "UF_IS_SUBSCRIBED",
			"USER_TYPE_ID" => "boolean",
			"XML_ID" => "IS_SUBSCRIBED",
			"SORT" => 500,
			"MULTIPLE" => "N",
			"MANDATORY" => "N",
			"SHOW_FILTER" => "N",
			"SHOW_IN_LIST" => "",
			"EDIT_IN_LIST" => "",
			"IS_SEARCHABLE" => "N",
			"SETTINGS" => [
				"DEFAULT_VALUE" => "N",
			],
			"EDIT_FORM_LABEL" => [
				"ru" => "Получать сообщения об акциях, скидках и новостях",
				"en" => "Receive messages about promotions, discounts and news",
			],
			"LIST_COLUMN_LABEL" => [
				"ru" => "Получать сообщения об акциях, скидках и новостях",
				"en" => "Receive messages about promotions, discounts and news",
			],
			"LIST_FILTER_LABEL" => [
				"ru" => "Получать сообщения об акциях, скидках и новостях",
				"en" => "Receive messages about promotions, discounts and news",
			],
		];

		if (empty($existFields[$aUserFields['FIELD_NAME']]) && !$oUserTypeEntity->Add($aUserFields)) {
			return false;
		}

		$aUserFields = [
			"ENTITY_ID" => "USER",
			"FIELD_NAME" => "UF_PHONE_CONFIRMED",
			"USER_TYPE_ID" => "boolean",
			"XML_ID" => "PHONE_CONFIRMED",
			"SORT" => 500,
			"MULTIPLE" => "N",
			"MANDATORY" => "N",
			"SHOW_FILTER" => "N",
			"SHOW_IN_LIST" => "",
			"EDIT_IN_LIST" => "",
			"IS_SEARCHABLE" => "N",
			"SETTINGS" => [
				"DEFAULT_VALUE" => "N",
			],
			"EDIT_FORM_LABEL" => [
				"ru" => "Мобильный телефон подтвержден",
				"en" => "Mobile phone confirmed",
			],
			"LIST_COLUMN_LABEL" => [
				"ru" => "Мобильный телефон подтвержден",
				"en" => "Mobile phone confirmed",
			],
			"LIST_FILTER_LABEL" => [
				"ru" => "Мобильный телефон подтвержден",
				"en" => "Mobile phone confirmed",
			],
		];

		if (empty($existFields[$aUserFields['FIELD_NAME']]) && !$oUserTypeEntity->Add($aUserFields)) {
			return false;
		}

		$aUserFields = [
			"ENTITY_ID" => "USER",
			"FIELD_NAME" => "UF_EMAIL_CONFIRMED",
			"USER_TYPE_ID" => "boolean",
			"XML_ID" => "EMAIL_CONFIRMED",
			"SORT" => 500,
			"MULTIPLE" => "N",
			"MANDATORY" => "N",
			"SHOW_FILTER" => "N",
			"SHOW_IN_LIST" => "",
			"EDIT_IN_LIST" => "",
			"IS_SEARCHABLE" => "N",
			"SETTINGS" => [
				"DEFAULT_VALUE" => "N",
			],
			"EDIT_FORM_LABEL" => [
				"ru" => "Email подтвержден",
				"en" => "Email confirmed",
			],
			"LIST_COLUMN_LABEL" => [
				"ru" => "Email подтвержден",
				"en" => "Email confirmed",
			],
			"LIST_FILTER_LABEL" => [
				"ru" => "Email подтвержден",
				"en" => "Email confirmed",
			],
		];

		if (empty($existFields[$aUserFields['FIELD_NAME']]) && !$oUserTypeEntity->Add($aUserFields)) {
			return false;
		}

		return true;
	}

	function UnInstallUserFields()
	{
		$oUserTypeEntity = new CUserTypeEntity();
		foreach ($this->userFields as $field) {
			$bdField = $oUserTypeEntity->GetList([], ["ENTITY_ID" => "USER", "FIELD_NAME" => $field])->fetch();

			if (!$bdField) {
				return false;
			}

			if (!$oUserTypeEntity->Delete($bdField["ID"])) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Получим путь до модуля
	 * @return string
	 */
	function getPath()
	{
		if (empty($this->PATH)) {
			$path = str_replace("\\", "/", __FILE__);
			$path = substr($path, 0, strlen($path) - strlen("/index.php"));
			$this->PATH = $path;
		}
		return $this->PATH;
	}
}

?>
