<?php
/**
 * FileName： System.php
 * Author: solely
 * DateTime: 2016/10/31 18:44
 * Description:
 */
class SystemPlugin extends \Yaf\Plugin_Abstract
{
    public function routerStartup(\Yaf\Request_Abstract $request, \Yaf\Response_Abstract $response)
    {
        $urlSuffix = \Yaf\Registry::get('config')->application->url_suffix;
        $requestUri = $request->getRequestUri();
        if ($urlSuffix) {
            if (strtolower(substr($requestUri, -strlen($urlSuffix))) == strtolower($urlSuffix)) {
                $request->setRequestUri(substr($requestUri, 0, - strlen($urlSuffix)));
            }
        }
    }

    public function preDispatch(\Yaf\Request_Abstract $request, \Yaf\Response_Abstract $response)
    {
        $moduleName = $request->getModuleName();
        $viewPath = \Yaf\Registry::get('config')->smarty->template_dir;

        if (strtoupper($moduleName) !== 'INDEX') {
            $viewPath = APPLICATION_PATH.DS.'modules'.DS.$moduleName.DS.'views';
        }
        \Smarty\Adapter::getInstance()->setScriptPath($viewPath);
    }
}