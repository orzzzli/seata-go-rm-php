<?php
/**
 * @user: ligongxiang (ligongxiang@rouchi.com)
 * @date : 2020/5/6
 * @version : 1.0
 * @file : MysqlGrammarTest.php
 * @desc :
 */

use PHPUnit\Framework\TestCase;
use ResourceManager\Grammar\Mysql\MysqlGrammar;
use ResourceManager\Grammar\Mysql\SQLStruct;

/**
 * @covers \ResourceManager\Grammar\Mysql\MysqlGrammar
 * */
class MysqlGrammarTest extends TestCase
{
    public function testAnalyse()
    {
        $a = 'delete LOW_PRIORITY IGNORE from `set`';
        $t = MysqlGrammar::analyse($a);
        $this->assertEquals(SQLStruct::SQL_TYPE_DELETE,$t->getSqlType());

        $a = 'insert LOW_PRIORITY IGNORE into `set` set a="set"';
        $t = MysqlGrammar::analyse($a);
        $this->assertEquals(SQLStruct::SQL_TYPE_INSERT,$t->getSqlType());

        $a = 'update LOW_PRIORITY IGNORE `set` set a="set" order by id desc';
        $t = MysqlGrammar::analyse($a);
        $this->assertEquals(SQLStruct::SQL_TYPE_UPDATE,$t->getSqlType());

        $a = 'select * from `set`';
        $t = MysqlGrammar::analyse($a);
        $this->assertEquals(SQLStruct::SQL_TYPE_SELECT,$t->getSqlType());
    }

    public function testGetSQLType()
    {
        $a = 'delete LOW_PRIORITY IGNORE from `set`';
        $t = MysqlGrammar::getSqlTypeConst($a);
        $this->assertEquals(SQLStruct::SQL_TYPE_DELETE,$t);

        $a = 'insert LOW_PRIORITY IGNORE into `set` set a="set"';
        $t = MysqlGrammar::getSqlTypeConst($a);
        $this->assertEquals(SQLStruct::SQL_TYPE_INSERT,$t);

        $a = 'update LOW_PRIORITY IGNORE `set` set a="set" order by id desc';
        $t = MysqlGrammar::getSqlTypeConst($a);
        $this->assertEquals(SQLStruct::SQL_TYPE_UPDATE,$t);

        $a = 'select * from `set`';
        $t = MysqlGrammar::getSqlTypeConst($a);
        $this->assertEquals(SQLStruct::SQL_TYPE_SELECT,$t);
    }
}