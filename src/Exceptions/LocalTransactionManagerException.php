<?php
/**
 * @user: ligongxiang (ligongxiang@rouchi.com)
 * @date : 2020/5/5
 * @version : 1.0
 * @file : LocalTransactionManagerException.php
 * @desc :
 */

namespace ResourceManager\Exceptions;

class LocalTransactionManagerException extends \Exception
{
    const DONT_HAVE_ACTIVE_LOCAL_TRANSACTION = 30000;
    const ALREADY_HAVE_ACTIVE_LOCAL_TRANSACTION = 30001;
    const ERROR_MESSAGES = [
        //没有激活中的本地事务
        self::DONT_HAVE_ACTIVE_LOCAL_TRANSACTION => 'none active local transaction.',
        //已有激活中的本地事务
        self::ALREADY_HAVE_ACTIVE_LOCAL_TRANSACTION => 'already have active local transaction.',
    ];
    public function __construct($code)
    {
        $message = 'unknow exception';
        if (isset(self::ERROR_MESSAGES[$code]))
            $message = self::ERROR_MESSAGES[$code];
        parent::__construct($message, $code, null);
    }
}