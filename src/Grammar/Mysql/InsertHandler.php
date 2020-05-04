<?php
/**
 * @user: ligongxiang (ligongxiang@rouchi.com)
 * @date : 2020/4/30
 * @version : 1.0
 * @file : InsertHandler.php
 * @desc :
 */

namespace ResourceManager\Grammar\Mysql;

use ResourceManager\Exceptions\MysqlGrammarException;

/**
 * Insert语句实际解析类
 * 错误码：[11100-11200)
 * */
class InsertHandler
{
    const KEYWORDS = [
        'UPDATE',
        'LOW_PRIORITY',
        'DELAYED',
        'HIGH_PRIORITY',
        'IGNORE',
        'INTO',
        'SET',
        'VALUES',
        'VALUE',
    ];
    protected $sqlType = 'INSERT';
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
        $this->spiltWords();
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
        $this->sqlStruct->setTable($this->findTable());
        $this->sqlStruct->setChangeColumnsListMap($this->findChangeMap());
        return $this->sqlStruct;
    }

    /**
     * 根据keywordMap找到表名，去除可能分词时自动包裹的'
     *
     * @throws MysqlGrammarException 11100,语法错误，找不到表名
     */
    protected function findTable()
    {
        if (isset($this->keywordMap['INTO'])) {
            $this->tableIndex = $this->keywordMap['INTO'];
            return trim($this->wordList[$this->keywordMap['INTO']],'\'');
        }
        if (isset($this->keywordMap['IGNORE'])) {
            $this->tableIndex = $this->keywordMap['IGNORE'];
            return trim($this->wordList[$this->keywordMap['IGNORE']],'\'');
        }
        if (isset($this->keywordMap['LOW_PRIORITY'])){
            $this->tableIndex = $this->keywordMap['LOW_PRIORITY'];
            return trim($this->wordList[$this->keywordMap['LOW_PRIORITY']],'\'');
        }
        if (isset($this->keywordMap['DELAYED'])) {
            $this->tableIndex = $this->keywordMap['DELAYED'];
            return trim($this->wordList[$this->keywordMap['DELAYED']],'\'');
        }
        if (isset($this->keywordMap['HIGH_PRIORITY'])) {
            $this->tableIndex = $this->keywordMap['HIGH_PRIORITY'];
            return trim($this->wordList[$this->keywordMap['HIGH_PRIORITY']],'\'');
        }
        if (isset($this->keywordMap['INSERT'])) {
            $this->tableIndex = $this->keywordMap['INSERT'];
            return trim($this->wordList[$this->keywordMap['INSERT']],'\'');
        }
        throw new MysqlGrammarException(11100,'insert sql syntax error, cant find table');
    }

    /**
     * 根据keywordMap找到set修改的字段arr，设值时，去掉分词时可能设置的'
     *
     * @throws MysqlGrammarException 11101-11106,语法错误
     */
    protected function findChangeMap()
    {
        if (!isset($this->keywordMap['VALUES']) && !isset($this->keywordMap['VALUE']) && !isset($this->keywordMap['SET']))
            throw new MysqlGrammarException(11101,'insert sql syntax error, cant find keyword values/value/set');

        //SET模式的INSERT
        if (isset($this->keywordMap['SET'])) {
            $changeMap = array();
            $start = $this->keywordMap['SET'];
            $end = count($this->wordList);

            $tempKey = false;
            for ($i=$start;$i<$end;$i++) {
                if (in_array($this->wordList[$i],MysqlGrammar::JOIN_WORD_SIGN))
                    continue;
                if ($tempKey === false) {
                    $changeMap[$this->wordList[$i]] = '';
                    $tempKey = $this->wordList[$i];
                    continue;
                }
                if ($tempKey !== false) {
                    $tempKey = trim($tempKey,'\'');
                    $changeMap[$tempKey] = trim($this->wordList[$i],'\'');
                    $tempKey = false;
                    continue;
                }
            }
            return array($changeMap);
        }
        //VALUE模式的INSERT
        if (isset($this->keywordMap['VALUE'])) {
            $valueStart = $this->keywordMap['VALUE'];
            $columnStart = $this->tableIndex+1;
            $columnEnd = $columnStart + $this->columnNumbers;
            if ($columnEnd != $valueStart) {
                throw new MysqlGrammarException(11102,'insert sql syntax error, column number not fit');
            }
            $changeMap = array();
            for ($i=$columnStart;$i<$columnEnd;$i++) {
                if (!isset($this->wordList[$i+$this->columnNumbers]))
                    throw new MysqlGrammarException(11103,'insert sql syntax error, column/value not match');
                $changeMap[trim($this->wordList[$i],'\'')] = trim($this->wordList[$i+$this->columnNumbers],'\'');
            }
            return array($changeMap);
        }
        //VALUES模式的INSERT
        if (isset($this->keywordMap['VALUES'])) {
            $valueStart = $this->keywordMap['VALUES'];
            $columnStart = $this->tableIndex+1;
            $columnEnd = $columnStart + $this->columnNumbers;
            if ($columnEnd != $valueStart) {
                throw new MysqlGrammarException(11104,'batch insert sql syntax error, column number not fit');
            }
            //计算有几组values
            $tempColumnEnd = $columnEnd;
            $valueGroupNumber = 0;
            while (true) {
                if (!isset($this->wordList[$tempColumnEnd]))
                    break;
                $tempColumnEnd += $this->columnNumbers;
                $valueGroupNumber++;
            }
            if ($valueGroupNumber === 0) {
                throw new MysqlGrammarException(11105,'batch insert sql syntax error, values group is none');
            }
            //生成changeMapList
            $changeMap = array();
            for ($j=0;$j<$valueGroupNumber;$j++) {
                $temp = array();
                $valueGap = ($j*$this->columnNumbers)+$this->columnNumbers;
                for ($i=$columnStart;$i<$columnEnd;$i++) {
                    if (!isset($this->wordList[$i+$valueGap]))
                        throw new MysqlGrammarException(11106,'batch insert sql syntax error, column/value not match');
                    $temp[trim($this->wordList[$i],'\'')] = trim($this->wordList[$i+$valueGap],'\'');
                }
                $changeMap[] = $temp;
            }
            return $changeMap;
        }

        throw new MysqlGrammarException(11101,'insert sql syntax error, cant find keyword values/value/set');
    }

    /**
     * 分词，将sql解析成单词并填充到对应的关键字map,普通词组
     *
     * @throws MysqlGrammarException 11000,11001 sql为空,语法错误
     */
    protected function spiltWords()
    {
        $strLen = strlen($this->originSql);
        if ($strLen == 0) {
            throw new MysqlGrammarException(11000,'update sql is empty');
        }
        $letters = array();
        $symbolTemp = null;
        $countTemp = false;
        $symbolBracketsTemp = null;
        for ($i=0;$i<$strLen;$i++) {
            $letter = $this->originSql[$i];
            //匹配中的非对应配对字符，全部计入字符数组
            if ($symbolTemp !== null && !in_array($letter,MysqlGrammar::PAIR_SIGN)) {
                array_push($letters,$letter);
                continue;
            }
            //匹配中的配对字符，一致则生成单词，并作为单词计入，否则计入字符数组
            if ($symbolTemp !== null && in_array($letter,MysqlGrammar::PAIR_SIGN)) {
                if ($letter == $symbolTemp) {
                    if ($symbolBracketsTemp !== null && !$countTemp)
                        $this->processWord($letters,true,true);
                    else
                        $this->processWord($letters,true);
                    $symbolTemp = null;
                }else{
                    array_push($letters,$letter);
                }
                continue;
            }
            //非匹配中的分隔符，生成单词，如果需要记录单词则将符号计入
            if ($symbolTemp === null && in_array($letter,MysqlGrammar::SEPARATE_SIGN)) {
                //在括号中，
                if ($symbolBracketsTemp !== null && !$countTemp) {
                    $this->processWord($letters,false,true);
                }else{
                    $this->processWord($letters);
                }
                if ($letter == '(') {
                    $symbolBracketsTemp = $letter;
                }
                if ($letter == ')') {
                    $symbolBracketsTemp = null;
                    $countTemp = true;
                }
                if (in_array($letter,MysqlGrammar::JOIN_WORD_SIGN))
                    array_push($this->wordList,$letter);
                continue;
            }
            //非匹配中的匹配字符，更新标记，生成单词
            if ($symbolTemp === null && in_array($letter,MysqlGrammar::PAIR_SIGN)) {
                if ($symbolBracketsTemp !== null && !$countTemp) {
                    $this->processWord($letters,false,true);
                }else{
                    $this->processWord($letters);
                }
                $symbolTemp = $letter;
                continue;
            }
            //普通字符
            array_push($letters,$letter);
        }
        if (!empty($letters))
            $this->processWord($letters);
        if ($symbolTemp !== null)
            throw new MysqlGrammarException(11001,'update sql syntax error');
    }

    /**
     * 处理单词工具方法，对于order by作为一个单词的特殊处理，处于配对串中的字符串使用''包裹
     * @param &$letters array 字符数组引用
     * @param bool $inPair 单词是否处于引号或单引号之间，如果处于代表肯定不是关键字
     * @param bool $inBrackets 单词是否处于括号中，用于记录字段数
     */
    protected function processWord(&$letters,$inPair = false,$inBrackets = false)
    {
        $word = implode("",$letters);
        if (empty($word)) {
            return;
        }
        $upperWord = strtoupper($word);
        //特殊处理order by
        if ($upperWord == 'BY') {
            $lastWord = array_pop($this->wordList);
            if (strtoupper($lastWord) == 'ORDER') {
                $word = $lastWord.' '.$word;
                $upperWord = strtoupper($word);
            }else{
                //放回pop出的末尾单词
                array_push($this->wordList,$lastWord);
            }
        }
        //关键字
        if (in_array($upperWord,self::KEYWORDS) && !$inPair) {
            $this->keywordMap[$upperWord] = count($this->wordList);
        }else{
            if ($inPair)
                $word = '\''.$word.'\'';
            array_push($this->wordList,$word);
        }
        if ($inBrackets) {
            $this->columnNumbers++;
        }
        $letters = array();
    }
}