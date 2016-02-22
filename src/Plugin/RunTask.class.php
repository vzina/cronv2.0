<?php
/**
 * SyncTask.class.php.
 * Author: yeweijian
 * E-mail: yeweijian@hoolai.com
 * Date: 2016/2/19
 * Time: 14:38
 */

namespace EasyCron\Plugin;


use EasyCron\TaskHandle;

class RunTask extends PluginBase
{
    protected $_async_mode = false;

    public function run($task)
    {
        try {
            TaskHandle::parse($this->_run($task));
        } catch (\Exception $e) {
            \Main::log_write($e->getMessage());
            $this->worker->exit(1);
        }

        /** 异步执行不需要关闭进程 */
        if (!TaskHandle::$async_mode) {
            TaskHandle::$async_mode = false;
            $this->worker->exit(0);
        }
    }

    protected function _run($task)
    {
        if (empty($task['class'])
            || empty($task['func'])
            || !class_exists($task['class'])
        ) {
            throw new \Exception("[system error]");
        }

        $obj = new $task['class'];

        if (!is_callable([$obj, $task['func']])) {
            throw new \Exception("[error] {$task['func']} is not exists");
        }

        if(empty($task['params'])) {
            $task['params'] = [];
        }

        array_unshift($task['params'], $this->worker);

        return call_user_func_array([$obj, $task['func']], $task['params']);
    }
}