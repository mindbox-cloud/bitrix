<?php

require  __DIR__ . '/../include.php';

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Type\DateTime;

// phpcs:disable
\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

class mindbox_marketing extends CModule
{
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
        "UF_EMAIL_CONFIRMED"
    ];

    public function __construct()
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
    public function DoInstall()
    {
        $this->InstallEvents();

        $this->InstallFiles();

        RegisterModule($this->MODULE_ID);

        $this->InstallAgents();
        $this->InstallDB();
        $this->InstallUserFields();

        // установка правила работы с корзиной для расчета скидки на товар
        $productCartRuleInstaller = new \Mindbox\Installer\ProductCartRuleInstaller();
        $productCartRuleInstaller->install();

        // установка правила работы с корзиной для расчета скидки на доставку
        $deliveryCartRuleInstaller = new \Mindbox\Installer\DeliveryCartRuleInstaller();
        $deliveryCartRuleInstaller->install();

        $orderPropertyInstaller = new \Mindbox\Installer\OrderPropertiesInstaller();
        $orderPropertyInstaller->install();

        $transactionTable = new \Mindbox\DataBase\MindboxTransactionTable();
        $transactionTable->createTable();

        $mindboxLog = new \Mindbox\AccessLogs();
        $mindboxLog->setLogAccess();

        $GLOBALS["APPLICATION"]->IncludeAdminFile(GetMessage("MINDBOX_INSTALL_TITLE"), __DIR__ . "/step1.php");
    }

    /**
     * Удаляем модуль
     */
    public function DoUninstall()
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
                $productCartRuleInstaller = new \Mindbox\Installer\ProductCartRuleInstaller();
                $productCartRuleInstaller->unInstall();

                $deliveryCartRuleInstaller = new \Mindbox\Installer\DeliveryCartRuleInstaller();
                $deliveryCartRuleInstaller->unInstall();

                $orderPropertyInstaller = new \Mindbox\Installer\OrderPropertiesInstaller();
                $orderPropertyInstaller->uninstall();
            }

            UnRegisterModule($this->MODULE_ID);
            $APPLICATION->IncludeAdminFile(GetMessage("MINDBOX_UNINSTALL_TITLE"), __DIR__ . "/unstep2.php");
        } else {
            die();
        }
    }

    public function InstallAgents()
    {
        $now = new DateTime();
        CAgent::AddAgent(
            "\Mindbox\YmlFeedMindbox::start();",
            $this->MODULE_ID,
            "N",
            86400,
            $now,
            "Y",
            $now,
            30
        );

        CAgent::AddAgent(
            "\Mindbox\QueueTable::start();",
            $this->MODULE_ID,
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
                $this->MODULE_ID,
                "N",
                86400,
                $tomorrow,
                "Y",
                $tomorrow,
                30
        );

        return true;
    }

    public function UnInstallAgents()
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

        CAgent::RemoveAgent(
        '\Mindbox\LogsRotation::agentRotationLogs();',
            $this->MODULE_ID
        );
    }

    public function InstallDB()
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

    public function UnInstallDB()
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
    public function InstallEvents()
    {
        $moduleEventController = new \Mindbox\EventController();
        $moduleEventController->installEvents();
        return true;
    }

    /**
     * Удаляем события
     * @return bool
     */
    public function UnInstallEvents()
    {
        $moduleEventController = new \Mindbox\EventController();
        $moduleEventController->unInstallEvents();
        return true;
    }

    public function InstallFiles()
    {
        mkdir($_SERVER["DOCUMENT_ROOT"] . "/bitrix/js/mindbox");
        mkdir($_SERVER["DOCUMENT_ROOT"] . "/bitrix/css/mindbox");
        mkdir($_SERVER["DOCUMENT_ROOT"] . "/bitrix/images/mindbox");

        if (!CopyDirFiles(__DIR__ . "/components", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components", true, true)) {
            return false;
        }

        if (!CopyDirFiles(
            __DIR__ . "/js",
            $_SERVER["DOCUMENT_ROOT"] . "/bitrix/js/mindbox",
            true,
            true
        )) {
            return false;
        }

        if (!CopyDirFiles(
            __DIR__ . "/css",
            $_SERVER["DOCUMENT_ROOT"] . "/bitrix/css/mindbox",
            true,
            true
        )) {
            return false;
        }

        if (!CopyDirFiles(
            __DIR__ . "/images",
            $_SERVER["DOCUMENT_ROOT"] . "/bitrix/images/mindbox",
            true,
            true
        )) {
            return false;
        }

        return true;
    }

    public function UnInstallFiles()
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

    public function InstallUserFields()
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

    public function UnInstallUserFields()
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
    public function getPath()
    {
        if (empty($this->PATH)) {
            $path = str_replace("\\", "/", __FILE__);
            $path = substr($path, 0, strlen($path) - strlen("/index.php"));
            $this->PATH = $path;
        }
        return $this->PATH;
    }
}
// phpcs:enable