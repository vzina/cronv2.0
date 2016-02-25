<?php
namespace App\Workers;


use EasyCron\Worker\WorkerBase;

class ReadBookWorker extends WorkerBase
{
    /**
     * 运行入口
     * @param $task
     * @return mixed
     */
    public function Run($task)
    {
        /** 主动关闭进程 */
        if ('exit' == $task) {
            $this->worker->exit(0);
            return;
        }

        $this->_run($task);
    }

    /**
     * 此处不建议使用异步操作
     * @param $task
     */
    protected function _run($task)
    {
        echo __CLASS__ . " run : " . $task . "\n";
    }
}