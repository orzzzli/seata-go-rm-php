<?php
/**
 * @user: ligongxiang (ligongxiang@rouchi.com)
 * @date : 2020/4/24
 * @version : 1.0
 * @file : LocalTransaction.php
 * @desc :
 */

namespace ResourceManager\Interfaces;


interface LocalTransaction
{
    public function start();
    public function doing();
    public function commit();
    public function rollback();
}