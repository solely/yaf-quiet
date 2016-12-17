<?php
/**
 * FileName： Index.php
 * Author: solely
 * DateTime: 2016/12/4 18:08
 * Description:
 */
class IndexController extends AbstractController
{
    public function indexAction()
    {
        $this->responseSend('<h2 style="text-align: center">hello,this is test modules index action.</h2>');
    }

    public function testViewAction()
    {
        dump($this->getView()->getScriptPath());
        dump('222');
        $this->getView()->assign('title','test模块index控制器test方法');
        return true;
    }
}