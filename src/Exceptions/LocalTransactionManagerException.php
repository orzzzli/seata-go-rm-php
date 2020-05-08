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
    const UNDO_MISS_TABLE_ERROR = 30002;
    const UNDO_MISS_PRIMARY_KEY_ERROR = 30003;
    const UNDO_MISS_PRIMARY_VALUE_ERROR = 30004;
    const UNDO_INSERT_ERROR = 30005;
    const DONT_SUPPORT_SQL_TYPE = 30006;
    const UNDO_UPDATE_MISS_COLS_OR_BEFORE = 30007;
    const UNDO_UPDATE_COLS_AND_BEFORE_NOT_MATCH = 30008;
    const UNDO_UPDATE_ERROR = 30009;
    const UNDO_DELETE_ERROR = 30010;
    const ERROR_MESSAGES = [
        //没有激活中的本地事务
        self::DONT_HAVE_ACTIVE_LOCAL_TRANSACTION => 'none active local transaction.',
        //已有激活中的本地事务
        self::ALREADY_HAVE_ACTIVE_LOCAL_TRANSACTION => 'already have active local transaction.',
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