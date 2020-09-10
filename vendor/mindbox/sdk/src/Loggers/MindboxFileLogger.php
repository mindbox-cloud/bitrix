<?php

namespace Mindbox\Loggers;

use Mindbox\Exceptions\MindboxConfigException;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * Класс, отвечающий за запись логов в файл стандартными средствами PHP.
 * Реализует интерфейс \Psr\Log\LoggerInterface.
 * Логгер предполагает 8 уровней критичности, определённых в PSR-3:
 * @see https://www.php-fig.org/psr/psr-3/
 * Class MindboxFileLogger
 *
 * @package Mindbox\Loggers
 */
class MindboxFileLogger extends AbstractLogger
{
    /**
     * Отладка.
     */
    const DEBUG = 100;

    /**
     * Иинформация.
     */
    const INFO = 200;

    /**
     * Замечание.
     */
    const NOTICE = 250;

    /**
     * Предупреждение.
     */
    const WARNING = 300;

    /**
     * Ошибка.
     */
    const ERROR = 400;

    /**
     * Критическая ошибка.
     */
    const CRITICAL = 500;

    /**
     * Тревога.
     */
    const ALERT = 550;

    /**
     * Авария.
     */
    const EMERGENCY = 600;

    /**
     * Имя файла для записи логов.
     */
    const LOG_FILE_NAME = 'mindbox.log';

    /**
     * @var string $logsDirectory Путь к диретории для записи логов.
     */
    private $logsDirectory;

    /**
     * @var int $logLevel Уровень логирования запросов.
     */
    private $logLevel;

    /**
     * @var string[] $levels Массив соответствия между уровнями логирования MindboxFileLogger и PSR-3.
     */
    protected static $levels = [
        self::DEBUG     => 'DEBUG',
        self::INFO      => 'INFO',
        self::NOTICE    => 'NOTICE',
        self::WARNING   => 'WARNING',
        self::ERROR     => 'ERROR',
        self::CRITICAL  => 'CRITICAL',
        self::ALERT     => 'ALERT',
        self::EMERGENCY => 'EMERGENCY',
    ];

    /**
     * Конструктор MindboxFileLogger.
     *
     * @param string $logsDirectory Путь к диретории для записи логов.
     * @param string $logLevel      Уровень логирования запросов.
     *                              По умолчанию логируются только ошибки:
     *                              - код ответа 4XX;
     *                              - пустое тело ответа;
     *                              - неизвестный код ответа.
     */
    public function __construct($logsDirectory, $logLevel = LogLevel::ERROR)
    {
        $this->logsDirectory = $logsDirectory;
        $this->validateLogsDirectory();
        $this->setLogLevel($logLevel);
    }

    /**
     * Сеттер для $logLevel.
     *
     * @param string|int $logLevel Уровень логирования.
     *
     * @throws MindboxConfigException
     */
    private function setLogLevel($logLevel)
    {
        $this->logLevel = $this->toMindboxLogLevel($logLevel);
    }

    /**
     * Перевод уровня логирования в формат понятный MindboxFileLogger.
     *
     * @param string|int $logLevel Уровень логирования, может быть задан как строкой, так и числом.
     *
     * @return int
     */
    private function toMindboxLogLevel($logLevel)
    {
        if (is_string($logLevel)) {
            if (defined(__CLASS__ . '::' . strtoupper($logLevel))) {
                return constant(__CLASS__ . '::' . strtoupper($logLevel));
            }
            throw new MindboxConfigException(
                'Level "' . $logLevel . '" is not defined, use one of: ' . implode(', ', array_keys(static::$levels))
            );
        }

        return $logLevel;
    }

    /**
     * Проверка существования и создание директории для записи логов.
     *
     * @throws MindboxConfigException
     */
    private function validateLogsDirectory()
    {
        $fullPath = $this->getFullDirPath();

        if (!file_exists($fullPath)) {
            if (!mkdir($fullPath, 0755, true)) {
                throw new MindboxConfigException('Can`t create logs directory');
            }
        }
    }

    /**
     * Возвращает полный путь до директории содержащей лог файл.
     *
     * @return string
     */
    private function getFullDirPath()
    {
        return $this->logsDirectory . DIRECTORY_SEPARATOR . static::getLogDirPath();
    }

    /**
     * Возвращает путь до директории в которую будут записаны логи в формате: mindbox/ГГГГ/ММ/ДД.
     *
     * @return string
     */
    public static function getLogDirPath()
    {
        return 'mindbox' . DIRECTORY_SEPARATOR . date('Y') .
            DIRECTORY_SEPARATOR . date('m') . DIRECTORY_SEPARATOR . date('d');
    }

    /**
     * Проверка уровня логирования, формирование сообщения и запись в файл.
     *
     * @param mixed  $level   Уровень записи.
     * @param string $message Сообщение.
     * @param array  $context Контекст.
     */
    public function log($level, $message, array $context = [])
    {
        if ($this->toMindboxLogLevel($level) < $this->logLevel) {
            return;
        }

        $record   = $this->getMessage($level, $message, $context);
        $fileName = $this->getFullPath();

        $this->writeLog($fileName, $record);
    }

    /**
     * Формирование сообщения для записи в лог.
     *
     * @param mixed  $level   Уровень сообщения.
     * @param string $message Тело сообщения.
     * @param array  $context Контекст.
     *
     * @return string
     */
    private function getMessage($level, $message, $context)
    {
        return $message . ' LEVEL::' . $level . PHP_EOL . print_r($context, true) . "\r\n\r\n\r\n";
    }

    /**
     * Возвращает полный путь до файла с логами.
     *
     * @return string
     */
    private function getFullPath()
    {
        return $this->getFullDirPath() . DIRECTORY_SEPARATOR . self::LOG_FILE_NAME;
    }

    /**
     * Запись лога в файл.
     *
     * @param string $fileName Полный путь до файла.
     * @param string $record   Тело сообщения.
     */
    private function writeLog($fileName, $record)
    {
        file_put_contents($fileName, $record, FILE_APPEND);
    }
}
