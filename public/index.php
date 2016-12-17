<?php
/**
 * FileName： index.php
 * Author: solely
 * DateTime: 2016/10/8 11:43
 * Description:
 */
define('APP_PATH',realpath(dirname(__FILE__).'/../'));
define('APPLICATION_PATH',APP_PATH.'/application');
define('LIBRARY_PATH',APPLICATION_PATH.'/library');
define('RUNTIME_PATH',APPLICATION_PATH.'/runtime');
define('CONF_PATH',APP_PATH.'/conf');
define('PUBLIC_PATH',APP_PATH.'/public');
define('VENDOR_PATH',APP_PATH.'/vendor');
define('MEMORY_LIMIT_ON', true);//开启内存消耗提示，只有使用了G方法才有效果
define('DS', DIRECTORY_SEPARATOR);
define('__ROOT__', DS);
define('__PUBLIC__', __ROOT__);
define('__ASSETS__', __ROOT__.'assets/');
define('NOW_TIME',$_SERVER['REQUEST_TIME']);

$application = new \Yaf\Application(CONF_PATH.'/application.ini');

$application->bootstrap()->run();