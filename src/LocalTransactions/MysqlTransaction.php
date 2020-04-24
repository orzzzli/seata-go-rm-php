<?php
/**
 * @user: ligongxiang (ligongxiang@rouchi.com)
 * @date : 2020/4/24
 * @version : 1.0
 * @file : LocalTransaction.php
 * @desc :
 */

namespace ResourceManager\LocalTransactions;


use ResourceManager\Interfaces\LocalTransaction;

class MysqlTransaction implements LocalTransaction
{
    public function start()
    {
        // TODO: Implement start() method.
    }

    public function doing()
    {
        // TODO: Implement doing() method.
    }

    public function commit()
    {
        // TODO: Implement commit() method.
    }

    public function rollback()
    {
        // TODO: Implement rollback() method.
    }

}