<?php
/**
 * @user: ligongxiang (ligongxiang@rouchi.com)
 * @date : 2020/5/5
 * @version : 1.0
 * @file : MysqlTransactionException.php
 * @desc :
 */

namespace ResourceManager\Exceptions;


class MysqlTransactionException extends \Exception
{
    const INSERT_LOCAL_TRANSACTION_ERROR = 40000;
    const BEGIN_LOCAL_TRANSACTION_ERROR = 40001;
    const INSERT_UNDO_ERROR = 40002;
    const BUILD_BEFORE_ERROR = 40003;
    const TABLE_DONT_HAVE_PRIMARY_KEY_ID = 40004;
    const CONT_FIND_MATCH_PRIMARY_VALUE = 40005;
    const ERROR_MESSAGES = [
        //插入本地事务库失败
        self::INSERT_LOCAL_TRANSACTION_ERROR => 'insert transaction local fail.',
        //开启事务失败
        self::BEGIN_LOCAL_TRANSACTION_ERROR => 'begin local transaction fail.',
        //插入undo失败
        self::INSERT_UNDO_ERROR => 'insert undo fail.',
        //生成before语句为空
        self::BUILD_BEFORE_ERROR => 'build before fail.',
        //表不包含主键id
        self::TABLE_DONT_HAVE_PRIMARY_KEY_ID => 'table dont have primary key id.',
        //找不到对应的主键id
        self::CONT_FIND_MATCH_PRIMARY_VALUE => 'cont find match primary value.',
    ];
    public function __construct($code)
    {
        $message = 'unknow exception';
        if (isset(self::ERROR_MESSAGES[$code]))
            $message = self::ERROR_MESSAGES[$code];
        parent::__construct($message, $code, null);
    }
}