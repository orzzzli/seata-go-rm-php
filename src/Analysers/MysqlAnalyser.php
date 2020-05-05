<?php
/**
 * @user: ligongxiang (ligongxiang@rouchi.com)
 * @date : 2020/4/24
 * @version : 1.0
 * @file : MysqlAnalyser.php
 * @desc :
 */

namespace ResourceManager\Analysers;

use ResourceManager\Exceptions\MysqlGrammarException;
use ResourceManager\Grammar\Mysql\MysqlGrammar;
use ResourceManager\Grammar\Mysql\SQLStruct;

/**
 * mysql分析器
 * */
class MysqlAnalyser
{

    /**
     * 分析SQL获取Struct对象
     * @param string $sql sql语句
     * @return SQLStruct SQL对象
     * @throws MysqlGrammarException
     */
    public function analyse(string $sql)
    {
        return MysqlGrammar::analyse($sql);
    }
}