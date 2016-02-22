<?php
namespace EasyCron;
/**
 * Created by PhpStorm.
 * User: ClownFish 187231450@qq.com
 * Date: 14-12-27
 * Time: 下午10:39
 */
class Process
{
    public $task;

    /**
     * 创建一个子进程
     * @param $task
     */
    public function create_process($id, $task)
    {
        $this->task = $task;
        $process = new \swoole_process(array($this, "run"));
        if (!($pid = $process->start())) {

        }
        //记录当前任务
        Crontab::$task_list[$pid] = array(
            "start" => microtime(true),
            "id" => $id,
            "task" => $task,
            "type" => "crontab",
            "process" => $process,
        );

        swoole_event_add($process->pipe, function ($pipe) use ($process) {
            $task = $process->read();
            if (!$task) return;
            list($pid, $sec) = explode(",", $task);
            if (isset(Crontab::$task_list[$pid])) {
                $tasklist = Crontab::$task_list[$pid];
                Crontab::$delay[$pid] = array("start" => time() + $sec, "task" => $tasklist["task"]);
                $process->write($task);
            }
        });
    }

    /**
     * 子进程执行的入口
     * @param $worker
     */
    public function run($worker)
    {
        $class = $this->task["execute"];
        $worker->name("lzm_crontab_" . $class . "_" . $this->task["id"]);
        $class = __NAMESPACE__ . '\Plugin\\' . $class;
        $c = new $class;
        $c->worker = $worker;
        $c->run($this->task["args"]);
    }


}

