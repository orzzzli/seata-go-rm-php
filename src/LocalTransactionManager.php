<?php
/**
 * @user: ligongxiang (ligongxiang@rouchi.com)
 * @date : 2020/5/5
 * @version : 1.0
 * @file : LocalTransactionManager.php
 * @desc :
 */

namespace ResourceManager;

use ResourceManager\Connector\Mysql\MysqlConnector;
use ResourceManager\Exceptions\LocalTransactionManagerException;
use ResourceManager\Exceptions\MysqlGrammarException;
use ResourceManager\Exceptions\MysqlTransactionException;
use ResourceManager\Grammar\Mysql\MysqlGrammar;
use ResourceManager\Grammar\Mysql\SQLStruct;
use ResourceManager\LocalTransactions\MysqlTransaction;

/**
 * 本地事务管理器，单例
 * */
class LocalTransactionManager
{
    /**
     * 当前激活的事务
     *  MysqlTransaction实例
     * */
    protected static $_active = null;
    /**
     * 当前使用的mysql连接
     *  理论上单次请求中无需切换请求。
     *  慎用切换，连接不同很难保证事务的正确执行。
     * todo:优雅的处理连接切换问题
     * */
    protected static $_connector = null;

    protected static $_instance = null;
    protected function __construct(string $host,string $username,string $pass,string $dbname,string $port = '3306',string $charset = 'utf8')
    {
        self::$_connector = new MysqlConnector($host,$username,$pass,$dbname,$port,$charset);
    }

    /**
     * 获取管理器实例
     * @param string $host host
     * @param string $username  用户名
     * @param string $pass  密码
     * @param string $dbname    库名
     * @param string $port  端口，默认3306
     * @param string $charset   字符集，默认utf8
     * @return LocalTransactionManager|null
     */
    public static function getInstance(string $host,string $username,string $pass,string $dbname,string $port = '3306',string $charset = 'utf8')
    {
        if (self::$_instance === null)
            self::$_instance = new self($host,$username,$pass,$dbname,$port,$charset);
        return self::$_instance;
    }

    /**
     * 变更连接器
     * @param MysqlConnector $mysqlConnector
     */
    public function changeConnector(MysqlConnector $mysqlConnector)
    {
        self::$_connector = $mysqlConnector;
    }

    /**
     * 注册本地事务.
     *  如果已经有一个激活中的本地事务，报错。
     *  否则注册一个本地事务。
     * @param string $desc 一句话描述
     * @param string $tid 全局事务id
     * @throws Exceptions\MysqlTransactionException
     * @throws LocalTransactionManagerException
     */
    public function begin(string $desc = '',string $tid = '')
    {
        if (self::$_active !== null)
            throw new LocalTransactionManagerException(LocalTransactionManagerException::ALREADY_HAVE_ACTIVE_LOCAL_TRANSACTION);
        $transaction = new MysqlTransaction(self::$_connector,$desc,$tid);
        $transaction->start();
        self::$_active = $transaction;
    }

    /**
     * 使用当前激活的本地事务执行sql
     * @param string $sql sql语句
     * @return array|false|int sql结果
     * @throws MysqlTransactionException
     * @throws MysqlGrammarException
     */
    public function do(string $sql)
    {
        //直接执行语句
        if (self::$_active === null) {
            $sqlType = MysqlGrammar::getSqlTypeConst($sql);
            if ($sqlType === SQLStruct::SQL_TYPE_SELECT)
                return self::$_connector->query($sql);
            if ($sqlType === SQLStruct::SQL_TYPE_INSERT)
                return self::$_connector->insert($sql);
            if ($sqlType === SQLStruct::SQL_TYPE_UPDATE)
                return self::$_connector->update($sql);
            if ($sqlType === SQLStruct::SQL_TYPE_DELETE)
                return self::$_connector->delete($sql);
            return false;
        }
        return self::$_active->doing($sql);
    }

    /**
     * 提交
     *
     * @throws MysqlTransactionException
     * */
    public function commit()
    {
        if (self::$_active === null)
            return;
        self::$_active->commit();
        self::$_active = null;
    }

    /**
     * 回滚
     *
     * @throws MysqlTransactionException
     * */
    public function rollback()
    {
        if (self::$_active === null)
            return;
        self::$_active->rollback();
        self::$_active = null;
    }

    /**
     * 全局回滚
     *
     *  a.倒序查出该全局事务包含的本地事务
     *  b.依次根据undo构建语句回滚
     * @param string $tid 待回滚的全局事务
     * @throws LocalTransactionManagerException
     */
    public function grollback(string $tid)
    {
        if (empty($tid)) {
            return;
        }
        $undoSQL = $this->buildSelectUndoSQL($tid);
        $res = self::$_connector->query($undoSQL);
        foreach ($res as $undo) {
            $sqlType = $undo['type'] ?? SQLStruct::SQL_TYPE_UNKNOW;
            $table = $undo['table'] ?? '';
            if (empty($table))
                throw new LocalTransactionManagerException(LocalTransactionManagerException::UNDO_MISS_TABLE_ERROR);
            $primaryK = $undo['primary_key'] ?? '';
            if (empty($table))
                throw new LocalTransactionManagerException(LocalTransactionManagerException::UNDO_MISS_PRIMARY_KEY_ERROR);
            $primaryV = $undo['primary_value'] ?? '';
            if (empty($table))
                throw new LocalTransactionManagerException(LocalTransactionManagerException::UNDO_MISS_PRIMARY_VALUE_ERROR);
            if ($sqlType == SQLStruct::SQL_TYPE_INSERT) {
                $sql = $this->buildUndoInsertSQL($table,$primaryK,$primaryV);
                $count = self::$_connector->delete($sql);
                if (empty($count))
                    throw new LocalTransactionManagerException(LocalTransactionManagerException::UNDO_INSERT_ERROR);
            }
            $cols = $undo['cols'] ?? '';
            $before = $undo['before'] ?? '';
            if ($sqlType == SQLStruct::SQL_TYPE_UPDATE) {
                if (empty($cols) || empty($before))
                    throw new LocalTransactionManagerException(LocalTransactionManagerException::UNDO_UPDATE_MISS_COLS_OR_BEFORE);
                $colList = explode(',',$cols);
                $beforeList = explode(',',$before);
                if (count($colList) != count($beforeList))
                    throw new LocalTransactionManagerException(LocalTransactionManagerException::UNDO_UPDATE_MISS_COLS_OR_BEFORE);
                $sql = $this->buildUndoUpdateSQL($table,$colList,$beforeList,$primaryK,$primaryV);
                $count = self::$_connector->update($sql);
                if (empty($count))
                    throw new LocalTransactionManagerException(LocalTransactionManagerException::UNDO_UPDATE_ERROR);
            }
            if ($sqlType == SQLStruct::SQL_TYPE_DELETE) {
                if (empty($cols) || empty($before))
                    throw new LocalTransactionManagerException(LocalTransactionManagerException::UNDO_UPDATE_MISS_COLS_OR_BEFORE);
                $colList = explode(',',$cols);
                $beforeList = explode(',',$before);
                if (count($colList) != count($beforeList))
                    throw new LocalTransactionManagerException(LocalTransactionManagerException::UNDO_UPDATE_MISS_COLS_OR_BEFORE);
                $sql = $this->buildUndoDeleteSQL($table,$colList,$beforeList,$primaryK,$primaryV);
                list($count,$lastInsertId) = self::$_connector->insert($sql);
                if (empty($count))
                    throw new LocalTransactionManagerException(LocalTransactionManagerException::UNDO_DELETE_ERROR);
            }
        }
    }

    /**
     * 工具方法，拼select语句
     * @param string $tid 全局事务id
     * @return string update语句
     */
    protected function buildSelectUndoSQL(string $tid)
    {
        $selectSQL = 'SELECT * FROM `%s` WHERE tid = \'%s\' order by id desc';
        return sprintf($selectSQL,MysqlTransaction::TABLE_UNDO,$tid);
    }

    /**
     * 工具方法，undoInsert语句，delete语句
     * @param string $table 表名
     * @param string $primaryK 主键
     * @param string $primaryV 主键值
     * @return string 语句
     */
    protected function buildUndoInsertSQL(string $table,string $primaryK,string $primaryV)
    {
        $sql = 'DELETE FROM `%s` WHERE %s="%s"';
        return sprintf($sql,$table,$primaryK,$primaryV);
    }

    /**
     * 工具方法，undoUpdate语句，update语句
     * @param string $table 表名
     * @param array $cols 列名list
     * @param array $befores 变更前list
     * @param string $primaryK 主键
     * @param string $primaryV 主键值
     * @return string 语句
     */
    protected function buildUndoUpdateSQL(string $table,array $cols,array $befores,string $primaryK,string $primaryV)
    {
        $setStr = '';
        foreach ($cols as $k => $v) {
            $setStr .= $v.'="'.$befores[$k].'"';
            if ($k != count($cols) - 1)
                $setStr .= ',';
        }
        $sql = 'UPDATE `%s` SET %s WHERE %s = "%s"';
        return sprintf($sql,$table,$setStr,$primaryK,$primaryV);
    }

    /**
     * 工具方法，undoDelete语句，insert语句
     * @param string $table 表名
     * @param array $cols 列名list
     * @param array $befores 变更前list
     * @param string $primaryK 主键
     * @param string $primaryV 主键值
     * @return string 语句
     */
    protected function buildUndoDeleteSQL(string $table,array $cols,array $befores,string $primaryK,string $primaryV)
    {
        array_push($cols,$primaryK);
        array_push($befores,$primaryV);
        $colStr = '';
        foreach ($cols as $k => $col) {
            $colStr .= '`'.$col.'`';
            if ($k != count($cols) - 1)
                $colStr .= ',';
        }
        $valueStr = '';
        foreach ($befores as $k => $col) {
            $valueStr .= '"'.$col.'"';
            if ($k != count($befores) - 1)
                $valueStr .= ',';
        }
        $sql = 'INSERT INTO `%s` (%s) VALUE (%s)';
        return sprintf($sql,$table,$colStr,$valueStr);
    }
}