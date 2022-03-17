<?php

namespace Mindbox;

use Mindbox\Options;

class AccessLogs
{

    const ADMIN_GROUP_ID = 1;

    public function setLogAccess()
    {
        $logPath = Options::getModuleOption('LOG_PATH');

        if (empty($logPath)) {
            return false;
        } else {
            if (!file_exists($logPath)) {
                @mkdir($logPath, BX_DIR_PERMISSIONS, true);
            }
        }

        $logAccessFiles = [
            __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . '.htaccess',
            __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'index.php'
        ];

        foreach ($logAccessFiles as $file) {
            $sourceFileName = $file;
            $destinationFileName = $logPath . DIRECTORY_SEPARATOR . pathinfo($file)['basename'];
            @copy($sourceFileName, $destinationFileName);
        }
    }

    public function checkLogAccess($logPath)
    {
        global $USER, $APPLICATION;

        if (!$logPath) {
            return false;
        }

        $logDir = Options::getModuleOption('LOG_PATH');
        $mindboxFilename = $logDir . $logPath;
        $arGroups = $USER->GetUserGroupArray();
        if ($USER->IsAuthorized() &&
            in_array(self::ADMIN_GROUP_ID, $arGroups) &&
            file_exists($mindboxFilename) &&
            is_file($mindboxFilename) &&
            strpos($mindboxFilename, $_SERVER['PHP_SELF']) === false
        ) {
            $APPLICATION->RestartBuffer();
            echo "<pre>" . htmlspecialchars(file_get_contents($mindboxFilename)) . "</pre>";
            exit;
        } else {
            ShowMessage(GetMessage('LOG_DENIED_ERROR_MESSAGE'));
        }
    }
}
