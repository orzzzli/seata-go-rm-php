<?php
/**
 * @user: ligongxiang (ligongxiang@rouchi.com)
 * @date : 2020/4/30
 * @version : 1.0
 * @file : InsertHandlerTest.php
 * @desc :
 */

use PHPUnit\Framework\TestCase;
use ResourceManager\Grammar\Mysql\Handlers\InsertHandler;

/**
 * @covers \ResourceManager\Grammar\Mysql\Handlers\InsertHandler
 * */
class InsertHandlerTest extends TestCase
{
    public function testTable()
    {
        $a = 'insert LOW_PRIORITY IGNORE into `set` set a="set"';
        $t = new InsertHandler($a);
        $this->assertEquals('set',$t->getSQLStruct()->getTable());

        $a = 'insert LOW_PRIORITY IGNORE `se t` set a="set"';
        $t = new InsertHandler($a);
        $this->assertEquals('se t',$t->getSQLStruct()->getTable());
    }

    public function testChangeMap()
    {
        $a = 'insert into `aaabbb` (`id`,`set`) value (null,\'ddd\');';
        $t = new InsertHandler($a);
        $this->assertEquals('null',$t->getSQLStruct()->getChangeColumnsListMap()[0]['id']);
        $this->assertEquals('ddd',$t->getSQLStruct()->getChangeColumnsListMap()[0]['set']);

        $a = 'insert into `aaabbb` (`id`,`set`) values (null,\'ddd\'),(null,"qwe"),(null,"d r");';
        $t = new InsertHandler($a);
        $this->assertEquals(3,count($t->getSQLStruct()->getChangeColumnsListMap()));
        $this->assertEquals('null',$t->getSQLStruct()->getChangeColumnsListMap()[0]['id']);
        $this->assertEquals('ddd',$t->getSQLStruct()->getChangeColumnsListMap()[0]['set']);
        $this->assertEquals('null',$t->getSQLStruct()->getChangeColumnsListMap()[1]['id']);
        $this->assertEquals('qwe',$t->getSQLStruct()->getChangeColumnsListMap()[1]['set']);
        $this->assertEquals('null',$t->getSQLStruct()->getChangeColumnsListMap()[2]['id']);
        $this->assertEquals('d r',$t->getSQLStruct()->getChangeColumnsListMap()[2]['set']);
    }
}