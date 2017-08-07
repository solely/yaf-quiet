<?php
/**
 * FileName： Cache.php
 * Author: solely
 * DateTime: 2016/11/6 12:50
 * Description:     缓存类
 */
namespace Core;

use Yaf\Exception;
use Yaf\Registry;

class Cache
{
    /*
     * 操作句柄
     */
    protected $handler;

    /*
     * 链接参数
     */
    protected $options = array();

    /*
     * 链接缓存
     */
    public function connect($type = '', $options = array())
    {
        if (empty($type)) {
            $config_obj = Registry::get("config");
            $config_cache = $config_obj->cache->toArray();
            $type = $config_cache['data_cache_type'];
        }

        $class = strpos($type, '\\') ? $type : '\\Core\\Cache\\' . ucwords(strtolower($type));
        if (class_exists($class)) {
            $cache = new $class($options);
        } else {
            throw new Exception($type . ' 缓存类型不存在');
        }

        return $cache;
    }

    public static function getInstance($type = '', $options = array())
    {
        static $_instance = array();
        $guid = $type . to_guid_string($options);
        if (!isset($_instance[$guid])) {
            $obj = new self();
            $_instance[$guid] = $obj->connect($type, $options);
        }
        return $_instance[$guid];
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    public function __set($name, $value)
    {
        return $this->set($name, $value);
    }

    public function __unset($name)
    {
        $this->rm($name);
    }

    public function setOptions($name, $value)
    {
        $this->options[$name] = $value;
    }

    public function getOptions($name)
    {
        return $this->options[$name];
    }

    protected function queue($key)
    {
        static $_handler = array(
            'file' => array('\\Core\\Cache\\File::Fget', '\\Core\\Cache\\File::Fset'),
            'xcache' => array('xcache_get', 'xcache_set'),
            'apc' => array('apc_fetch', 'apc_store'),
        );
        $queue = isset($this->options['queue']) ? $this->options['queue'] : 'file';
        $fun = isset($_handler[$queue]) ? $_handler[$queue] : $_handler['file'];
        $queue_name = isset($this->options['queue_name']) ? $this->options['queue_name'] : 'think_queue';
        $value = $fun[0]($queue_name);
        if (!$value) {
            $value = array();
        }
        // 进列
        if (false === array_search($key, $value)) array_push($value, $key);
        if (count($value) > $this->options['length']) {
            // 出列
            $key = array_shift($value);
            // 删除缓存
            $this->rm($key);
        }
        return $fun[1]($queue_name, $value);
    }

    public function __call($method, $args)
    {
        //调用缓存类型自己的方法
        if (method_exists($this->handler, $method)) {
            return call_user_func_array(array($this->handler, $method), $args);
        } else {
            throw new Exception($method . ' METHOD NOT EXIST');
            return;
        }
    }
}
