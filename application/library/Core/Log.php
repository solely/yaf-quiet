<?php
/**
 * FileName： Log.php
 * Author: solely
 * DateTime: 2016/11/20 18:28
 * Description:     日志记录
 *
 * 日志等级
 * DEBUG (100): 详细的debug信息。
 * INFO (200): 关键事件。
 * NOTICE (250): 普通但是重要的事件。
 * WARNING (300): 出现非错误的异常。
 * ERROR (400): 运行时错误，但是不需要立刻处理。
 * CRITICAL (500): 严重错误。
 * EMERGENCY (600): 系统不可用。
 */
namespace Core;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Yaf\Application;
use Yaf\Exception;

class Log
{
    private $logLevel;
    private $loggerLevel;
    private static $instance;
    private static $loggerInstance;

    private function __construct()
    {
        $this->logLevel = strtoupper(Application::app()->getConfig()->log->level);
        $this->loggerLevel = !empty(Logger::getLevels()[$this->logLevel]) ? Logger::getLevels()[$this->logLevel] : Logger::DEBUG;
    }

    private function getLogger($channelName = '', $logDir = '')
    {
        try {
            empty($channelName) && $channelName = 'app';
            empty($logDir) && $logDir = APPLICATION_PATH . DS . 'log' . DS . 'app_' . date('Y-m-d') . '.log';
            $logKey = md5($channelName . $logDir);

            if (empty(self::$loggerInstance[$logKey])) {
                $stream = new StreamHandler($logDir, $this->loggerLevel);
                $logger = new Logger($channelName);
                $logger->pushHandler($stream);
                self::$loggerInstance[$logKey] = $logger;
            }
            return self::$loggerInstance[$logKey];
        } catch (Exception $e) {
            throw new Exception($e->getTraceAsString());
        }
    }

    public static function debug($message, array $context = array(), $channelName = '', $logDir = '')
    {
        self::getInstance()->getLogger($channelName, $logDir)->addDebug($message, $context);
    }

    public static function info($message, array $context = array(), $channelName = '', $logDir = '')
    {
        self::getInstance()->getLogger($channelName, $logDir)->addInfo($message, $context);
    }

    public static function notice($message, array $context = array(), $channelName = '', $logDir = '')
    {
        self::getInstance()->getLogger($channelName, $logDir)->addNotice($message, $context);
    }

    public static function __callStatic($name, $arguments)
    {
        $argLength = count($arguments);
        if ($argLength < 2 || $argLength > 4) {
            throw new Exception('The parameters of the incoming number must be 2 to 4');
        }
        try {
            $arg[2] = isset($arguments[2]) ? $arguments[2] : 'app';
            $arg[3] = isset($arguments[3]) ? $arguments[3] : '';
            call_user_func_array(array(self::getInstance()->getLogger($arg[2], $arg[3]), 'add' . ucfirst($name)), $arguments);
        } catch (Exception $e) {
            throw new Exception($e->getTraceAsString());
        }
    }

    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}