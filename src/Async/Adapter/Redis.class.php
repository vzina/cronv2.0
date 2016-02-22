<?php
/**
 * Redis.php.
 * Author: yeweijian
 * E-mail: yeweijian@hoolai.com
 * Date: 2016/1/30
 * Time: 14:23
 */

namespace EasyCron\Async\Adapter;


use EasyCron\Async\ClientAdapter;

class Redis implements ClientAdapter
{
    /**
     * 函数原型
     * @var string $_method 必须为合法的Redis指令，详细参见Redis指令列表(http://redis.io/commands)
     */
    protected $_method;
    /**
     * 执行参数
     * @var array $_argv 所有元素必须为字符串
     */
    protected $_argv;
    protected $_config = ['host' => '127.0.0.1', 'port' => 6379, 'db' => 0];

    function __construct(array $config = array())
    {
        $this->_config = array_merge($this->_config, $config);
    }

    public function select($index = 0)
    {
        $this->_config['db'] = intval($index);
    }

    public function send(callable $callback)
    {
        $client = new \swoole_redis();
        $calltime = microtime(true);
        $key = md5($calltime . $this->_config['host'] . $this->_config['port'] . rand(0, 10000));

        $method = $this->_method;
        $params = $this->_argv;
        $db = (int)$this->_config['db'];

        array_push($params, function (\swoole_redis $redis, $result) use (
            $callback, $key, $calltime
        ) {
            call_user_func_array($callback, [
                'r' => 0, 'key' => $key, 'calltime' => $calltime, 'data' => $result
            ]);
            $redis->close();
        });
        
        $client->connect(
            $this->_config['host'],
            $this->_config['port'],
            function (\swoole_redis $client, $result) use ($method, $params, $db) {
                $callback = end($params);

                if ($result == false) {
                    call_user_func_array($callback, [
                        $client, ['errCode' => $client->errCode, 'errMsg' => $client->errMsg]
                    ]);
                    return;
                }

                if ($db) {
                    $client->select($db, function (\swoole_redis $client, $result) use ($method, $params, $callback) {
                        if ($result == false) {
                            call_user_func_array($callback, [
                                $client, ['errCode' => $client->errCode, 'errMsg' => $client->errMsg]
                            ]);
                            return;
                        }
                        call_user_func_array([$client, $method], $params);
                    });
                    return;
                }

                call_user_func_array([$client, $method], $params);
            });

        unset($this->_method, $this->_argv);
    }

    public function __call($method, $args)
    {
        $this->_method = $method;
        $this->_argv = $args;
        return $this;
    }
}