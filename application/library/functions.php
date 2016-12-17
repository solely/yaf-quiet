<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 
 * 公共函数库
 * @author solely
 * @time 2016年1月13日14:42:11
 */

//参数过滤
function getParamsVal($name, $default = null, $filter = "htmlspecialchars", $datas = null) {
    static $_PUT	=	null;
    if(strpos($name,'/')){ // 指定修饰符
        list($name,$type) 	=	explode('/',$name,2);
    }
    if(strpos($name,'.')) { // 指定参数来源
        list($method,$name) =   explode('.',$name,2);
    }else{ // 默认为自动判断
        $method =   'param';
    }
    switch(strtolower($method)) {
        case 'get'     :
            $input =& $_GET;
            break;
        case 'post'    :
            $input =& $_POST;
            break;
        case 'put'     :
            if(is_null($_PUT)){
                parse_str(file_get_contents('php://input'), $_PUT);
            }
            $input 	=	$_PUT;
            break;
        case 'param'   :
            switch(REQUEST_METHOD) {
                case 'POST':
                    $input  =  $_POST;
                    break;
                case 'PUT':
                    if(is_null($_PUT)){
                        parse_str(file_get_contents('php://input'), $_PUT);
                    }
                    $input 	=	$_PUT;
                    break;
                default:
                    $input  =  $_GET;
            }
            break;
        case 'request' :
            $input =& $_REQUEST;
            break;
        case 'session' :
            $input =& $_SESSION;
            break;
        case 'cookie'  :
            $input =& $_COOKIE;
            break;
        case 'server'  :
            $input =& $_SERVER;
            break;
        case 'globals' :
            $input =& $GLOBALS;
            break;
        case 'data'    :
            $input =& $datas;
            break;
        default:
            return null;
    }
    $filters    =   $filter;
    if(''==$name) { // 获取全部变量
        $data       =   $input;
        if($filters) {
            if(is_string($filters)){
                $filters    =   explode(',',$filters);
            }
            foreach($filters as $filter){
                $data   =   array_map_recursive($filter,$data); // 参数过滤
            }
        }
    }elseif(isset($input[$name])) { // 取值操作
        $data       =   $input[$name];
        if($filters) {
            if(is_string($filters)){
                if(0 === strpos($filters,'/')){
                    if(1 !== preg_match($filters,(string)$data)){
                        // 支持正则验证
                        return   isset($default) ? $default : null;
                    }
                }else{
                    $filters    =   explode(',',$filters);
                }
            }elseif(is_int($filters)){
                $filters    =   array($filters);
            }

            if(is_array($filters)){
                foreach($filters as $filter){
                    if(function_exists($filter)) {
                        $data   =   is_array($data) ? array_map_recursive($filter,$data) : $filter($data); // 参数过滤
                    }else{
                        $data   =   filter_var($data,is_int($filter) ? $filter : filter_id($filter));
                        if(false === $data) {
                            return   isset($default) ? $default : null;
                        }
                    }
                }
            }
        }
        if(!empty($type)){
            switch(strtolower($type)){
                case 'a':	// 数组
                    $data 	=	(array)$data;
                    break;
                case 'd':	// 数字
                    $data 	=	(int)$data;
                    break;
                case 'f':	// 浮点
                    $data 	=	(float)$data;
                    break;
                case 'b':	// 布尔
                    $data 	=	(boolean)$data;
                    break;
                case 's':   // 字符串
                default:
                    $data   =   (string)$data;
            }
        }
    }else{ // 变量默认值
        $data       =    isset($default)?$default:null;
    }
    is_array($data) && array_walk_recursive($data,'tFilter');
    return $data;
}

function array_map_recursive($filter, $data) {
    $result = array();
    foreach ($data as $key => $val) {
        $result[$key] = is_array($val) ? array_map_recursive($filter, $val) : call_user_func($filter, $val);
    }
    return $result;
}

/**
 * 字符串命名风格转换
 * type 0 将Java风格转换为C的风格 1 将C风格转换为Java的风格
 * @param string $name 字符串
 * @param integer $type 转换类型
 * @return string
 */
function parse_name($name, $type = 0) {
    if ($type) {
        return ucfirst(preg_replace_callback('/_([a-zA-Z])/', function($match) {
                    return strtoupper($match[1]);
                }, $name));
    } else {
        return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
    }
}

/**
 * 缓存管理
 * @param mixed $name 缓存名称，如果为数组表示进行缓存设置
 * @param mixed $value 缓存值
 * @param mixed $options 缓存参数
 * @return mixed
 */
function cache($name, $value = '', $options = null) {
    static $cache = '';
    if (is_array($options)) {
        // 缓存操作的同时初始化
        $type = isset($options['type']) ? $options['type'] : '';
        $cache = \Core\Cache::getInstance($type, $options);
    } elseif (is_array($name)) { // 缓存初始化
        $type = isset($name['type']) ? $name['type'] : '';
        $cache = \Core\Cache::getInstance($type, $name);
        return $cache;
    } elseif (empty($cache)) { // 自动初始化
        $cache = \Core\Cache::getInstance();
    }
    if ('' === $value) { // 获取缓存
        return $cache->get($name);
    } elseif (is_null($value)) { // 删除缓存
        return $cache->rm($name);
    } else { // 缓存数据
        if (is_array($options)) {
            $expire = isset($options['expire']) ? $options['expire'] : NULL;
        } else {
            $expire = is_numeric($options) ? $options : NULL;
        }
        return $cache->set($name, $value, $expire);
    }
}

/**
 * 根据PHP各种类型变量生成唯一标识号
 * @param mixed $mix 变量
 * @return string
 */
function to_guid_string($mix) {
    if (is_object($mix)) {
        return spl_object_hash($mix);
    } elseif (is_resource($mix)) {
        $mix = get_resource_type($mix) . strval($mix);
    } else {
        $mix = serialize($mix);
    }
    return md5($mix);
}

/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @param boolean $adv 是否进行高级模式获取（有可能被伪装） 
 * @return mixed
 */
function get_client_ip($type = 0, $adv = false) {
    $type = $type ? 1 : 0;
    static $ip = NULL;
    if ($ip !== NULL)
        return $ip[$type];
    if ($adv) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);
            if (false !== $pos)
                unset($arr[$pos]);
            $ip = trim($arr[0]);
        }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u", ip2long($ip));
    $ip = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}

/**
 * 记录和统计时间（微秒）和内存使用情况
 * 使用方法:
 * <code>
 * G('begin'); // 记录开始标记位
 * // ... 区间运行代码
 * G('end'); // 记录结束标签位
 * echo G('begin','end',6); // 统计区间运行时间 精确到小数后6位
 * echo G('begin','end','m'); // 统计区间内存使用情况
 * 如果end标记位没有定义，则会自动以当前作为标记位
 * 其中统计内存使用需要 MEMORY_LIMIT_ON 常量为true才有效
 * </code>
 * @param string $start 开始标签
 * @param string $end 结束标签
 * @param integer|string $dec 小数位或者m
 * @return mixed
 */
function G($start, $end = '', $dec = 4) {
    static $_info = array();
    static $_mem = array();
    if (is_float($end)) { // 记录时间
        $_info[$start] = $end;
    } elseif (!empty($end)) { // 统计时间和内存使用
        if (!isset($_info[$end]))
            $_info[$end] = microtime(TRUE);
        if (MEMORY_LIMIT_ON && $dec == 'm') {
            if (!isset($_mem[$end]))
                $_mem[$end] = memory_get_usage();
            return number_format(($_mem[$end] - $_mem[$start]) / 1024);
        }else {
            return number_format(($_info[$end] - $_info[$start]), $dec);
        }
    } else { // 记录时间和内存使用
        $_info[$start] = microtime(TRUE);
        if (MEMORY_LIMIT_ON)
            $_mem[$start] = memory_get_usage();
    }
    return null;
}

/**
 * 浏览器友好的变量输出
 * @param mixed $var 变量
 * @param boolean $echo 是否输出 默认为true 如果为false 则返回输出字符串
 * @param string $label 标签 默认为空
 * @return void|string
 */
function dump($var, $echo = true, $label = null) {
    $label = (null === $label) ? '' : rtrim($label) . ':';
    ob_start();
    var_dump($var);
    $output = ob_get_clean();
    $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
    if (isCli()) {
        $output = PHP_EOL . $label . $output . PHP_EOL;
    } else {
        if (!extension_loaded('xdebug')) {
            $output = htmlspecialchars($output, ENT_QUOTES);
        }
        $output = '<pre>' . $label . $output . '</pre>';
    }
    if ($echo) {
        echo ($output);
        return null;
    } else {
        return $output;
    }
}

/*
 * 获取资源路径，模板使用
 * 可以考虑写成smarty插件函数
 * time  2016年3月3日21:24:12
 */
function assets($path) {
    return __ROOT__ . ltrim($path, '/');
}

/*
 * 生成目录规则，供文件上传使用
 * @time 2016年3月6日13:36:17
 */
function subdir() {
    return date("y") . "/" . date("m") . "/" . date("d") . "/" . substr(md5(uniqid()), 0, 3);
}

/*
 * uuid
 * @time 2016年3月6日23:01:16
 */
function uuid() {
    if (function_exists('com_create_guid')) {
        return com_create_guid();
    } else {
        mt_srand((double) microtime() * 10000); //optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45); // "-"
        $uuid = chr(123)// "{"
                . substr($charid, 0, 8) . $hyphen
                . substr($charid, 8, 4) . $hyphen
                . substr($charid, 12, 4) . $hyphen
                . substr($charid, 16, 4) . $hyphen
                . substr($charid, 20, 12)
                . chr(125); // "}"  
        return $uuid;
    }
}

//@time 2016年11月1日12:58:11
function tFilter(&$value){
    // 过滤查询特殊字符
    if(preg_match('/^(EXP|NEQ|GT|EGT|LT|ELT|OR|XOR|LIKE|NOTLIKE|NOT BETWEEN|NOTBETWEEN|BETWEEN|NOTIN|NOT IN|IN)$/i',$value)){
        $value .= ' ';
    }
}

/**
 * @param string $message
 * @param array $context
 * @param string $channelName
 * @param string $logDir
 */
function logDebug($message, array $context = array(), $channelName = '', $logDir = '')
{
    \Core\Log::debug($message,$context,$channelName,$logDir);
}

/**
 * @param string $message
 * @param array $context
 * @param string $channelName
 * @param string $logDir
 */
function logInfo($message, array $context = array(), $channelName = '', $logDir = '')
{
    \Core\Log::info($message, $context, $channelName, $logDir);
}

function logNotice($message, array $context = array(), $channelName = '', $logDir = '')
{
    \Core\Log::notice($message,$context,$channelName,$logDir);
}

function logWarning($message, array $context = array(), $channelName = '', $logDir = '')
{
    \Core\Log::warning($message,$context,$channelName,$logDir);
}

function logError($message, array $context = array(), $channelName = '', $logDir = '')
{
    \Core\Log::error($message,$context,$channelName,$logDir);
}

function logCritical($message, array $context = array(), $channelName = '', $logDir = '')
{
    \Core\Log::critical($message,$context,$channelName,$logDir);
}

function logEmergency($message, array $context = array(), $channelName = '', $logDir = '')
{
    \Core\Log::emergency($message,$context,$channelName,$logDir);
}

/**
 * XML编码
 * @param mixed $data 数据
 * @param string $root 根节点名
 * @param string $item 数字索引的子节点名
 * @param string $attr 根节点属性
 * @param string $id   数字索引子节点key转换的属性名
 * @param string $encoding 数据编码
 * @return string
 */
function xml_encode($data, $root='yaf', $item='item', $attr='', $id='id', $encoding='utf-8') {
    if(is_array($attr)){
        $_attr = array();
        foreach ($attr as $key => $value) {
            $_attr[] = "{$key}=\"{$value}\"";
        }
        $attr = implode(' ', $_attr);
    }
    $attr   = trim($attr);
    $attr   = empty($attr) ? '' : " {$attr}";
    $xml    = "<?xml version=\"1.0\" encoding=\"{$encoding}\"?>";
    $xml   .= "<{$root}{$attr}>";
    $xml   .= data_to_xml($data, $item, $id);
    $xml   .= "</{$root}>";
    return $xml;
}

/**
 * 数据XML编码
 * @param mixed  $data 数据
 * @param string $item 数字索引时的节点名称
 * @param string $id   数字索引key转换为的属性名
 * @return string
 */
function data_to_xml($data, $item='item', $id='id') {
    $xml = $attr = '';
    foreach ($data as $key => $val) {
        if(is_numeric($key)){
            $id && $attr = " {$id}=\"{$key}\"";
            $key  = $item;
        }
        $xml    .=  "<{$key}{$attr}>";
        $xml    .=  (is_array($val) || is_object($val)) ? data_to_xml($val, $item, $id) : $val;
        $xml    .=  "</{$key}>";
    }
    return $xml;
}

function isGet()
{
    return REQUEST_METHOD === 'GET' ? true : false;
}

function isPost()
{
    return REQUEST_METHOD === 'POST' ? true : false;
}

function isXmlHttpRequest()
{
    return REQUEST_METHOD === 'XMLHTTPREQUEST' ? true : false;
}

function isCli()
{
    return REQUEST_METHOD === 'CLI' ? true : false;
}

function isPut()
{
    return REQUEST_METHOD === 'PUT' ? true : false;
}

function isOptions()
{
    return REQUEST_METHOD === 'OPTIONS' ? true : false;
}

function isDelete()
{
    return REQUEST_METHOD === 'DELETE' ? true : false;
}

function autoload($class)
{
    if (strtoupper($class) == 'CORE\MODEL') {
        include LIBRARY_PATH.'/Core/Model.php';
    }
    return;
}
spl_autoload_register('autoload',true,true);