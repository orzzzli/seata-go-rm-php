<?php
/**
 * @user: ligongxiang (ligongxiang@rouchi.com)
 * @date : 2020/5/7
 * @version : 1.0
 * @file : MysqlTransactionTest.php
 * @desc :
 */

use PHPUnit\Framework\TestCase;
use ResourceManager\Connector\Mysql\MysqlConnector;
use ResourceManager\LocalTransactions\MysqlTransaction;

/**
 * @covers \ResourceManager\LocalTransactions\MysqlTransaction
 * */
class MysqlTransactionTest extends TestCase
{
    protected function initConnector()
    {
        return new MysqlConnector($_ENV['host'],$_ENV['username'],$_ENV['password'],$_ENV['database'],$_ENV['port'],$_ENV['charset']);
    }

    public function testStartAndCommit()
    {
        $transaction = new MysqlTransaction($this->initConnector(),'test','test1');
        $transaction->start();
        $newConn = $this->initConnector();
        $sql = 'SELECT * FROM `transaction_local` WHERE tid = "test";';
        $res = $newConn->query($sql);
        $this->assertEquals(0,count($res));
        $transaction->commit();
        $res = $newConn->query($sql);
        $this->assertEquals(1,count($res));
        $delSql = 'DELETE FROM `transaction_local` where tid = "test";';
        $newConn->delete($delSql);
    }

    public function testStartAndRollback()
    {
        $transaction = new MysqlTransaction($this->initConnector(),'test','test1');
        $transaction->start();
        $newConn = $this->initConnector();
        $sql = 'SELECT * FROM `transaction_local` WHERE tid = "test";';
        $res = $newConn->query($sql);
        $this->assertEquals(0,count($res));
        $transaction->rollback();
        $res = $newConn->query($sql);
        $this->assertEquals(0,count($res));
    }

    public function testDoingInsertOnce()
    {
        $transaction = new MysqlTransaction($this->initConnector(),'test_insert_once','test1');
        $transaction->start();
        $sql = 'SELECT * FROM `test_transaction` WHERE test_a = "a";';
        $transaction->doing($sql);
        $sqlb = 'INSERT INTO `test_transaction` (`test_a`) value ("a");';
        $transaction->doing($sqlb);
        $sqlc = 'INSERT INTO `test_transaction` (`test_a`) value ("b");';
        $transaction->doing($sqlc);
        $transaction->commit();
        $newConn = $this->initConnector();
        $sql = 'SELECT * FROM `test_transaction` WHERE test_a = "a";';
        $res = $newConn->query($sql);
        $this->assertEquals(1,count($res));
        $sql = 'SELECT * FROM `test_transaction` WHERE test_a = "b";';
        $res = $newConn->query($sql);
        $this->assertEquals(1,count($res));
        $sql = 'SELECT * FROM `transaction_local` WHERE tid = "test_insert_once";';
        $res = $newConn->query($sql);
        $this->assertEquals(1,count($res));
        $this->assertEquals(2,$res[0]['status']);
        $sql = 'SELECT * FROM `transaction_undo` WHERE tid = "test_insert_once";';
        $res = $newConn->query($sql);
        $this->assertEquals(2,count($res));
        $this->assertEquals('',$res[0]['before']);
        $this->assertEquals('a',$res[0]['after']);
        $this->assertEquals('',$res[1]['before']);
        $this->assertEquals('b',$res[1]['after']);
        $delSql = 'DELETE FROM `test_transaction` where test_a = "a";';
        $newConn->delete($delSql);
        $delSql = 'DELETE FROM `test_transaction` where test_a = "b";';
        $newConn->delete($delSql);
        $delSql = 'DELETE FROM `transaction_local` where tid = "test_insert_once";';
        $newConn->delete($delSql);
        $delSql = 'DELETE FROM `transaction_undo` where tid = "test_insert_once";';
        $newConn->delete($delSql);
    }

    public function testDoingInsertBatch()
    {
        $transaction = new MysqlTransaction($this->initConnector(),'test_insert_batch','test1');
        $transaction->start();
        $sql = 'SELECT * FROM `test_transaction` WHERE test_a = "a";';
        $transaction->doing($sql);
        $sqlb = 'INSERT INTO `test_transaction` (`test_a`) values ("a"),("b"),("c");';
        $transaction->doing($sqlb);
        $transaction->commit();
        $newConn = $this->initConnector();
        $sql = 'SELECT * FROM `test_transaction` WHERE test_a = "a";';
        $res = $newConn->query($sql);
        $this->assertEquals(1,count($res));
        $sql = 'SELECT * FROM `test_transaction` WHERE test_a = "b";';
        $res = $newConn->query($sql);
        $this->assertEquals(1,count($res));
        $sql = 'SELECT * FROM `test_transaction` WHERE test_a = "c";';
        $res = $newConn->query($sql);
        $this->assertEquals(1,count($res));
        $sql = 'SELECT * FROM `transaction_local` WHERE tid = "test_insert_batch";';
        $res = $newConn->query($sql);
        $this->assertEquals(1,count($res));
        $this->assertEquals(2,$res[0]['status']);
        $sql = 'SELECT * FROM `transaction_undo` WHERE tid = "test_insert_batch";';
        $res = $newConn->query($sql);
        $this->assertEquals(3,count($res));
        $this->assertEquals('',$res[0]['before']);
        $this->assertEquals('a',$res[0]['after']);
        $this->assertEquals('',$res[1]['before']);
        $this->assertEquals('b',$res[1]['after']);
        $this->assertEquals('',$res[2]['before']);
        $this->assertEquals('c',$res[2]['after']);
        $delSql = 'DELETE FROM `test_transaction` where test_a = "a";';
        $newConn->delete($delSql);
        $delSql = 'DELETE FROM `test_transaction` where test_a = "b";';
        $newConn->delete($delSql);
        $delSql = 'DELETE FROM `test_transaction` where test_a = "c";';
        $newConn->delete($delSql);
        $delSql = 'DELETE FROM `transaction_local` where tid = "test_insert_batch";';
        $newConn->delete($delSql);
        $delSql = 'DELETE FROM `transaction_undo` where tid = "test_insert_batch";';
        $newConn->delete($delSql);
    }

    public function testDoingUpdate()
    {
        $transaction = new MysqlTransaction($this->initConnector(),'test_update','test1');
        $transaction->start();
        $sql = 'SELECT * FROM `test_transaction` WHERE test_a = "a";';
        $transaction->doing($sql);
        $sqlb = 'INSERT INTO `test_transaction` (`test_a`) value ("a");';
        $transaction->doing($sqlb);
        $sqlc = 'UPDATE `test_transaction` SET `test_a` = "c";';
        $transaction->doing($sqlc);
        $transaction->commit();
        $newConn = $this->initConnector();
        $sql = 'SELECT * FROM `test_transaction` WHERE test_a = "a";';
        $res = $newConn->query($sql);
        $this->assertEquals(0,count($res));
        $sql = 'SELECT * FROM `test_transaction` WHERE test_a = "c";';
        $res = $newConn->query($sql);
        $this->assertEquals(1,count($res));
        $sql = 'SELECT * FROM `transaction_local` WHERE tid = "test_update";';
        $res = $newConn->query($sql);
        $this->assertEquals(1,count($res));
        $this->assertEquals(2,$res[0]['status']);
        $sql = 'SELECT * FROM `transaction_undo` WHERE tid = "test_update";';
        $res = $newConn->query($sql);
        $this->assertEquals(2,count($res));
        $this->assertEquals('',$res[0]['before']);
        $this->assertEquals('a',$res[0]['after']);
        $this->assertEquals('a',$res[1]['before']);
        $this->assertEquals('c',$res[1]['after']);
        $delSql = 'DELETE FROM `test_transaction` where test_a = "c";';
        $newConn->delete($delSql);
        $delSql = 'DELETE FROM `transaction_local` where tid = "test_update";';
        $newConn->delete($delSql);
        $delSql = 'DELETE FROM `transaction_undo` where tid = "test_update";';
        $newConn->delete($delSql);
    }

    public function testDoingDelete()
    {
        $transaction = new MysqlTransaction($this->initConnector(),'test_delete','test1');
        $transaction->start();
        $sqlb = 'INSERT INTO `test_transaction` (`test_a`,`test_b`) value ("a","b");';
        $transaction->doing($sqlb);
        $transaction->commit();
        $transaction2 = new MysqlTransaction($this->initConnector(),'test_delete','test1');
        $transaction2->start();
        $sqlc = 'DELETE FROM `test_transaction` where test_a = "a";';
        $transaction2->doing($sqlc);
        $transaction2->commit();
        $newConn = $this->initConnector();
        $sql = 'SELECT * FROM `test_transaction` WHERE test_a = "a";';
        $res = $newConn->query($sql);
        $this->assertEquals(0,count($res));
        $sql = 'SELECT * FROM `transaction_local` WHERE tid = "test_delete";';
        $res = $newConn->query($sql);
        $this->assertEquals(2,count($res));
        $this->assertEquals(2,$res[0]['status']);
        $this->assertEquals(2,$res[1]['status']);
        $sql = 'SELECT * FROM `transaction_undo` WHERE tid = "test_delete";';
        $res = $newConn->query($sql);
        $this->assertEquals(2,count($res));
        $this->assertEquals('',$res[0]['before']);
        $this->assertEquals('a,b',$res[0]['after']);
        $this->assertEquals('a,b',$res[1]['before']);
        $this->assertEquals('',$res[1]['after']);
        $delSql = 'DELETE FROM `test_transaction` where test_a = "a";';
        $newConn->delete($delSql);
        $delSql = 'DELETE FROM `transaction_local` where tid = "test_delete";';
        $newConn->delete($delSql);
        $delSql = 'DELETE FROM `transaction_undo` where tid = "test_delete";';
        $newConn->delete($delSql);
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
        $transaction->grollback();
        $newConn = $this->initConnector();
        $sql = 'SELECT * FROM `test_transaction` WHERE test_a = "a";';
        $res = $newConn->query($sql);
        $this->assertEquals(0,count($res));
        $sql = 'SELECT * FROM `test_transaction` WHERE test_a = "c";';
        $res = $newConn->query($sql);
        $this->assertEquals(0,count($res));
        $delSql = 'DELETE FROM `transaction_local` where tid = "test_grollback";';
        $newConn->delete($delSql);
        $delSql = 'DELETE FROM `transaction_undo` where tid = "test_grollback";';
        $newConn->delete($delSql);
    }
}