<?php
/**
 * FileName： Error.php
 * Author: solely
 * DateTime: 2016/11/1 11:36
 * Description: 错误控制器, 在发生未捕获的异常时刻被调用
 */

class ErrorController extends \Yaf\Controller_Abstract
{
    //从2.1开始, errorAction支持直接通过参数获取异常
    public function errorAction($exception)
    {
        if (DEBUG_MODE) {
            dump($exception->getMessage());
            dump($exception);
        }
        logError($exception->getMessage(), $exception->getTrace());
        return false;
    }
}