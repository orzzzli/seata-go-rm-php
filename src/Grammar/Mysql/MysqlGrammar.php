<?php
/**
 * @user: ligongxiang (ligongxiang@rouchi.com)
 * @date : 2020/4/30
 * @version : 1.0
 * @file : MysqlGrammar.php
 * @desc :
 */

namespace ResourceManager\Grammar\Mysql;

use ResourceManager\Exceptions\MysqlGrammarException;

/**
 * Mysql语法解析器，核心目标为解析一条sql语句，返回SQLStruct结构对象。
 * 错误码：[10000-10100)
 * */
class MysqlGrammar
{
    //支持sql类型
    const SUPPORT_SQL_TYPE = [
        'INSERT',
        'UPDATE',
        'DELETE',
        'SELECT',
    ];

    //碰到这些字符，分割单词
    const SEPARATE_SIGN = [
        ' ',
        ',',
        ';',
        '(',
        ')',
        '=',
        '>',
        '<',
        '<>',
    ];
    //碰到这些字符，进入单词arr
    const JOIN_WORD_SIGN = [
        '=',
        '>',
        '<',
        '<>',
    ];
    //匹配字符，分割符在这些字符中当成普通字符串处理
    const PAIR_SIGN = [
        '`',
        '"',
    ];

    /**
     * 核心方法，解析SQL类型并根据类型委托给对应Handler生成SQLStruct
     *
     * @param $originSql string sql语句
     * @return SQLStruct 格式化的sql结构体
     * @throws MysqlGrammarException
     */
    public static function analyse($originSql)
    {
        $sqlType = self::getSqlType($originSql);
        $handler = self::firstCapToUpper($sqlType).'Handler';
        $handlerObj = new $handler();
        return $handlerObj->getSQLStruct();
    }

    /**
     * 根据前6个字符判断SQL类型，并判断是否支持
     *
     * @param $originSql string SQL语句
     * @return null|string 解析出的SQL类型
     * @throws MysqlGrammarException 10000，sql类型不支持
     */
    protected static function getSqlType($originSql)
    {
        $type = substr(trim($originSql),0,6);
        if (in_array(strtoupper($type),self::SUPPORT_SQL_TYPE)) {
            throw new MysqlGrammarException(10000,'mysql type not support');
        }
        return $type;
    }

    /**
     * 工具方法，首字母大写
     * @param $str string 需格式化的str
     * @return string 格式化后的str
     */
    protected static function firstCapToUpper($str)
    {
        $outStr = '';
        for ($i=0;$i<strlen($str);$i++) {
            if ($i == 0)
                $outStr .= strtoupper($str[$i]);
            else
                $outStr .= strtolower($str[$i]);
        }
        return $outStr;
    }
}