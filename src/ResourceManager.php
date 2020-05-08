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
    //todo:变更连接/配置处理方式
    const HOST = '';
    const UNAME = '';
    const PASS = '';
    const DBNAME = '';
    const PORT = '';
    const CHARSET = '';

    /**
     * 本地事务管理器，单例
     * */
    protected $_localTransactionManager = null;

    protected static $_instance = null;

    protected function __construct()
    {
        $this->_localTransactionManager = LocalTransactionManager::getInstance(self::HOST,self::UNAME,self::PASS,self::DBNAME);
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
     * @param string $desc 一句话描述
     * @param string $tid 全局事务id
     * @throws Exceptions\MysqlTransactionException
     * @throws Exceptions\LocalTransactionManagerException
     */
    public function begin(string $desc = '',string $tid = '')
    {
        $this->_localTransactionManager->begin($desc,$tid);
    }

    /**
     * 执行sql
     * @param string $sql SQL语句
     * @throws Exceptions\MysqlGrammarException
     * @throws Exceptions\MysqlTransactionException
     */
    public function do(string $sql)
    {
        $this->_localTransactionManager->do($sql);
    }

    /**
     * 提交一个本地事务
     *
     * @throws Exceptions\MysqlTransactionException
     */
    public function commit()
    {
        $this->_localTransactionManager->commit();
    }

    /**
     * 回滚一个本地事务
     *
     * @throws Exceptions\MysqlTransactionException
     */
    public function rollback()
    {
        $this->_localTransactionManager->rollback();
    }

    /**
     * 全局回滚方法
     *
     * @throws Exceptions\MysqlTransactionException
     */
    public function grollback()
    {
        $this->_localTransactionManager->grollback();
    }
}