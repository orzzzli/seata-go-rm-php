<?php
/**
 * @user: ligongxiang (ligongxiang@rouchi.com)
 * @date : 2020/5/8
 * @version : 1.0
 * @file : TransactionManager.php
 * @desc :
 */

namespace ResourceManager;


class TransactionManager
{
    protected static $_instance = null;
    protected function __construct()
    {
    }

    public static function getInstance()
    {
        if (self::$_instance === null)
            self::$_instance = new self();
        return self::$_instance;
    }

    public function gstart()
    {

    }
}