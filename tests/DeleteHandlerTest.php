<?php
/**
 * @user: ligongxiang (ligongxiang@rouchi.com)
 * @date : 2020/5/4
 * @version : 1.0
 * @file : DeleteHandlerTest.php
 * @desc :
 */

use PHPUnit\Framework\TestCase;
use ResourceManager\Grammar\Mysql\DeleteHandler;

/**
 * @covers \ResourceManager\Grammar\Mysql\DeleteHandler
 * */
class DeleteHandlerTest extends TestCase
{
    public function testTable()
    {
        $a = 'delete LOW_PRIORITY IGNORE from `set`';
        $t = new DeleteHandler($a);
        $this->assertEquals('set',$t->getSQLStruct()->getTable());

        $a = 'delete from `s et`';
        $t = new DeleteHandler($a);
        $this->assertEquals('s et',$t->getSQLStruct()->getTable());

        $a = 'delete from s';
        $t = new DeleteHandler($a);
        $this->assertEquals('s',$t->getSQLStruct()->getTable());
    }

    public function testConditionStr()
    {
        $a = 'delete LOW_PRIORITY IGNORE  from `set` order by id desc';
        $t = new DeleteHandler($a);
        $this->assertEquals('ORDER BY id desc ',$t->getSQLStruct()->getConditionsStr());

        $a = 'delete from `s et` where c = 1000 and aabba = "dqec dd" limit 10';
        $t = new DeleteHandler($a);
        $this->assertEquals('WHERE c = 1000 and aabba = \'dqec dd\' LIMIT 10 ',$t->getSQLStruct()->getConditionsStr());
    }
}