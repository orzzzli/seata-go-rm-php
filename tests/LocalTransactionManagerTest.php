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
use ResourceManager\LocalTransactions\MysqlTransaction;
use ResourceManager\Connector\Mysql\MysqlConnector;

/**
 * @covers \ResourceManager\LocalTransactionManager
 * */
class LocalTransactionManagerTest extends TestCase
{
    protected function getInstance()
    {
        return LocalTransactionManager::getInstance($_ENV['host'],$_ENV['username'],$_ENV['password'],$_ENV['database'],$_ENV['port'],$_ENV['charset']);
    }

    protected function initConnector()
    {
        return new MysqlConnector($_ENV['host'],$_ENV['username'],$_ENV['password'],$_ENV['database'],$_ENV['port'],$_ENV['charset']);
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

    public function testGlobalRollback()
    {
        $transaction = new MysqlTransaction($this->initConnector(),'test_grollback','test1');
        $transaction->start();
        $sqlb = 'INSERT INTO `test_transaction` (`test_a`,`test_b`) value ("a","b");';
        $transaction->doing($sqlb);
        $sqlc = 'UPDATE `test_transaction` SET `test_a` = "c", `test_b` = "d";';
        $transaction->doing($sqlc);
        $sqlb = 'INSERT INTO `test_transaction` (`test_a`,`test_b`) value ("e","f");';
        $transaction->doing($sqlb);
        $sqlb = 'DELETE FROM `test_transaction` where test_a = "e";';
        $transaction->doing($sqlb);
        $transaction->commit();
        $manager = $this->getInstance();
        $manager->grollback('test_grollback');
        $sql = 'SELECT * FROM `test_transaction` WHERE test_a = "a";';
        $res = $manager->do($sql);
        $this->assertEquals(0,count($res));
        $sql = 'SELECT * FROM `test_transaction` WHERE test_a = "c";';
        $res = $manager->do($sql);
        $this->assertEquals(0,count($res));
        $delSql = 'DELETE FROM `transaction_local` where tid = "test_grollback";';
        $manager->do($delSql);
        $delSql = 'DELETE FROM `transaction_undo` where tid = "test_grollback";';
        $manager->do($delSql);
    }
}