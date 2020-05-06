<?php
/**
 * @user: ligongxiang (ligongxiang@rouchi.com)
 * @date : 2020/4/30
 * @version : 1.0
 * @file : UpdateHandlerTest.php
 * @desc :
 */

use PHPUnit\Framework\TestCase;
use ResourceManager\Grammar\Mysql\Handlers\UpdateHandler;

/**
 * @covers \ResourceManager\Grammar\Mysql\Handlers\UpdateHandler
 * */
class UpdateHandlerTest extends TestCase
{
    public function testTable()
    {
        $a = 'update LOW_PRIORITY IGNORE `set` set a="set" order by id desc';
        $t = new UpdateHandler($a);
        $this->assertEquals('set',$t->getSQLStruct()->getTable());

        $a = 'update `s et` set a="set" order by id desc';
        $t = new UpdateHandler($a);
        $this->assertEquals('s et',$t->getSQLStruct()->getTable());
    }

    public function testConditionStr()
    {
        $a = 'update LOW_PRIORITY IGNORE `set` set a="set" order by id desc';
        $t = new UpdateHandler($a);
        $this->assertEquals('ORDER BY id desc ',$t->getSQLStruct()->getConditionsStr());

        $a = 'update `s et` set a="set" where c = 1000 and aabba = "dqec dd" limit 10';
        $t = new UpdateHandler($a);
        $this->assertEquals('WHERE c = 1000 and aabba = \'dqec dd\' LIMIT 10 ',$t->getSQLStruct()->getConditionsStr());
    }

    public function testChangeMap()
    {
        $a = 'update LOW_PRIORITY IGNORE `set` set a="set" order by id desc';
        $t = new UpdateHandler($a);
        $this->assertEquals('set',$t->getSQLStruct()->getChangeColumnsListMap()[0]['a']);

        $a = 'update `s et` set c = 1000,aabba = "dqec dd" where c = 1000 and aabba = "dqec dd" limit 10';
        $t = new UpdateHandler($a);
        $this->assertEquals('1000',$t->getSQLStruct()->getChangeColumnsListMap()[0]['c']);
        $this->assertEquals('dqec dd',$t->getSQLStruct()->getChangeColumnsListMap()[0]['aabba']);
    }
}