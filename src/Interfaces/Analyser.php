<?php
/**
 * @user: ligongxiang (ligongxiang@rouchi.com)
 * @date : 2020/4/24
 * @version : 1.0
 * @file : Analyser.php
 * @desc :
 */

namespace ResourceManager\Interfaces;


interface Analyser
{
    public function buildBeforeImage();
    public function buildAfterImage();
    public function storeImage();
}