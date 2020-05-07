<?php
/**
 * @user: ligongxiang (ligongxiang@rouchi.com)
 * @date : 2020/5/7
 * @version : 1.0
 * @file : Connector.php
 * @desc :
 */

namespace ResourceManager\Connector\Mysql;

/**
 * Mysql连接类
 *  考虑到读写库以及跨库的情况，非单例
 * */
class MysqlConnector
{
    protected $_username = '';
    protected $_password = '';
    protected $_dsn = '';
    protected $_instance = null;

    /**
     * 初始化SQL连接
     * @param string $host host
     * @param string $username  用户名
     * @param string $password  密码
     * @param string $dbname    库名
     * @param string $port  端口，默认3306
     * @param string $charset   字符集，默认utf8
     */
    public function __construct(string $host,string $username,string $password,string $dbname,string $port = '3306',string $charset = 'utf8')
    {
        $this->_username = $username;
        $this->_password = $password;
        $this->_dsn = "mysql:host=$host;dbname=$dbname;port=$port;charset=$charset";
        $this->_instance = new \PDO($this->_dsn,$this->_username,$this->_password);
    }

    /**
     * 查询
     * @param string $sql select语句
     * @return array    二维数组，list+kvmap
     */
    public function query(string $sql)
    {
        $stmt = $this->_instance->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * 插入
     * @param string $sql insert语句
     * @return array    [影响的行数，lastInsertId]
     */
    public function insert(string $sql)
    {
        $count = $this->_instance->exec($sql);
        $lastId = $this->_instance->lastInsertId();
        return [$count,$lastId];
    }

    /**
     * 更新
     * @param string $sql update语句
     * @return false|int    影响的行数
     */
    public function update(string $sql)
    {
        return $this->_instance->exec($sql);
    }

    /**
     * 删除
     * @param string $sql delete语句
     * @return false|int    影响的行数
     */
    public function delete(string $sql)
    {
        return $this->_instance->exec($sql);
    }

    /**
     * 开启事务
     * */
    public function begin()
    {
        $this->_instance->beginTransaction();
    }

    /**
     * 提交
     * */
    public function commit()
    {
        $this->_instance->commit();
    }

    /**
     * 回滚
     * */
    public function rollback()
    {
        $this->_instance->rollBack();
    }
}