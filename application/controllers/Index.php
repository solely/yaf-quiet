<?php
/**
 * FileName： Index.php
 * Author: solely
 * DateTime: 2016/11/1 11:46
 * Description:
 */

use Core\Log;

class IndexController extends AbstractController
{
    /**
     * 默认动作
     * Yaf支持直接把Yaf_Request_Abstract::getParam()得到的同名参数作为Action的形参
     * 对于如下的例子, 当访问http://yourhost/Sample/index/index/index/name/root 的时候, 你就会发现不同
     */
    public function indexAction()
    {
        echo 'hello world!';
        dump($_GET);
        dump($this->getRequest()->getParams());
        return false;
    }

    public function testViewAction()
    {
        $this->getView()->assign('title', '测试模板以及get提交');
        return true;
    }

    public function testLogAction()
    {
        echo '测试日志记录';
        Log::debug('测试日志揭露');
        Log::debug('测试日志揭露1');
        Log::debug('测试日志揭露2');
        Log::debug('测试日志揭露3');
        Log::warning('测试warning', array('warning' => 'warn'));
        Log::warning('测试warning2', array('warning' => 'warn2', 'second' => 'secondWarning'));
        Log::error('测试warning2', array('error' => 'error', 'second' => 'secondError'));
        Log::error('测试warning', array('error' => 'error', 'second' => 'secondError'));
        Log::error('测试warning', array('error' => 'error', 'second' => 'secondError'));
        Log::critical('测试critical，严重错误', array('error' => 'error', 'second' => 'secondError'));
        Log::critical('测试critical，严重错误', array('error' => 'error', 'second' => '严重错误'));
        Log::emergency('测试emergency，系统不可用', array('error' => 'error', 'second' => '系统不可用'));
        logDebug('测试debug',array('debug'=>'debug'));
        logDebug('测试debug',array('debug'=>'debug'),'db');
        logInfo('测试info',array('info'=>'info'));
        logInfo('测试info',array('info'=>'info'),'db');
        logNotice('测试notice',array('notice'=>'notice'));
        logNotice('测试notice',array('notice'=>'notice'),'db');
        logWarning('测试warning',array('warning'=>'warning'));
        logWarning('测试warning',array('warning'=>'warning'),'db');
        logError('测试error',array('error'=>'error'));
        logError('测试error',array('error'=>'error'),'db');
        logCritical('测试critical',array('critical'=>'critical'));
        logCritical('测试critical',array('critical'=>'critical'),'db');
        logEmergency('测试emergency',array('emergency'=>'emergency'));
        logEmergency('测试emergency',array('emergency'=>'emergency'),'db');
        return false;
    }

    public function testResponseAction()
    {
        $response = $this->getResponse();
        $response->setHeader('Content-Type','application/json;charset=utf-8');
        $response->setBody('Hello')->setBody('world','footer');
        return false;
    }

    public function testResponseJsonAction()
    {
        $this->responseJson(['test'=>'a','aaa'=>'bbb','ccc'=>'dddd','debug'=>DEBUG_MODE]);

    }

    public function testResponseRequestAction()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();

        dump($request);
        dump($response);
        dump($_GET);
        dump($request->getParams());
        return false;
    }

    public function testGetAction($name = '测试')
    {
        if ($this->getRequest()->isGet()) {
            dump($this->getRequest());
            dump($this->getRequest()->getParams());
            dump($_GET);
        }
        $this->getView()->assign('title',$name.'提交');
        dump($this->getView()->getScriptPath());
        return true;
    }

    public function testGet1Action()
    {
        $this->getView()->assign('title','测试get提交');
        dump('11');
        return true;
    }

    public function testRRAction()
    {
        $this->responseJson([
            'isGet' => isGet(),
            'isPost'=> isPost(),
            'isPut' => isPut(),
            'isXmlHttpRequest' => isXmlHttpRequest(),
            'method' => REQUEST_METHOD,
        ]);
    }

    public function testPostAction()
    {
        if (isPost()) {
            dump($this->getRequest()->getParams());
            dump($this->getRequest());
            dump($_POST);
        }
        return false;
    }

    public function testModelAction()
    {
        $test = new \test\test\testModel();
        dump($test->getList());
        dump(get_included_files());
        return false;
    }
}