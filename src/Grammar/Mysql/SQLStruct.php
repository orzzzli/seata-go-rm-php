<?php
/**
 * @user: ligongxiang (ligongxiang@rouchi.com)
 * @date : 2020/4/28
 * @version : 1.0
 * @file : SQLStruct.php
 * @desc :
 */

namespace ResourceManager\Grammar\Mysql;

/**
 * SQL结构体
 * */
class SQLStruct
{
    const SQL_TYPE_UNKNOW = 0;
    const SQL_TYPE_INSERT = 1;
    const SQL_TYPE_SELECT = 2;
    const SQL_TYPE_UPDATE = 3;
    const SQL_TYPE_DELETE = 4;

    protected $originSql = '';
    protected $sqlType = self::SQL_TYPE_UNKNOW;
    protected $table = '';
    protected $conditionsStr = '';
    protected $changeColumnsListMap = array();

    public function __construct($originSql)
    {
        $this->originSql = $originSql;
    }

    public function getOriginSql(): string
    {
        return $this->originSql;
    }

    public function getSqlType(): int
    {
        return $this->sqlType;
    }

    public function setSqlType($sqlTypeStr)
    {
        $type = strtoupper($sqlTypeStr);
        if ($type == 'SELECT') {
            $this->sqlType = self::SQL_TYPE_SELECT;
        }
        if ($type == 'INSERT') {
            $this->sqlType = self::SQL_TYPE_INSERT;
        }
        if ($type == 'UPDATE') {
            $this->sqlType = self::SQL_TYPE_UPDATE;
        }
        if ($type == 'DELETE') {
            $this->sqlType = self::SQL_TYPE_DELETE;
        }
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function setTable(string $table)
    {
        $this->table = $table;
    }

    public function getConditionsStr(): string
    {
        return $this->conditionsStr;
    }

    public function setConditionsStr(string $conditionsStr)
    {
        $this->conditionsStr = $conditionsStr;
    }

    public function getChangeColumnsListMap(): array
    {
        return $this->changeColumnsListMap;
    }

    public function setChangeColumnsListMap(array $changeColumnsListMap)
    {
        $this->changeColumnsListMap = $changeColumnsListMap;
    }

    public function getColumnsWithId()
    {
        if (empty($this->changeColumnsListMap))
            return array();
        $temp = array_keys($this->changeColumnsListMap[0]);
        //检查是否包含id
        if (!in_array('id',$temp))
            array_push($temp,'id');
        return $temp;
    }

    public function getChangeColumns()
    {
        if (empty($this->changeColumnsListMap))
            return array();
        return array_keys($this->changeColumnsListMap[0]);
    }
}