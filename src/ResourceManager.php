<?php
/**
 * @user: ligongxiang (ligongxiang@rouchi.com)
 * @date : 2020/4/24
 * @version : 1.0
 * @file : ResourceManager.php
 * @desc :
 */

namespace ResourceManager;

/**
 * 资源管理器，单例
 * */
class ResourceManager
{
    /**
     * 分析器，单例
     * */
    protected $_analyser = null;

    /**
     * 本地事务管理器，单例
     * */
    protected $_localTransactionManager = null;

    /**
     * DB代理，单例
     * */
    protected $_dbProxy = null;

    protected static $_instance = null;

    protected function __construct()
    {
        $this->_analyser = null;
        $this->_localTransactionManager = LocalTransactionManager::getInstance();
        $this->_dbProxy = null;
    }

    /**
     * 获取RM实例
     * */
    public static function getInstance()
    {
        if (self::$_instance === null)
            self::$_instance = new self();
        return self::$_instance;
    }

    /**
     * 开启一个本地事务.
     *  此方法只是对外的一个接口，实际执行调用本地事务管理器注册一个本地事务.
     * @param string $sign 连接的唯一签名
     * @param string $desc 一句话描述
     * @param string $tid 全局事务id
     * @throws Exceptions\MysqlTransactionException
     */
    public function start(string $sign,string $desc = '',string $tid = '')
    {
        $this->_localTransactionManager->register($sign,$desc,$tid);
    }

    /**
     * 执行sql
     * @param string $sql SQL语句
     */
    public function do(string $sql)
    {

    }

    /**
     * 提交一个本地事务
     * */
    public function commit()
    {

    }

    /**
     * 回滚一个本地事务
     * */
    public function rollback()
    {

    }
}