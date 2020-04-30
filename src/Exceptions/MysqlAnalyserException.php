<?php
/**
 * @user: ligongxiang (ligongxiang@rouchi.com)
 * @date : 2020/4/28
 * @version : 1.0
 * @file : MysqlAnalyserException.php
 * @desc :
 */

namespace ResourceManager\Exceptions;

class MysqlAnalyserException extends \Exception
{
    public function __construct($code, $message)
    {
        parent::__construct($message, $code, null);
    }
}