<?php
/**
 * @user: ligongxiang (ligongxiang@rouchi.com)
 * @date : 2020/4/30
 * @version : 1.0
 * @file : UpdateHandler.php
 * @desc :
 */

namespace ResourceManager\Grammar\Mysql\Handlers;

use ResourceManager\Exceptions\MysqlGrammarException;
use ResourceManager\Grammar\Mysql\MysqlGrammar;
use ResourceManager\Grammar\Mysql\SQLStruct;
/**
 * Update语句实际解析类
 * 错误码：[11000-11100)
 * */
class UpdateHandler
{
    const KEYWORDS = [
        'UPDATE',
        'LOW_PRIORITY',
        'IGNORE',
        'SET',
        'WHERE',
        'ORDER BY',
        'LIMIT',
    ];
    protected $sqlType = 'UPDATE';
    protected $sqlStruct = null;
    protected $originSql = '';
    protected $keywordMap = array();
    protected $wordList = array();

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
        $this->sqlStruct->setConditionsStr($this->findFullConditionStr());
        $this->sqlStruct->setChangeColumnsListMap(array($this->findChangeMap()));
        return $this->sqlStruct;
    }

    /**
     * 根据keywordMap找到表名，去除可能分词时自动包裹的'
     *
     * @throws MysqlGrammarException 11002,语法错误，找不到表名
     */
    protected function findTable()
    {
        if (isset($this->keywordMap['IGNORE']))
            return trim($this->wordList[$this->keywordMap['IGNORE']],'\'');
        if (isset($this->keywordMap['LOW_PRIORITY']))
            return trim($this->wordList[$this->keywordMap['LOW_PRIORITY']],'\'');
        if (isset($this->keywordMap['UPDATE']))
            return trim($this->wordList[$this->keywordMap['UPDATE']],'\'');
        throw new MysqlGrammarException(11002,'update sql syntax error, cant find table');
    }

    /**
     * 根据keywordMap找到where条件语句
     */
    protected function findFullConditionStr()
    {
        //构造一个反向map
        $revertKeywordMap = array();
        foreach ($this->keywordMap as $k=>$v) {
            $revertKeywordMap[$v] = $k;
        }
        $fullConditionStr = '';
        if (isset($this->keywordMap['WHERE'])) {
            for ($i=$this->keywordMap['WHERE'];$i<count($this->wordList);$i++) {
                if (isset($revertKeywordMap[$i])) {
                    $fullConditionStr .= $revertKeywordMap[$i].' '.$this->wordList[$i].' ';
                    continue;
                }
                $fullConditionStr .= $this->wordList[$i].' ';
            }
            return $fullConditionStr;
        }
        if (isset($this->keywordMap['ORDER BY'])) {
            for ($i=$this->keywordMap['ORDER BY'];$i<count($this->wordList);$i++) {
                if (isset($revertKeywordMap[$i])) {
                    $fullConditionStr .= $revertKeywordMap[$i].' '.$this->wordList[$i].' ';
                    continue;
                }
                $fullConditionStr .= $this->wordList[$i].' ';
            }
            return $fullConditionStr;
        }
        if (isset($this->keywordMap['LIMIT'])) {
            for ($i=$this->keywordMap['LIMIT'];$i<count($this->wordList);$i++) {
                if (isset($revertKeywordMap[$i])) {
                    $fullConditionStr .= $revertKeywordMap[$i].' '.$this->wordList[$i].' ';
                    continue;
                }
                $fullConditionStr .= $this->wordList[$i].' ';
            }
            return $fullConditionStr;
        }
        return $fullConditionStr;
    }

    /**
     * 根据keywordMap找到set修改的字段arr，设值时，去掉分词时可能设置的'
     *
     * @throws MysqlGrammarException 11003,语法错误，找不到set关键字
     */
    protected function findChangeMap()
    {
        if (!isset($this->keywordMap['SET']))
            throw new MysqlGrammarException(11003,'update sql syntax error, cant find keyword set');
        $changeMap = array();
        $start = $this->keywordMap['SET'];
        $end = count($this->wordList);
        if (isset($this->keywordMap['LIMIT'])) {
            $end = $this->keywordMap['LIMIT'];
        }
        if (isset($this->keywordMap['ORDER BY'])) {
            $end = $this->keywordMap['ORDER BY'];
        }
        if (isset($this->keywordMap['WHERE'])) {
            $end = $this->keywordMap['WHERE'];
        }
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
        return $changeMap;
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
                    $this->processWord($letters,true);
                    $symbolTemp = null;
                }else{
                    array_push($letters,$letter);
                }
                continue;
            }
            //非匹配中的分隔符，生成单词，如果需要记录单词则将符号计入
            if ($symbolTemp === null && in_array($letter,MysqlGrammar::SEPARATE_SIGN)) {
                $this->processWord($letters);
                if (in_array($letter,MysqlGrammar::JOIN_WORD_SIGN))
                    array_push($this->wordList,$letter);
                continue;
            }
            //非匹配中的匹配字符，更新标记，生成单词
            if ($symbolTemp === null && in_array($letter,MysqlGrammar::PAIR_SIGN)) {
                $this->processWord($letters);
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
     */
    protected function processWord(&$letters,$inPair = false)
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
        $letters = array();
    }
}