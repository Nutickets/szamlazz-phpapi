<?php

namespace SzamlaAgent;

class Log
{
    const LOG_FILENAME = 'szamlaagent';
    const LOG_PATH = './logs';
    const LOG_LEVEL_OFF   = 0;
    const LOG_LEVEL_ERROR = 1;
    const LOG_LEVEL_WARN  = 2;
    const LOG_LEVEL_DEBUG = 3;

    private static $logLevels = [
        self::LOG_LEVEL_OFF,
        self::LOG_LEVEL_ERROR,
        self::LOG_LEVEL_WARN,
        self::LOG_LEVEL_DEBUG
    ];

    private $logFileName = self::LOG_FILENAME;
    private $logPath = self::LOG_PATH;

    protected static $instance;

    protected function __construct($logPath = self::LOG_PATH, $fileName = self::LOG_FILENAME)
    {
        $this->logPath = $logPath;
        $this->logFileName = $fileName . '_' . date('Y-m-d') . '.log';
    }

    public function getLogFileName()
    {
        return $this->logFileName;
    }

    public function setLogFileName($fileName): static
    {
        $this->logFileName = $fileName;

        return $this;
    }

    public function getLogPath()
    {
        return $this->logPath;
    }

    public function setLogPath($logPath): static
    {
        $this->logPath = $logPath;

        return $this;
    }

    public static function get()
    {
        $instance = self::$instance;
        if ($instance === null) {
            return self::$instance = new self();
        } else {
            return $instance;
        }
    }

    public static function writeLog($pMessage, $pType = self::LOG_LEVEL_DEBUG, $pEmail = '')
    {
        $log = Log::get();
        $filename   = SzamlaAgentUtil::getAbsPath($log->getLogPath(), $log->getLogFileName());
        $remoteAddr = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '';
        $logType = SzamlaAgentUtil::isNotBlank($log->getLogTypeStr($pType)) ? ' ['.$log->getLogTypeStr($pType).'] ' : '';
        $message    = '['.date('Y-m-d H:i:s').'] ['.$remoteAddr.']'. $logType . $pMessage.PHP_EOL;

        error_log($message, 3, $filename);

        if (!empty($pEmail) && $pType == self::LOG_LEVEL_ERROR) {
            $headers = "Content-Type: text/html; charset=UTF-8";
            error_log($message, 1, $pEmail, $headers);
        }
    }

    protected function getLogTypeStr($type)
    {
        return match ($type) {
            self::LOG_LEVEL_ERROR => 'error',
            self::LOG_LEVEL_WARN => 'warn',
            self::LOG_LEVEL_DEBUG => 'debug',
            default => '',
        };
    }

    public static function isValidLogLevel($logLevel)
    {
        return (in_array($logLevel, self::$logLevels));
    }

    public static function isNotValidLogLevel($logLevel)
    {
        return !self::isValidLogLevel($logLevel);
    }
}
