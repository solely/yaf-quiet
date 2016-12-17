<?php
/**
 * FileName： Adapter.php
 * Author: solely
 * DateTime: 2016/11/1 0:25
 * Description:     smarty模板引擎
 *
 * there are some config for smarty in the config:
 *
 * smarty.left_delimiter   = "{{"
 * smarty.right_delimiter  = "}}"
 * smarty.template_dir     = APPLICATION_PATH "/views/scripts/"
 * smarty.compile_dir      = APPLICATION_PATH "/views/templates_c/"
 * smarty.cache_dir        = APPLICATION_PATH "/views/templates_d/"
 *
 */
namespace Smarty;

use Yaf\Exception;

class Adapter implements \Yaf\View_Interface
{
    public $_smarty;
    private static $instance;

    public function __construct()
    {
        $this->_smarty = new \Smarty();
    }

    public function init($tmplPath = null, $extraParams = array())
    {
        try {
            if (null !== $tmplPath) {
                $this->setScriptPath($tmplPath);
            }

            if (!empty($extraParams)) {
                foreach ($extraParams as $key => $value) {
                    $this->_smarty->$key = $value;
                }
            }
            return $this;
        } catch (Exception $e) {
            throw new Exception($e->getTraceAsString());
        };
    }

    public function setScriptPath($template_dir)
    {
        if (is_readable($template_dir)) {
            $this->_smarty->setTemplateDir($template_dir);
            return;
        }

        throw new Exception('Invalid smarty template_dir provided');
    }

    /**
     * 獲取模板位置
     * @time 2016年11月1日00:37:03
     */
    public function getScriptPath()
    {
        return $this->_smarty->getTemplateDir();
    }

    /**
     * @param $name
     * @param $value
     * @time 2016年11月1日00:45:10
     */
    public function __set($name, $value)
    {
        $this->_smarty->assign($name, $value);
    }

    public function __isset($name)
    {
        return (null !== $this->_smarty->getTemplateVars($name));
    }

    public function __unset($name)
    {
        $this->_smarty->clearAssign($name);
    }

    public function assign($spec, $value = null)
    {
        if (is_array($spec)) {
            $this->_smarty->assign($spec);
            return;
        }

        $this->_smarty->assign($spec, $value);
    }

    public function clearVars()
    {
        $this->_smarty->clearAllAssign();
    }

    public function render($tpl, $tpl_vars = null)
    {
        $this->_smarty->display($tpl);
    }

    public function display($tpl, $tpl_vars = null)
    {
        echo $this->_smarty->display($tpl);
    }

    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}