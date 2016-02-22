<?php
namespace EasyCron;
/**
 * Created by PhpStorm.
 * User:  ClownFish 187231450@qq.com
 * Date: 15-11-4
 * Time: 下午10:16
 */
class Worker
{
    public $workers;

    public function loadWorker()
    {
        foreach ($this->getWorkers() as $classname => $task) {
            for ($i = 1; $i <= $task["processNum"]; $i++) {
                $this->create_process($classname, $i, $task["redis"]);
            }
        }
    }

    protected function getWorkers()
    {
        $path = ROOT_PATH . "config/worker.php";
        $config = include $path;
        if (empty($config)) {
            return array();
        }
        return $config;
    }

    /**
     * 创建一个子进程
     * @param $classname
     * @param $number
     * @param $redis
     */
    public function create_process($classname, $number, $redis)
    {
        $this->workers["classname"] = $classname;
        $this->workers["number"] = $number;
        $this->workers["redis"] = $redis;
        $process = new \swoole_process(array($this, "run"));
        if (!($pid = $process->start())) {

        }
        //记录当前任务
        Crontab::$task_list[$pid] = array(
            "start" => microtime(true),
            "classname" => $classname,
            "number" => $number,
            "redis" => $redis,
            "type" => "worker",
            "process" => $process
        );
    }

    /**
     * 子进程执行的入口
     * @param $worker
     */
    public function run($worker)
    {
        $class = $this->workers["classname"];
        $number = $this->workers["number"];
        $worker->name("lzm_worker_" . $class . "_" . $number);
        $class = '\App\Workers\\' . $class . "Worker";
        $w = new $class;
        $w->content($this->workers["redis"]);
        $w->tick($worker);
    }
}