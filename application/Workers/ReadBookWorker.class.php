<?php
namespace App\Workers;


use EasyCron\Async\Adapter\Mysql;
use EasyCron\Async\Coroutine\EndTask;
use EasyCron\TaskHandle;
use EasyCron\Worker\WorkerBase;


/**
 * Created by PhpStorm.
 * User: ClownFish 187231450@qq.com
 * Date: 15-11-4
 * Time: 下午10:02
 */
class ReadBookWorker extends WorkerBase
{

    /**
     * 运行入口
     * @param $task
     * @return mixed
     */
    public function Run($task)
    {
        TaskHandle::parse($this->_run());
        /** 异步执行不需要关闭进程 */
        if (!TaskHandle::$async_mode) {
            TaskHandle::$async_mode = false;
            $this->worker->exit(0);
        }
    }

    protected function _run()
    {
        $mysql = new Mysql([
            'host' => '192.168.17.251',
            'port' => '3306',
            'user' => 'root',
            'password' => '0987abc123',
            'database' => 'test_123',
            'charset' => 'utf8',
        ]);
        $data = (yield $mysql->query('select * from user limit 1;'));
        echo var_export($data, true) . "\n";
        yield;
//        $result = new EndTask($this->worker);
//        yield $result;
    }
}