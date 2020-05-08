<?php
/**
 * @user: ligongxiang (ligongxiang@rouchi.com)
 * @date : 2020/5/8
 * @version : 1.0
 * @file : LocalTransactionManagerTest.php
 * @desc :
 */

use PHPUnit\Framework\TestCase;
use ResourceManager\LocalTransactionManager;

/**
 * @covers \ResourceManager\LocalTransactionManager
 * */
class LocalTransactionManagerTest extends TestCase
{
    public function getInstance()
    {
        return LocalTransactionManager::getInstance($_ENV['host'],$_ENV['username'],$_ENV['password'],$_ENV['database'],$_ENV['port'],$_ENV['charset']);
    }

    public function testJustDo()
    {
        $manager = $this->getInstance();
        $manager->do("INSERT INTO `test_transaction` (test_a,test_b) value ('aa','bb')");
        $res = $manager->do("SELECT * FROM `test_transaction` where test_a = 'aa'");
        $this->assertEquals(1,count($res));
        $manager->do("DELETE FROM `test_transaction` where test_a = 'aa'");
        $res = $manager->do("SELECT * FROM `test_transaction` where test_a = 'aa'");
        $this->assertEquals(0,count($res));
    }
}