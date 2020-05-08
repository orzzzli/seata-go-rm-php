<?php
/**
 * @user: ligongxiang (ligongxiang@rouchi.com)
 * @date : 2020/4/24
 * @version : 1.0
 * @file : LocalTransaction.php
 * @desc :
 */

namespace ResourceManager\LocalTransactions;

use ResourceManager\Analysers\MysqlAnalyser;
use ResourceManager\Connector\Mysql\MysqlConnector;
use ResourceManager\Exceptions\MysqlTransactionException;
use ResourceManager\Grammar\Mysql\MysqlGrammar;
use ResourceManager\Grammar\Mysql\SQLStruct;
use ResourceManager\Exceptions\MysqlGrammarException;

class MysqlTransaction
{
    const STATUS_ACTIVE = 1;
    const STATUS_COMMIT = 2;
    const STATUS_ROLLBACK = 3;

    const TABLE_TRANSACTION = 'transaction_local';
    const TABLE_UNDO = 'transaction_undo';
    const PRIMARY_KEY = 'id';
    protected $_lastInsertId = 0;
    protected $_connection = null;
    protected $_analyser = null;

    protected $_tid = '';
    protected $_desc = '';
    protected $_status = self::STATUS_ACTIVE;
    public function __construct(MysqlConnector $connector,string $tid = '',string $desc = '')
    {
        $this->_desc = $desc;
        $this->_tid = $tid;

        $this->_connection = $connector;
        $this->_analyser = new MysqlAnalyser();
    }

    /**
     * 开启事务.
     *  a.开启本地事务.
     *  b.本地事务对象入库.
     *
     * @throws MysqlTransactionException mysql数据库错误
     */
    public function start()
    {
        $this->_connection->begin();
        //非全局事务
        if ($this->_tid === '')
            return;
        //本地事务对象入库.
        $insertSQL = $this->buildInsertSQL();
        list($count,$lastInsertId) = $this->_connection->insert($insertSQL);
        if (empty($count))
            throw new MysqlTransactionException(MysqlTransactionException::INSERT_LOCAL_TRANSACTION_ERROR);
        $this->_lastInsertId = $lastInsertId;
    }

    /**
     * 执行SQL.
     *  a.分析SQL获得SQL结构
     *  b.构造beforeImage
     *  c.执行sql
     *  d.构造afterImage
     *  e.插入undoLog
     * @param string $sql sql语句
     * @return array|false|int SQL执行结果
     * @throws MysqlTransactionException
     * @throws MysqlGrammarException
     */
    public function doing(string $sql)
    {
        //分析sql
        $sqlStruct = $this->_analyser->analyse($sql);
        //非全局事务直接执行
        if ($this->_tid === '') {
            return $this->doSQLToDB($sqlStruct->getSqlType(),$sqlStruct->getOriginSql());
        }
        $primaryV = array();
        $cols = array();
        $before = array();
        $after = array();
        //判断sql类型，构造beforeImage
        if ($sqlStruct->getSqlType() === SQLStruct::SQL_TYPE_UPDATE || $sqlStruct->getSqlType() === SQLStruct::SQL_TYPE_DELETE) {
            $beforeSQL = $this->buildBeforeImageSQL($sqlStruct);
            if (empty($beforeSQL))
                throw new MysqlTransactionException(MysqlTransactionException::BUILD_BEFORE_ERROR);
            $rows = $this->_connection->query($beforeSQL);
            foreach ($rows as &$row) {
                if (!isset($row[self::PRIMARY_KEY]))
                    throw new MysqlTransactionException(MysqlTransactionException::TABLE_DONT_HAVE_PRIMARY_KEY_ID);
                $primaryV[] = $row[self::PRIMARY_KEY];
                unset($row[self::PRIMARY_KEY]);
                $before[] = array_values($row);
                $cols[] = array_keys($row);
            }
        }
        //执行sql
        $res = $this->doSQLToDB($sqlStruct->getSqlType(),$sqlStruct->getOriginSql());
        //判断类型，构造afterImage
        if ($sqlStruct->getSqlType() === SQLStruct::SQL_TYPE_UPDATE) {
            foreach ($before as $index => $value) {
                if (!isset($primaryV[$index]))
                    throw new MysqlTransactionException(MysqlTransactionException::CONT_FIND_MATCH_PRIMARY_VALUE);
                $afterSQL = $this->buildAfterImageSQL($sqlStruct,self::PRIMARY_KEY,$primaryV[$index]);
                $rows = $this->_connection->query($afterSQL);
                foreach ($rows as &$row) {
                    if (!isset($row[self::PRIMARY_KEY]))
                        throw new MysqlTransactionException(MysqlTransactionException::TABLE_DONT_HAVE_PRIMARY_KEY_ID);
                    unset($row[self::PRIMARY_KEY]);
                    $after[] = array_values($row);
                }
            }
        }
        if ($sqlStruct->getSqlType() === SQLStruct::SQL_TYPE_INSERT) {
            list($count,$lastInsertId) = $res;
            $insertTimes = count($sqlStruct->getChangeColumnsListMap());
            for ($i=0;$i<$insertTimes;$i++) {
                $primaryV[] = $lastInsertId+$i;
            }
            foreach ($primaryV as $value) {
                $afterSQL = $this->buildAfterImageSQL($sqlStruct,self::PRIMARY_KEY,$value);
                $rows = $this->_connection->query($afterSQL);
                foreach ($rows as &$row) {
                    if (!isset($row[self::PRIMARY_KEY]))
                        throw new MysqlTransactionException(MysqlTransactionException::TABLE_DONT_HAVE_PRIMARY_KEY_ID);
                    unset($row[self::PRIMARY_KEY]);
                    $after[] = array_values($row);
                }
            }
        }
        //插入undoLog
        foreach ($primaryV as $index => $value) {
            $col = $cols[$index] ?? array();
            $oneBefore = $before[$index] ?? array();
            $oneAfter = $after[$index] ?? array();
            $undoSQL = $this->buildInsertUndoSQL($sqlStruct->getSqlType(),$col,$oneBefore,$oneAfter,$sqlStruct->getTable(),self::PRIMARY_KEY,$value);
            list($count,$lastInsertId) = $this->_connection->insert($undoSQL);
            if (empty($count))
                throw new MysqlTransactionException(MysqlTransactionException::INSERT_UNDO_ERROR);
        }
        return $res;
    }

    /**
     * 本地事务提交
     *  a.更新本地事务状态
     *  b.tc申请锁
     *  c.本地事务commit
     *  d.报告tc事务状态
     *
     * @throws MysqlTransactionException
     */
    public function commit()
    {
        if ($this->_tid === '') {
            //todo:请求tc申请锁
            $this->_connection->commit();
            return;
        }
        $updateSQL = $this->buildUpdateStatusSQL(self::STATUS_COMMIT);
        $count = $this->_connection->update($updateSQL);
        if (empty($count))
            throw new MysqlTransactionException(MysqlTransactionException::UPDATE_LOCAL_TRANSACTION_STATUS_ERROR);
        //todo:请求tc申请锁
        $this->_connection->commit();
        //todo:报告tc
        $this->_status = self::STATUS_COMMIT;
    }

    /**
     * 本地事务回滚
     *  a.本地事务rollback
     *  b.报告tc事务状态
     * */
    public function rollback()
    {
        $this->_connection->rollback();
        if ($this->_tid === '')
            return;
        //todo:报告tc
        $this->_status = self::STATUS_ROLLBACK;
    }

    /**
     * 全局事务回滚
     *  a.倒序查出该全局事务包含的本地事务
     *  b.依次根据undo构建语句回滚
     * */
    public function grollback()
    {
        if ($this->_tid === '') {
            $this->rollback();
            return;
        }
        $undoSQL = $this->buildSelectUndoSQL($this->_tid);
        $res = $this->_connection->query($undoSQL);
        foreach ($res as $undo) {
            $sqlType = $undo['type'] ?? SQLStruct::SQL_TYPE_UNKNOW;
            $table = $undo['table'] ?? '';
            if (empty($table))
                throw new MysqlTransactionException(MysqlTransactionException::UNDO_MISS_TABLE_ERROR);
            $primaryK = $undo['primary_key'] ?? '';
            if (empty($table))
                throw new MysqlTransactionException(MysqlTransactionException::UNDO_MISS_PRIMARY_KEY_ERROR);
            $primaryV = $undo['primary_value'] ?? '';
            if (empty($table))
                throw new MysqlTransactionException(MysqlTransactionException::UNDO_MISS_PRIMARY_VALUE_ERROR);
            if ($sqlType == SQLStruct::SQL_TYPE_INSERT) {
                $sql = $this->buildUndoInsertSQL($table,$primaryK,$primaryV);
                $count = $this->_connection->delete($sql);
                if (empty($count))
                    throw new MysqlTransactionException(MysqlTransactionException::UNDO_INSERT_ERROR);
            }
            $cols = $undo['cols'] ?? '';
            $before = $undo['before'] ?? '';
            if ($sqlType == SQLStruct::SQL_TYPE_UPDATE) {
                if (empty($cols) || empty($before))
                    throw new MysqlTransactionException(MysqlTransactionException::UNDO_UPDATE_MISS_COLS_OR_BEFORE);
                $colList = explode(',',$cols);
                $beforeList = explode(',',$before);
                if (count($colList) != count($beforeList))
                    throw new MysqlTransactionException(MysqlTransactionException::UNDO_UPDATE_MISS_COLS_OR_BEFORE);
                $sql = $this->buildUndoUpdateSQL($table,$colList,$beforeList,$primaryK,$primaryV);
                $count = $this->_connection->update($sql);
                if (empty($count))
                    throw new MysqlTransactionException(MysqlTransactionException::UNDO_UPDATE_ERROR);
            }
            if ($sqlType == SQLStruct::SQL_TYPE_DELETE) {
                if (empty($cols) || empty($before))
                    throw new MysqlTransactionException(MysqlTransactionException::UNDO_UPDATE_MISS_COLS_OR_BEFORE);
                $colList = explode(',',$cols);
                $beforeList = explode(',',$before);
                if (count($colList) != count($beforeList))
                    throw new MysqlTransactionException(MysqlTransactionException::UNDO_UPDATE_MISS_COLS_OR_BEFORE);
                $sql = $this->buildUndoDeleteSQL($table,$colList,$beforeList,$primaryK,$primaryV);
                list($count,$lastInsertId) = $this->_connection->insert($sql);
                if (empty($count))
                    throw new MysqlTransactionException(MysqlTransactionException::UNDO_DELETE_ERROR);
            }
        }
    }

    /**
     * 工具方法，拼insert语句
     * @param int $type sql类型
     * @param array $cols 变更字段
     * @param array $before 变更前值
     * @param array $after 变更后值
     * @param string $table 表名
     * @param string $primaryK 主键名
     * @param string $primaryV 主键值
     * @return string insert语句
     */
    protected function buildInsertUndoSQL(int $type,array $cols,array $before,array $after,string $table,string $primaryK,string $primaryV)
    {
        $temp = 'INSERT INTO `%s` (`ltid`,`tid`,`type`,`cols`,`before`,`after`,`table`,`primary_key`,`primary_value`) VALUE (\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\')';
        return sprintf($temp,self::TABLE_UNDO,$this->_lastInsertId,$this->_tid,$type,implode(',',$cols),implode(',',$before),implode(',',$after),$table,$primaryK,$primaryV);
    }

    /**
     * 工具方法，拼insert语句
     * @return string insert语句
     * */
    protected function buildInsertSQL()
    {
        $temp = 'INSERT INTO `%s` (`tid`,`desc`,`status`) VALUE (\'%s\',\'%s\',\'%s\')';
        return sprintf($temp,self::TABLE_TRANSACTION,$this->_tid,$this->_desc,$this->_status);
    }

    /**
     * 工具方法，拼selectBefore语句
     * @param SQLStruct $struct 分析后的SQL结构体
     * @return string selectBefore语句
     */
    protected function buildBeforeImageSQL(SQLStruct $struct)
    {
        $temp = 'SELECT %s FROM `%s` %s';
        $cols = $struct->getColumnsWithId();
        if (empty($cols))
            $cols = ['*'];
        return sprintf($temp,implode(',',$cols),$struct->getTable(),$struct->getConditionsStr());
    }

    /**
     * 工具方法，拼selectAfter语句
     * @param SQLStruct $struct 分析后的SQL结构体
     * @param string $primaryK 主键key
     * @param string $primaryV 主键value
     * @return string selectAfter语句
     */
    protected function buildAfterImageSQL(SQLStruct $struct,string $primaryK,string $primaryV)
    {
        $temp = 'SELECT %s FROM `%s` WHERE %s = \'%s\'';
        $cols = $struct->getColumnsWithId();
        if (empty($cols))
            $cols = ['*'];
        return sprintf($temp,implode(',',$cols),$struct->getTable(),$primaryK,$primaryV);
    }

    /**
     * 工具方法，拼update语句
     * @param int $status 状态
     * @return string update语句
     */
    protected function buildUpdateStatusSQL(int $status)
    {
        $updateSQL = 'UPDATE `%s` SET status = \'%s\' WHERE id = \'%s\'';
        return sprintf($updateSQL,self::TABLE_TRANSACTION,$status,$this->_lastInsertId);
    }

    /**
     * 工具方法，拼select语句
     * @param string $tid 全局事务id
     * @return string update语句
     */
    protected function buildSelectUndoSQL(string $tid)
    {
        $selectSQL = 'SELECT * FROM `%s` WHERE tid = \'%s\' order by id desc';
        return sprintf($selectSQL,self::TABLE_UNDO,$tid);
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

    /**
     * 工具方法，执行mysql语句
     * @param int $sqlType sql类型
     * @param string $sql sql语句
     * @return array|false|int
     */
    protected function doSQLToDB(int $sqlType,string $sql)
    {
        if ($sqlType === SQLStruct::SQL_TYPE_SELECT)
            return $this->_connection->query($sql);
        if ($sqlType === SQLStruct::SQL_TYPE_INSERT)
            return $this->_connection->insert($sql);
        if ($sqlType === SQLStruct::SQL_TYPE_UPDATE)
            return $this->_connection->update($sql);
        if ($sqlType === SQLStruct::SQL_TYPE_DELETE)
            return $this->_connection->delete($sql);
        return false;
    }

}