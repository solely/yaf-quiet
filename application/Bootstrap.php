<?php
/**
 * FileName： Bootstrap.php
 * Author: solely
 * DateTime: 2016/10/31 18:23
 * Description:     所有在Bootstrap类中, 以_init开头的方法, 都会被Yaf调用,
 *                  这些方法, 都接受一个参数:Yaf_Dispatcher $dispatcher
 *                  调用的次序, 和申明的次序相同
 */

class Bootstrap extends \Yaf\Bootstrap_Abstract
{
    protected $config;

    public function _initConfig(\Yaf\Dispatcher $dispatcher)
    {
        $this->config = \Yaf\Application::app()->getConfig();
        \Yaf\Registry::set('config',$this->config);
        define('REQUEST_METHOD', strtoupper($dispatcher->getRequest()->getMethod()));
    }

    public function _initError(\Yaf\Dispatcher $dispatcher)
    {
        if ($this->config->application->debug) {
            define('DEBUG_MODE', true);
            ini_set('display_errors', 'On');
        } else {
            define('DEBUG_MODE', false);
            ini_set('display_errors', 'Off');
        }
    }

    public function _initCommon(\Yaf\Dispatcher $dispatcher)
    {
        \Yaf\Loader::import(LIBRARY_PATH."/functions.php");
        \Yaf\Loader::import(VENDOR_PATH."/autoload.php");
    }

    public function _initLocalName()
    {
        \Yaf\Loader::getInstance()->registerLocalNamespace(['Smarty','Core']);
    }

    public function _initPlugin(\Yaf\Dispatcher $dispatcher)
    {
        $urlSuffix = new SystemPlugin();
        $dispatcher->registerPlugin($urlSuffix);
    }

    public function _initRoute(\Yaf\Dispatcher $dispatcher)
    {
        $routes = $this->config->routes;
        if (!empty($routes)) {
            $router = $dispatcher->getRouter();
            $router->addConfig($routes);
        }
    }

    public function _initView(\Yaf\Dispatcher $dispatcher)
    {
        if (REQUEST_METHOD != 'CLI') {
            $smarty = \Smarty\Adapter::getInstance()->init(null,$this->config->smarty);
            $dispatcher->setView($smarty);
        }
    }
}