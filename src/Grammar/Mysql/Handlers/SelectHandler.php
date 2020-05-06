<?php
/**
 * @user: ligongxiang (ligongxiang@rouchi.com)
 * @date : 2020/5/4
 * @version : 1.0
 * @file : SelectHandler.php
 * @desc :
 */

namespace ResourceManager\Grammar\Mysql\Handlers;

use ResourceManager\Exceptions\MysqlGrammarException;
use ResourceManager\Grammar\Mysql\MysqlGrammar;
use ResourceManager\Grammar\Mysql\SQLStruct;
/**
 * Select语句解析类
 * 错误码：[11300-11400)
 * todo:完成解析逻辑
 * */
class SelectHandler
{
    const KEYWORDS = [
        'SELECT',
    ];
    protected $sqlType = 'SELECT';
    protected $sqlStruct = null;
    protected $originSql = '';
    protected $keywordMap = array();
    protected $wordList = array();

    protected $columnNumbers = 0;
    protected $tableIndex = 0;

    /**
     * 初始化
     * @param $sql string 待处理语句
     * @throws MysqlGrammarException
     */
    public function __construct($sql)
    {
        $this->originSql = $sql;
    }

    /**
     * 获取SQLStruct对象
     *
     * @throws MysqlGrammarException
     */
    public function getSQLStruct()
    {
        if (!empty($this->sqlStruct))
            return $this->sqlStruct;
        $this->sqlStruct = new SQLStruct($this->originSql);
        $this->sqlStruct->setSqlType($this->sqlType);
        return $this->sqlStruct;
    }
}