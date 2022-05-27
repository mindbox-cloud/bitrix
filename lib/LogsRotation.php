<?php

namespace Mindbox;

class LogsRotation
{
    protected static $logFileName = 'mindbox.log';

    protected static $pathSaveArchive = 'archive';

    protected static $pathToLogs = 'mindbox';

    protected static function getLogPath()
    {
        return str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR,
                DIRECTORY_SEPARATOR,
                Options::getModuleOption('LOG_PATH') . DIRECTORY_SEPARATOR . self::$pathToLogs);
    }

    protected static function getArchivePath()
    {
        $pathArchive = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR,
                DIRECTORY_SEPARATOR,
                Options::getModuleOption('LOG_PATH') . DIRECTORY_SEPARATOR . self::$pathToLogs . DIRECTORY_SEPARATOR . self::$pathSaveArchive);

        if (!is_dir($pathArchive)) {
            mkdir($pathArchive, defined('BX_DIR_PERMISSIONS') ? BX_DIR_PERMISSIONS : 0755);
        }

        return $pathArchive;
    }

    protected static function getLogLifeTime()
    {
        return (int)Options::getModuleOption('LOG_LIFE_TIME');
    }

    public static function agentRotationLogs()
    {
        $optionLifeDay = self::getLogLifeTime();

        if ($optionLifeDay > 0) {
            $optionPathLogs = self::getLogPath();
            $optionPathArchive = self::getArchivePath();

            $logFiles = self::findLogFiles($optionPathLogs, (new \DateTime())->setTime(0, 0, 0));

            if (extension_loaded('zlib')) {
                self::createArchiveZlib($optionPathArchive, $logFiles);
            } elseif (extension_loaded('zip')) {
                self::createArchiveZip($optionPathArchive, $logFiles);
            } elseif (extension_loaded('bz2')) {
                self::createArchiveBzip2($optionPathArchive, $logFiles);
            }

            self::removeLogs($optionPathLogs, (new \DateTime())->setTime(0, 0, 0));

            $lifeDays = new \DateTime(sprintf('-%s days', $optionLifeDay));
            self::removeArchive($optionPathArchive, $lifeDays);
        }

        return '\\' . __METHOD__ . '();';
    }

    public static function findLogFiles($path, \DateTime $date)
    {
        $logFiles = [];

        /** @var $iterator \RecursiveDirectoryIterator */
        /** @var $item \SplFileInfo */
        foreach ($iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS | \RecursiveDirectoryIterator::FOLLOW_SYMLINKS),
                \RecursiveIteratorIterator::SELF_FIRST) as $item) {

            if ($item->isFile()
                    && $item->isReadable()
                    && $item->getFilename() === self::$logFileName
                    && $date->getTimestamp() >= $item->getMTime()
            ) {
                $logFiles[] = $item;
            }
        }

        return $logFiles;
    }

    /**
     * remove old file logs
     * @param $path
     * @param \DateTime $date
     * @return void
     */
    public static function removeLogs($path, \DateTime $date)
    {
        /** @var $iterator \RecursiveDirectoryIterator */
        /** @var $item \SplFileInfo */
        foreach ($iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS | \RecursiveDirectoryIterator::FOLLOW_SYMLINKS),
                \RecursiveIteratorIterator::CHILD_FIRST) as $item
        ) {
            if ($item->isFile() && $date->getTimestamp() >= $item->getMTime()) {
                unlink($item->getPathname());
            } elseif ($item->isDir() && !$item->isLink()) {
                rmdir($item->getPathname());
            }
        }
    }

    /**
     * remove old archive
     *
     * @param $pathToArchive
     * @param \DateTime $lifeDays
     * @return void
     */
    public static function removeArchive($pathToArchive, \DateTime $lifeDays)
    {
        if (!is_dir($pathToArchive)) {
            return;
        }

        foreach ($iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($pathToArchive, \RecursiveDirectoryIterator::SKIP_DOTS | \RecursiveDirectoryIterator::FOLLOW_SYMLINKS),
                \RecursiveIteratorIterator::CHILD_FIRST) as $item
        ) {
            $matches = null;

            if ($item->isFile() && preg_match("/(\d{4})-(\d{2})-(\d{2})/ms".BX_UTF_PCRE_MODIFIER, $item->getFilename(), $matches)) {
                $archiveCreateTime = \DateTime::createFromFormat('Y-m-d', $matches[0]);

                if ($archiveCreateTime->getTimestamp() < $lifeDays->getTimestamp()) {
                    unlink($item->getPathname());
                }
            }
        }
    }

    /**
     * Adds a file to a GZ archive
     * @param $pathToSave
     * @param $arFiles
     * @return void
     */
    public static function createArchiveZlib($pathToSave, $arFiles)
    {
        if (!empty($arFiles)) {
            /** @var $item \SplFileInfo */
            foreach ($arFiles as $item) {
                $dateTime = (new \Datetime())->setTimestamp($item->getMTime());

                $archiveName = $pathToSave . DIRECTORY_SEPARATOR . $dateTime->format('Y-m-d') . '_' . $item->getFilename() . '.gz';

                if ($resource = gzopen($archiveName, 'w')) {

                    if ($resourceLogFile = fopen($item->getRealPath(),'rb')) {
                        while(!feof($resourceLogFile)) {
                            gzwrite($resource, fread($resourceLogFile,1024*512));
                        }

                        fclose($resourceLogFile);
                    }

                    gzclose($resource);
                }
            }
        }
    }

    /**
     * Adds a file to a ZIP archive
     * @param $pathToSave
     * @param $arFiles
     * @return void
     */
    public static function createArchiveZip($pathToSave, $arFiles)
    {
        if (!empty($arFiles)) {
            /** @var $item \SplFileInfo */
            foreach ($arFiles as $item) {
                $zip = new \ZipArchive();
                $dateTime = (new \Datetime())->setTimestamp($item->getMTime());

                $archiveName = $pathToSave . DIRECTORY_SEPARATOR . $dateTime->format('Y-m-d') . '_' . $item->getFilename() . '.zip';

                if ($zip->open($archiveName, \ZipArchive::CREATE) === true) {
                    $zip->addFile($item->getRealPath(), $dateTime->format('Y-m-d') . '_' . $item->getFilename());
                    $zip->close();
                }
            }
        }
    }

    /**
     * Adds a file to a BZ2 archive
     * @param $pathToSave
     * @param $arFiles
     * @return void
     */
    public static function createArchiveBzip2($pathToSave, $arFiles)
    {
        if (!empty($arFiles)) {
            /** @var $item \SplFileInfo */
            foreach ($arFiles as $item) {
                $dateTime = (new \Datetime())->setTimestamp($item->getMTime());

                $archiveName = $pathToSave . DIRECTORY_SEPARATOR . $dateTime->format('Y-m-d') . '_' . $item->getFilename() . '.bz2';

                if ($resource = bzopen($archiveName, 'w')) {
                    if ($resourceLogFile = fopen($item->getRealPath(),'rb')) {
                        while(!feof($resourceLogFile)) {
                            bzwrite($resource, fread($resourceLogFile,1024*512));
                        }

                        fclose($resourceLogFile);
                    }

                    bzclose($resource);
                }
            }
        }
    }
}
