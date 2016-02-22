<?php
/**
 * Mysql.php.
 * Author: yeweijian
 * E-mail: yeweijian@hoolai.com
 * Date: 2016/1/15
 * Time: 17:10
 */

namespace EasyCron\Async\Adapter;


use EasyCron\Async\ClientAdapter;

class Mysql implements ClientAdapter
{
    protected $db;
    protected $sql;
    protected $key;
    protected $conf;
    protected $callback;
    protected $calltime;

    /**
     * sqlConf = array(
     * 'host' => ,
     * 'port' => ,
     * 'username' => ,
     * 'password' => ,
     * 'dbname' => ,
     * 'charset' => ,
     * );
     * [__construct 构造函数，初始化mysqli]
     * @param [type] $sqlConf [description]
     */
    public function __construct($sqlConf)
    {
        $this->db = new \mysqli();
        $this->conf = $sqlConf;
    }


    /**
     * [send 兼容Base类封装的send方法，调度器可以不感知client类型]
     * @param callable $callback
     */
    public function send(callable $callback)
    {
        if (!isset($this->db)) {
            \Main::log_write("[error] db not init \n");
            return;
        }

        $config = $this->conf;
        $this->callback = $callback;
        $this->calltime = microtime(true);
        $this->key = md5($this->calltime . $config['host'] . $config['port'] . rand(0, 10000));

        $this->db->connect($config['host'], $config['username'], $config['password'], $config['dbname'], $config['port']);

        if (empty($config['charset'])) {
            $config['charset'] = 'utf8';
        }
        $this->db->set_charset($config['charset']);
        \swoole_mysql_query($this->db, $this->sql, array($this, 'onSqlReady'));
    }

    /**
     * [query 使用者调用该接口，返回当前mysql实例]
     * @param string $sql
     * @return \Generator
     */
    public function query($sql)
    {
        $this->sql = $sql;
        yield $this;
    }

    /**
     * [onSqlReady eventloog异步回调函数]
     * @param \mysqli $db
     * @param mixed $r
     */
    public function onSqlReady(\mysqli $db, $r)
    {
        if ($r === false) {
            $res = ['msg' => $db->_error, 'code' => $db->_errno];
        } //执行成功，update/delete/insert语句，没有结果集
        elseif ($r === true) {
            $res = ['affected_rows' => $db->_affected_rows, 'insert_id' => $db->_insert_id];
        } //执行成功，$r是结果集数组
        else {
            $res = $r;
        }

        call_user_func_array($this->callback, [
            'r' => 0,
            'key' => $this->key,
            'calltime' => $this->calltime,
            'data' => $res
        ]);
    }
}