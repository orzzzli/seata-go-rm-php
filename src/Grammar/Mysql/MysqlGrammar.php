<?php
/**
 * @user: ligongxiang (ligongxiang@rouchi.com)
 * @date : 2020/4/30
 * @version : 1.0
 * @file : MysqlGrammar.php
 * @desc :
 */

namespace ResourceManager\Grammar\Mysql;


use ResourceManager\Analysers\SQLStruct;
use ResourceManager\Exceptions\MysqlGrammarException;

/**
 * Mysql语法解析器，核心目标为解析一条sql语句，返回SQLStruct结构对象。
 * 错误码：10000-10100
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

    const SQL_UPDATE_KEYWORDS = [
        'LOW_PRIORITY',
        'IGNORE',
    ];
    const SQL_UPDATE_CONDITION_KEYWORDS = [
        'WHERE',
        'ORDER BY',
        'LIMIT',
    ];
    const SQL_UPDATE_CHANGE_KEYWORDS = [
        'SET',
    ];
    const SQL_INSERT_KEYWORDS = [
        'LOW_PRIORITY',
        'DELAYED',
        'HIGH_PRIORITY',
        'IGNORE',
        'INTO',
    ];
    const SQL_DELETE_KEYWORDS = [
        'LOW_PRIORITY',
        'IGNORE',
        'QUICK',
        'FROM',
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
     * @throws MysqlGrammarException
     */
    public static function analyse($originSql)
    {
        $sqlType = self::getSqlType($originSql);
        $handler = self::firstCapToUpper($sqlType).'Handler';
        $handlerObj = new $handler();
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

    //分词，将sql解析成单词arr
    public static function spiltWords($sql)
    {
        $strLen = strlen($sql);
        $words = array();
        if ($strLen == 0)
            return $words;
        $letters = array();
        $symbolTemp = null;
        for ($i=0;$i<$strLen;$i++) {
            $letter = $sql[$i];
            //非匹配中的分隔符，生成单词，并将符号本身作为单词计入
            if ($symbolTemp === null && in_array($letter,self::SEPARATE_SIGN)) {
                $word = implode("",$letters);
                if (!empty($word)) {
                    //特殊处理order by
                    if (strtoupper($word) == 'BY') {
                        $lastWord = array_pop($words);
                        if (strtoupper($lastWord) == 'ORDER') {
                            //插入order by
                            array_push($words,$lastWord.' '.$word);
                        }
                    }else{
                        array_push($words,$word);
                    }
                }
                //丢弃字符不进入单词数组
                if (!in_array($letter,self::DISCARD_SIGN))
                    array_push($words,$letter);
                $letters = array();
                continue;
            }
            //非匹配中的匹配字符，更新标记，生成单词，并作为单词计入
            if ($symbolTemp === null && in_array($letter,self::PAIR_SIGN)) {
                $symbolTemp = $letter;
                $word = implode("",$letters);
                if (!empty($word)) {
                    //特殊处理order by
                    if (strtoupper($word) == 'BY') {
                        $lastWord = array_pop($words);
                        if (strtoupper($lastWord) == 'ORDER') {
                            //插入order by
                            array_push($words,$lastWord.' '.$word);
                        }
                    }else{
                        array_push($words,$word);
                    }
                }
                //丢弃字符不进入单词数组
                if (!in_array($letter,self::DISCARD_SIGN))
                    array_push($words,$letter);
                $letters = array();
                continue;
            }
            //匹配中的非对应配对字符，全部计入字符数组
            if ($symbolTemp !== null && !in_array($letter,self::PAIR_SIGN)) {
                array_push($letters,$letter);
                continue;
            }
            //匹配中的配对字符，一致则生成单词，并作为单词计入，否则计入字符数组
            if ($symbolTemp !== null && in_array($letter,self::PAIR_SIGN)) {
                if ($letter == $symbolTemp) {
                    $word = implode("",$letters);
                    if (!empty($word)) {
                        //特殊处理order by
                        if (strtoupper($word) == 'BY') {
                            $lastWord = array_pop($words);
                            if (strtoupper($lastWord) == 'ORDER') {
                                //插入order by
                                array_push($words,$lastWord.' '.$word);
                            }
                        }else{
                            array_push($words,$word);
                        }
                    }
                    //丢弃字符不进入单词数组
                    if (!in_array($letter,self::DISCARD_SIGN))
                        array_push($words,$letter);
                    $letters = array();
                    $symbolTemp = null;
                }else{
                    array_push($letters,$letter);
                }
                continue;
            }
            //非匹配中的非丢弃字符，直接进入字符数组
            if ($symbolTemp === null && !in_array($letter,self::DISCARD_SIGN)) {
                array_push($letters,$letter);
            }
            //结束生成单词
            if ($i === ($strLen - 1) && !empty($letters)) {
                $word = implode("",$letters);
                if (!empty($word)) {
                    //特殊处理order by
                    if (strtoupper($word) == 'BY') {
                        $lastWord = array_pop($words);
                        if (strtoupper($lastWord) == 'ORDER') {
                            //插入order by
                            array_push($words,$lastWord.' '.$word);
                        }
                    }else{
                        array_push($words,$word);
                    }
                }
                $letters = array();
            }
        }
        if ($symbolTemp !== null)
            return array();
        return $words;
    }

    //分词，将sql解析成单词arr，带pair符号
    public static function spiltWordsWithPair($sql)
    {
        $strLen = strlen($sql);
        $words = array();
        if ($strLen == 0)
            return $words;
        $letters = array();
        $symbolTemp = null;
        for ($i=0;$i<$strLen;$i++) {
            $letter = $sql[$i];
            //非匹配中的分隔符，生成单词，并将符号本身作为单词计入
            if ($symbolTemp === null && in_array($letter,self::SEPARATE_SIGN)) {
                $word = implode("",$letters);
                if (!empty($word)) {
                    //特殊处理order by
                    if (strtoupper($word) == 'BY') {
                        $lastWord = array_pop($words);
                        if (strtoupper($lastWord) == 'ORDER') {
                            //插入order by
                            array_push($words,$lastWord.' '.$word);
                        }
                    }else{
                        array_push($words,$word);
                    }
                }
                //丢弃字符不进入单词数组
                if (in_array($letter,self::PAIR_SIGN) || !in_array($letter,self::DISCARD_SIGN))
                    array_push($words,$letter);
                $letters = array();
                continue;
            }
            //非匹配中的匹配字符，更新标记，生成单词，并作为单词计入
            if ($symbolTemp === null && in_array($letter,self::PAIR_SIGN)) {
                $symbolTemp = $letter;
                $word = implode("",$letters);
                if (!empty($word)) {
                    //特殊处理order by
                    if (strtoupper($word) == 'BY') {
                        $lastWord = array_pop($words);
                        if (strtoupper($lastWord) == 'ORDER') {
                            //插入order by
                            array_push($words,$lastWord.' '.$word);
                        }
                    }else{
                        array_push($words,$word);
                    }
                }
                //丢弃字符不进入单词数组
                if (in_array($letter,self::PAIR_SIGN) || !in_array($letter,self::DISCARD_SIGN))
                    array_push($words,$letter);
                $letters = array();
                continue;
            }
            //匹配中的非对应配对字符，全部计入字符数组
            if ($symbolTemp !== null && !in_array($letter,self::PAIR_SIGN)) {
                array_push($letters,$letter);
                continue;
            }
            //匹配中的配对字符，一致则生成单词，并作为单词计入，否则计入字符数组
            if ($symbolTemp !== null && in_array($letter,self::PAIR_SIGN)) {
                if ($letter == $symbolTemp) {
                    $word = implode("",$letters);
                    if (!empty($word)) {
                        //特殊处理order by
                        if (strtoupper($word) == 'BY') {
                            $lastWord = array_pop($words);
                            if (strtoupper($lastWord) == 'ORDER') {
                                //插入order by
                                array_push($words,$lastWord.' '.$word);
                            }
                        }else{
                            array_push($words,$word);
                        }
                    }
                    //丢弃字符不进入单词数组
                    if (in_array($letter,self::PAIR_SIGN) || !in_array($letter,self::DISCARD_SIGN))
                        array_push($words,$letter);
                    $letters = array();
                    $symbolTemp = null;
                }else{
                    array_push($letters,$letter);
                }
                continue;
            }
            //非匹配中的非丢弃字符，直接进入字符数组
            if ($symbolTemp === null && !in_array($letter,self::DISCARD_SIGN)) {
                array_push($letters,$letter);
            }
            //结束生成单词
            if ($i === ($strLen - 1) && !empty($letters)) {
                $word = implode("",$letters);
                if (!empty($word)) {
                    //特殊处理order by
                    if (strtoupper($word) == 'BY') {
                        $lastWord = array_pop($words);
                        if (strtoupper($lastWord) == 'ORDER') {
                            //插入order by
                            array_push($words,$lastWord.' '.$word);
                        }
                    }else{
                        array_push($words,$word);
                    }
                }
                $letters = array();
            }
        }
        if ($symbolTemp !== null)
            return array();
        return $words;
    }

    //todo:新增update、insert、delete等操作对于多表操作的支持
    public static function analyseTable($sqlType,&$words)
    {
        $table = '';
        //todo:新增对于select的处理
        if ($sqlType == SQLStruct::SQL_TYPE_SELECT) {
        }
        /*
            UPDATE [LOW_PRIORITY] [IGNORE] table_reference
                SET assignment_list
                [WHERE where_condition]
                [ORDER BY ...]
                [LIMIT row_count]

            value:
                {expr | DEFAULT}

            assignment:
                col_name = value

            assignment_list:
                assignment [, assignment] ...
         * */
        if ($sqlType == SQLStruct::SQL_TYPE_UPDATE) {
            //update包含关键字最多第四个单词为table
            for ($i=1;$i<=3;$i++) {
                if (!isset($words[$i]))
                    return '';
                if (!in_array(strtoupper($words[$i]),self::SQL_UPDATE_KEYWORDS))
                    return $words[$i];
            }
        }
        /*
            INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE]
            [INTO] tbl_name
            [PARTITION (partition_name [, partition_name] ...)]
            [(col_name [, col_name] ...)]
            {VALUES | VALUE} (value_list) [, (value_list)] ...
            [ON DUPLICATE KEY UPDATE assignment_list]
         * */
        if ($sqlType == SQLStruct::SQL_TYPE_INSERT) {
            //insert包含关键字最多第五个单词为table
            for ($i=1;$i<=4;$i++) {
                if (!isset($words[$i]))
                    return '';
                if (!in_array(strtoupper($words[$i]),self::SQL_INSERT_KEYWORDS))
                    return $words[$i];
            }
        }
        /*
            DELETE [LOW_PRIORITY] [QUICK] [IGNORE] FROM tbl_name
            [PARTITION (partition_name [, partition_name] ...)]
            [WHERE where_condition]
            [ORDER BY ...]
            [LIMIT row_count]
         * */
        if ($sqlType == SQLStruct::SQL_TYPE_DELETE) {
            //delete包含关键字最多第五个单词为table
            for ($i=1;$i<=5;$i++) {
                if (!isset($words[$i]))
                    return '';
                if (!in_array(strtoupper($words[$i]),self::SQL_DELETE_KEYWORDS))
                    return $words[$i];
            }
        }
        return $table;
    }

    public static function analyseFullConditionStr($sqlType,&$pairWords)
    {
        $conditionStr = '';
        /*
                    UPDATE [LOW_PRIORITY] [IGNORE] table_reference
                        SET assignment_list
                        [WHERE where_condition]
                        [ORDER BY ...]
                        [LIMIT row_count]

                    value:
                        {expr | DEFAULT}

                    assignment:
                        col_name = value

                    assignment_list:
                        assignment [, assignment] ...
                 * */
        if ($sqlType == SQLStruct::SQL_TYPE_UPDATE) {
            $symbolTemp = null;
            $conditionIndex = 0;
            foreach ($pairWords as $index => $word) {
                if (in_array(strtoupper($word),self::PAIR_SIGN) && $symbolTemp === null) {
                    $symbolTemp = $word;
                    continue;
                }
                if (in_array(strtoupper($word),self::PAIR_SIGN) && $symbolTemp !== null) {
                    if ($word == $symbolTemp) {
                        $symbolTemp = null;
                    }
                    continue;
                }
                if ($symbolTemp !== null) {
                    continue;
                }
                if (in_array(strtoupper($word),self::SQL_UPDATE_CONDITION_KEYWORDS)) {
                    $conditionIndex = $index;
                    break;
                }
            }
            $tempInPair = null;
            for ($i = $conditionIndex;$i<count($pairWords);$i++) {
                if (in_array($pairWords[$i],self::PAIR_SIGN) && $tempInPair === null) {
                    $conditionStr .= $pairWords[$i];
                    $tempInPair = $pairWords[$i];
                    continue;
                }
                if (in_array($pairWords[$i],self::PAIR_SIGN) && $tempInPair !== null) {
                    if ($pairWords[$i] == $tempInPair) {
                        $conditionStr .= $pairWords[$i].' ';
                        $tempInPair = null;
                    }else{
                        $conditionStr .= $pairWords[$i];
                    }
                    continue;
                }
                if ($tempInPair !== null) {
                    $conditionStr .= $pairWords[$i];
                }else{
                    $conditionStr .= $pairWords[$i].' ';
                }
            }
        }
        return $conditionStr;
    }

    public static function analyseChangeColumns($sqlType,&$pairWords)
    {
        $changeColumnsMap = array();
        if ($sqlType == SQLStruct::SQL_TYPE_UPDATE) {
            $changeStartTemp = false;
            $pairTemp = false;
            foreach ($pairWords as $index => $word) {
                //非匹配符号中的set才是关键字
                if ($pairTemp === false && in_array(strtoupper($word),self::SQL_UPDATE_CHANGE_KEYWORDS)) {
                    $changeStartTemp = $index;
                    break;
                }
            }
            for ($i=$changeStartTemp;$i<count($pairWords)-1;$i++) {

            }
        }
    }
}