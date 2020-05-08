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
    const UPDATE_LOCAL_TRANSACTION_STATUS_ERROR = 40006;
    const COMMIT_LOCAL_TRANSACTION_ERROR = 40007;
    const ROLLBACK_LOCAL_TRANSACTION_ERROR = 40008;
    const UNDO_MISS_TABLE_ERROR = 40009;
    const UNDO_MISS_PRIMARY_KEY_ERROR = 40010;
    const UNDO_MISS_PRIMARY_VALUE_ERROR = 40011;
    const UNDO_INSERT_ERROR = 40012;
    const DONT_SUPPORT_SQL_TYPE = 40013;
    const UNDO_UPDATE_MISS_COLS_OR_BEFORE = 40014;
    const UNDO_UPDATE_COLS_AND_BEFORE_NOT_MATCH = 40015;
    const UNDO_UPDATE_ERROR = 40016;
    const UNDO_DELETE_ERROR = 40017;
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
        //更新事务状态失败
        self::UPDATE_LOCAL_TRANSACTION_STATUS_ERROR => 'update local transaction status error.',
        //本地事务提交失败
        self::COMMIT_LOCAL_TRANSACTION_ERROR => 'commit transaction fail.',
        //本地事务回滚失败
        self::ROLLBACK_LOCAL_TRANSACTION_ERROR => 'rollback transaction fail.',
        //本地事务回滚缺失table
        self::UNDO_MISS_TABLE_ERROR => 'try undo local transaction miss table.',
        //本地事务回滚缺失主键key
        self::UNDO_MISS_PRIMARY_KEY_ERROR => 'try undo local transaction miss primary key.',
        //本地事务回滚缺失主键值
        self::UNDO_MISS_PRIMARY_VALUE_ERROR => 'try undo local transaction miss primary value.',
        //回滚insert失败
        self::UNDO_INSERT_ERROR => 'undo insert fail.',
        //不支持的sql类型
        self::DONT_SUPPORT_SQL_TYPE => 'dont support sql type.',
        //回滚update缺失列和before
        self::UNDO_UPDATE_MISS_COLS_OR_BEFORE => 'try undo local transaction miss cols or before.',
        //回滚update列和before不是一一对应的
        self::UNDO_UPDATE_COLS_AND_BEFORE_NOT_MATCH => 'try undo local transaction miss cols or before.',
        //回滚update失败
        self::UNDO_UPDATE_ERROR => 'undo update error.',
        //回滚delete失败
        self::UNDO_DELETE_ERROR => 'undo delete error.',
    ];
    public function __construct($code)
    {
        $message = 'unknow exception';
        if (isset(self::ERROR_MESSAGES[$code]))
            $message = self::ERROR_MESSAGES[$code];
        parent::__construct($message, $code, null);
    }
}