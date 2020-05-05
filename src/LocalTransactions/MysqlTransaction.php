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
use ResourceManager\Exceptions\MysqlTransactionException;
use ResourceManager\Grammar\Mysql\SQLStruct;

class MysqlTransaction
{
    const STATUS_ACTIVE = 1;
    const STATUS_COMMIT = 2;
    const STATUS_ROLLBACK = 3;

    const TABLE_TRANSACTION = 'transaction_local';
    const TABLE_UNDO = 'transaction_undo';
    const PRIMARY_KEY = 'id';
    protected $_lastInsertId = 0;
    protected $_pdo = null;
    protected $_analyser = null;

    protected $_tid = '';
    protected $_sign = '';
    protected $_desc = '';
    protected $_status = self::STATUS_ACTIVE;
    public function __construct(\PDO $pdo,string $sign,string $desc = '',string $tid = '')
    {
        $this->_pdo = $pdo;
        $this->_sign = $sign;
        $this->_desc = $desc;
        $this->_tid = $tid;

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
        $this->startTransactionToDB();
        $this->insertTransactionToDB();
    }

    /**
     * 执行SQL.
     *  a.分析SQL获得SQL结构
     *  b.构造beforeImage
     *  c.执行sql
     *  d.构造afterImage
     *  e.插入undoLog
     * @param string $sql sql语句
     * @throws MysqlTransactionException
     * @throws \ResourceManager\Exceptions\MysqlGrammarException
     */
    public function doing(string $sql)
    {
        //分析sql
        $sqlStruct = $this->_analyser->analyse($sql);
        $primaryV = array();
        $cols = array();
        $before = array();
        $after = array();
        //判断sql类型，构造beforeImage
        if ($sqlStruct->getSqlType() === SQLStruct::SQL_TYPE_UPDATE || $sqlStruct->getSqlType() === SQLStruct::SQL_TYPE_DELETE) {
            $beforeSQL = $this->buildBeforeImageSQL($sqlStruct);
            if (empty($beforeSQL))
                throw new MysqlTransactionException(MysqlTransactionException::BUILD_BEFORE_ERROR);
            $rows = $this->selectFromDBGetArr($beforeSQL);
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
        if ($sqlStruct->getSqlType() === SQLStruct::SQL_TYPE_UPDATE || $sqlStruct->getSqlType() === SQLStruct::SQL_TYPE_INSERT) {
            foreach ($before as $index => $value) {
                if (!isset($primaryV[$index]))
                    throw new MysqlTransactionException(MysqlTransactionException::CONT_FIND_MATCH_PRIMARY_VALUE);
                $afterSQL = $this->buildAfterImageSQL($sqlStruct,self::PRIMARY_KEY,$primaryV[$index]);
                $rows = $this->selectFromDBGetArr($afterSQL);
                foreach ($rows as &$row) {
                    if (!isset($row[self::PRIMARY_KEY]))
                        throw new MysqlTransactionException(MysqlTransactionException::TABLE_DONT_HAVE_PRIMARY_KEY_ID);
                    unset($row[self::PRIMARY_KEY]);
                    $after[] = array_values($row);
                }
            }
        }
        //插入undoLog
        foreach ($primaryV as $value) {
            $this->insertUndoToDB($this->_lastInsertId,$this->_tid,$sqlStruct->getSqlType(),$cols,$before,$after,$sqlStruct->getTable(),self::PRIMARY_KEY,$value);
        }
    }

    public function commit()
    {
        // TODO: Implement commit() method.
    }

    public function rollback()
    {
        // TODO: Implement rollback() method.
    }

    public function getStatus()
    {
        return $this->_status;
    }

    /**
     * 从DB中获取select结果
     * @param string $sql select语句
     * @return array 结果listMap
     */
    protected function selectFromDBGetArr(string $sql)
    {
        $stmt = $this->_pdo->query($sql);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC); //获取所有
        return $rows;
    }

    /**
     * mysql开启事务
     *
     * @throws MysqlTransactionException 开启事务失败
     * todo:处理pdo error
     */
    protected function startTransactionToDB()
    {
        $res = $this->_pdo->beginTransaction();
        if ($res === false)
            throw new MysqlTransactionException(MysqlTransactionException::BEGIN_LOCAL_TRANSACTION_ERROR);
    }

    /**
     * 插入本地事务表
     *
     * @throws MysqlTransactionException 插入失败
     * todo:处理pdo error
     */
    protected function insertTransactionToDB()
    {
        $insertSQL = $this->buildInsertSQL();
        $count = $this->_pdo->exec($insertSQL);
        if ($count === 0)
            throw new MysqlTransactionException(MysqlTransactionException::INSERT_LOCAL_TRANSACTION_ERROR);
        $this->_lastInsertId = $this->_pdo->lastInsertId();
    }

    /**
     * 插入undo表
     *
     * @param string $ltid 本地事务id
     * @param string $tid 全局事务id
     * @param int $type sql类型
     * @param array $cols 变更字段
     * @param array $before 变更前值
     * @param array $after 变更后值
     * @param string $table 表名
     * @param string $primaryK 主键名
     * @param string $primaryV 主键值
     * @throws MysqlTransactionException 插入失败
     * todo:处理pdo error
     */
    protected function insertUndoToDB(string $ltid,string $tid,int $type,array $cols,array $before,array $after,string $table,string $primaryK,string $primaryV)
    {
        $temp = 'INSERT INTO `%s` (ltid,tid,type,cols,before,after,table,primary_key,primary_value) VALUE (\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\')';
        $insertSQL = sprintf($temp,self::TABLE_UNDO,$ltid,$tid,$type,implode(',',$cols),implode(',',$before),implode(',',$after),$table,$primaryK,$primaryV);
        $count = $this->_pdo->exec($insertSQL);
        if ($count === 0)
            throw new MysqlTransactionException(MysqlTransactionException::INSERT_UNDO_ERROR);
    }

    /**
     * 工具方法，拼insert语句
     * @return string insert语句
     * */
    protected function buildInsertSQL()
    {
        $temp = 'INSERT INTO `%s` (tid,desc,status) VALUE (\'%s\',\'%s\',\'%s\')';
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
     * 工具方法，执行mysql语句
     * @param int $sqlType sql类型
     * @param string $sql sql语句
     * @return array|false|int
     */
    protected function doSQLToDB(int $sqlType,string $sql)
    {
        if ($sqlType === SQLStruct::SQL_TYPE_SELECT) {
            $stmt = $this->_pdo->query($sql);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC); //获取所有
        }
        return $this->_pdo->exec($sql);
    }

}