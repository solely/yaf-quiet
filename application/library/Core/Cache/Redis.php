<?php
/**
 * FileName： Redis.php
 * Author: solely
 * DateTime: 2016/11/6 15:28
 * Description:
 *   Redis缓存驱动，要求安装phpredis扩展：https://github.com/nicolasff/phpredis
 */
namespace Core\Cache;

use Core\Cache as BaseCache;
use Yaf\Exception;
use Yaf\Registry;

class Redis extends BaseCache
{
    protected $cacheConfig;

    /*
     *
     */
    public function __construct($options = array())
    {
        if (!extension_loaded('redis')) {
            throw new Exception('not support redis:没有加载redis扩展');
        }

        $configObj = Registry::get("config");
        $this->cacheConfig = $configObj->cache->toArray();

        $options = array_merge(array(
            'host' => $this->cacheConfig['data_cache_host'] ?: '127.0.0.1',
            'port' => $this->cacheConfig['data_cache_port'] ?: 6379,
            'timeout' => $this->cacheConfig['data_cache_timeout'] ?: false,
            'persistent' => false,
        ), $options);

        $this->options = $options;
        $this->options['expire'] = isset($options['expire']) ? $options['expire'] : $this->cacheConfig['data_cache_expire'];
        $this->options['prefix'] = isset($options['prefix']) ? $options['prefix'] : $this->cacheConfig['data_cache_prefix'];
        $this->options['length'] = isset($options['length']) ? $options['length'] : 0;
        $func = $options['persistent'] ? 'pconnect' : 'connect';
        $this->handler = new \Redis();
        $options['timeout'] === false ?
            $this->handler->$func($options['host'], $options['port']) :
            $this->handler->$func($options['host'], $options['port'], $options['timeout']);
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @return mixed
     */
    public function get($name)
    {
        $value = $this->handler->get($this->options['prefix'] . $name);
        $jsonData = json_decode($value, true);
        return ($jsonData === NULL) ? $value : $jsonData; //检测是否为JSON数据 true 返回JSON解析数组, false返回源数据
    }

    /**
     * 写入缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $value 存储数据
     * @param integer $expire 有效时间（秒）
     * @return boolean
     */
    public function set($name, $value, $expire = null)
    {
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }
        $name = $this->options['prefix'] . $name;
        //对数组/对象数据进行缓存处理，保证数据完整性
        $value = (is_object($value) || is_array($value)) ? json_encode($value) : $value;
        if (is_int($expire) && $expire) {
            $result = $this->handler->setex($name, $expire, $value);
        } else {
            $result = $this->handler->set($name, $value);
        }
        if ($result && $this->options['length'] > 0) {
            // 记录缓存队列
            $this->queue($name);
        }
        return $result;
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolean
     */
    public function rm($name)
    {
        return $this->handler->delete($this->options['prefix'] . $name);
    }

    /**
     * 清除缓存
     * @access public
     * @return boolean
     */
    public function clear()
    {
        return $this->handler->flushDB();
    }
}