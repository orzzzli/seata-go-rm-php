<?php
/**
 * @user: ligongxiang (ligongxiang@rouchi.com)
 * @date : 2020/5/7
 * @version : 1.0
 * @file : MysqlConnectorTest.php
 * @desc :
 */

use PHPUnit\Framework\TestCase;
use ResourceManager\Connector\Mysql\Connector;

/**
 * @covers \ResourceManager\Connector\Mysql\Connector
 * */
class MysqlConnectorTest extends TestCase
{
    const HOST = '127.0.0.1';
    const UNAME = 'root';
    const PASS = '';
    const DBNAME = 'test';
    const PORT = '3306';
    const CHARSET = 'utf8';

    public function testQuery()
    {
        $sql = 'SELECT * FROM `user` WHERE id = 1;';
        $connect = new Connector(self::HOST,self::UNAME,self::PASS,self::DBNAME,self::PORT,self::CHARSET);
        $res = $connect->query($sql);
        $this->assertEquals(1,$res[0]['id']);

        $sql = 'SELECT * FROM `user` limit 2;';
        $res = $connect->query($sql);
        $this->assertEquals(2,count($res));
    }

    public function testInsert()
    {
        $sql = 'INSERT INTO `transaction_local` (`tid`,`desc`) value ("1","test")';
        $connect = new Connector(self::HOST,self::UNAME,self::PASS,self::DBNAME,self::PORT,self::CHARSET);
        list($count,$lastId) = $connect->insert($sql);
        $this->assertEquals(1,$count);
        $this->assertNotEmpty($lastId);

        $sql = 'INSERT INTO `transaction_local` (`tid`,`desc`) values ("1","test"),("2","test2")';
        list($count,$lastId) = $connect->insert($sql);
        $this->assertEquals(2,$count);
        $this->assertNotEmpty($lastId);
    }

    public function testUpdate()
    {
        $sql = 'UPDATE `transaction_local` SET `desc` = "testUpdate" limit 1';
        $connect = new Connector(self::HOST,self::UNAME,self::PASS,self::DBNAME,self::PORT,self::CHARSET);
        $count = $connect->update($sql);
        $this->assertEquals(1,$count);

        $sql = 'UPDATE `transaction_local` SET `desc` = "test" limit 1';
        $count = $connect->update($sql);
        $this->assertEquals(1,$count);
    }

    public function testDelete()
    {
        $sql = 'DELETE FROM `transaction_local` limit 1';
        $connect = new Connector(self::HOST,self::UNAME,self::PASS,self::DBNAME,self::PORT,self::CHARSET);
        $count = $connect->delete($sql);
        $this->assertEquals(1,$count);
    }

    public function testTransactionCommit()
    {
        $connect = new Connector(self::HOST,self::UNAME,self::PASS,self::DBNAME,self::PORT,self::CHARSET);
        $connect->begin();
        $sql = 'INSERT INTO `transaction_local` (`tid`,`desc`) value ("1","test")';
        list($count,$lastId) = $connect->insert($sql);
        $connect->commit();
        $sql = 'SELECT * FROM `transaction_local` WHERE id = '.$lastId.';';
        $res = $connect->query($sql);
        $this->assertEquals(1,count($res));
    }

    public function testTransactionRollback()
    {
        $connect = new Connector(self::HOST,self::UNAME,self::PASS,self::DBNAME,self::PORT,self::CHARSET);
        $connect->begin();
        $sql = 'INSERT INTO `transaction_local` (`tid`,`desc`) value ("1","test")';
        list($count,$lastId) = $connect->insert($sql);
        $connect->rollback();
        $sql = 'SELECT * FROM `transaction_local` WHERE id = '.$lastId.';';
        $res = $connect->query($sql);
        $this->assertEquals(0,count($res));
    }
}