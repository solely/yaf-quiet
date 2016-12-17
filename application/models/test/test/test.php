<?php
/**
 * Created by PhpStorm.
 * User: solely
 * Date: 2016/12/1
 * Time: 14:21
 */
namespace test\test;

use Core\Model;

class testModel extends Model
{
    public function getList()
    {
        return $this->select();
    }
}