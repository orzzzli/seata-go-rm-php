<?php
/**
 * @user: ligongxiang (ligongxiang@rouchi.com)
 * @date : 2020/5/5
 * @version : 1.0
 * @file : LocalTransactionManager.php
 * @desc :
 */

namespace ResourceManager;

use ResourceManager\Exceptions\LocalTransactionManagerException;
use ResourceManager\LocalTransactions\MysqlTransaction;

/**
 * 本地事务管理器，单例
 * */
class LocalTransactionManager
{
    protected static $_active = null;

    protected static $_instance = null;
    protected function __construct()
    {
    }

    /**
     * 获取管理器实例
     * */
    public static function getInstance()
    {
        if (self::$_instance === null)
            self::$_instance = new self();
        return self::$_instance;
    }

    /**
     * 注册本地事务
     * @param string $sign 连接的唯一签名
     * @param string $desc 一句话描述
     * @param string $tid 全局事务id
     * @throws Exceptions\MysqlTransactionException
     * todo:处理active已经存在的情况
     */
    public function register(string $sign,string $desc = '',string $tid = '')
    {
        if (self::$_active === null) {
            $transaction = new MysqlTransaction(null,$sign,$desc,$tid);
            $transaction->start();
            self::$_active = $transaction;
        }
    }

    /**
     * 使用当前激活的本地事务执行sql
     * @param string $sql sql语句
     * @throws LocalTransactionManagerException
     */
    public function do(string $sql)
    {
        if (self::$_active === null)
            throw new LocalTransactionManagerException(LocalTransactionManagerException::DONT_HAVE_ACTIVE_LOCAL_TRANSACTION);
        self::$_active->doing($sql);
    }
}