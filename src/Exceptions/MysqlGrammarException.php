<?php
/**
 * @user: ligongxiang (ligongxiang@rouchi.com)
 * @date : 2020/4/28
 * @version : 1.0
 * @file : MysqlGrammarException.php
 * @desc :
 */

namespace ResourceManager\Exceptions;


class MysqlGrammarException extends \Exception
{
    public function __construct($code, $message)
    {
        parent::__construct($message, $code, null);
    }
}