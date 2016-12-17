<?php
/**
 * FileName： Db.php
 * Author: solely
 * DateTime: 2016/11/6 16:40
 * Description:     数据库中间层实现类
 */
namespace Core;

use Yaf\Registry;
use Yaf\Exception;

class Db
{
    private static $instance = array();//数据库连接实例
    private static $_instance = null;//当前数据库连接实例

    /**
     * 取得数据库类实例
     * @param array $config
     * @return mixed|null
     * @throws Exception
     */
    public static function getInstance($config = array())
    {
        if (empty($config)) {
            $configObj = Registry::get("config");
            $config = $configObj->database->config->toArray();
        }

        $md5 = md5(serialize($config));
        if (!isset(self::$instance[$md5])) {
            // 解析连接参数 支持数组和字符串
            $options = self::parseConfig($config);
            // 兼容mysqli
            if ('mysqli' == $options['type']) $options['type'] = 'mysql';
            $class = '\\Core\\Db\\' . ucwords(strtolower($options['type']));
            if (class_exists($class)) {
                self::$instance[$md5] = new $class($options);
            } else {
                // 类没有定义
                throw new Exception($class . 'is not has, 类没有定义，不存在');
            }
        }
        self::$_instance = self::$instance[$md5];
        return self::$_instance;
    }

    /**
     * 数据库连接参数解析
     * @param $config
     * @return array
     */
    private static function parseConfig($config)
    {
        if (is_string($config)) {
            return self::parseDsn($config);
        }
        $config = array_change_key_case($config);
        $config = array(
            'type' => $config['type'],
            'username' => $config['user'],
            'password' => $config['pwd'],
            'hostname' => $config['host'],
            'hostport' => $config['port'],
            'database' => $config['dbname'],
            'dsn' => isset($config['dsn']) ? $config['dsn'] : null,
            'params' => isset($config['params']) ? $config['params'] : null,
            'charset' => isset($config['charset']) ? $config['charset'] : 'utf8',
            'deploy' => isset($config['deploy_type']) ? $config['deploy_type'] : 0,
            'rw_separate' => isset($config['rw_separate']) ? $config['rw_separate'] : false,
            'master_num' => isset($config['master_num']) ? $config['master_num'] : 1,
            'slave_no' => isset($config['slave_no']) ? $config['slave_no'] : '',
            'debug' => isset($config['debug']) ? $config['debug'] : false,
            'lite' => isset($config['lite']) ? $config['lite'] : false,
        );

        return $config;
    }

    /**
     * DSN解析
     * 格式： mysql://username:passwd@localhost:3306/DbName?param1=val1&param2=val2#utf8
     * @param $dsnStr
     * @return array|bool
     */
    private static function parseDsn($dsnStr)
    {
        if (empty($dsnStr)) {
            return false;
        }
        $info = parse_url($dsnStr);
        if (!$info) {
            return false;
        }
        $dsn = array(
            'type' => $info['scheme'],
            'username' => isset($info['user']) ? $info['user'] : '',
            'password' => isset($info['pass']) ? $info['pass'] : '',
            'hostname' => isset($info['host']) ? $info['host'] : '',
            'hostport' => isset($info['port']) ? $info['port'] : '',
            'database' => isset($info['path']) ? substr($info['path'], 1) : '',
            'charset' => isset($info['fragment']) ? $info['fragment'] : 'utf8',
        );

        if (isset($info['query'])) {
            parse_str($info['query'], $dsn['params']);
        } else {
            $dsn['params'] = array();
        }
        return $dsn;
    }

    // 调用驱动类的方法
    public static function __callStatic($method, $params)
    {
        return call_user_func_array(array(self::$_instance, $method), $params);
    }
}