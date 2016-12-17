<?php

/**
 * FileName： Abstract.php
 * Author: solely
 * DateTime: 2016/11/1 11:28
 * Description: 公共控制器
 */
abstract class AbstractController extends \Yaf\Controller_Abstract
{
    /*
     * 初始化操作
     */
    public function init()
    {
        $URL['__ROOT__'] = __ROOT__;
        $URL['__PUBLIC__'] = __PUBLIC__;
        $this->getView()->assign("URL", $URL);
    }

    /**
     * @param string $data
     * @param array $header
     */
    public function responseSend($data = '', array $header = array())
    {
        $response = $this->getResponse();
        if (!empty($header)) {
            $headerKeys = array_keys($header);
            foreach ($headerKeys as $value) {
                $response->setHeader($value, $header[$value]);
            }
        }
        $response->setBody($data);
        $response->response();
        exit;
    }

    public function responseJson($data, array $header = array(), $charset = 'utf-8')
    {
        $header = array_merge($header, array('Content-Type' => 'application/json;charset=' . $charset));
        $this->responseSend(json_encode($data), $header);
    }

    public function responseJsonp($data, array $header = array(), $callbackFucParam = 'callback', $charset = 'utf-8')
    {
        $header = array_merge($header, array('Content-Type' => 'application/json; charset=' . $charset));
        $this->responseSend(getParamsVal($callbackFucParam) . '(' . json_encode($data) . ')', $header);
    }

    public function responseXml($data, array $header = array(), $charset = 'utf-8')
    {
        $header = array_merge($header, array('Content-type' => 'text/xml;charset=' . $charset));
        $this->responseSend(xml_encode($data), $header);
    }

}