<?php
/**
 * Task.php.
 * Author: yeweijian
 * E-mail: yeweijian@hoolai.com
 * Date: 2016/1/15
 * Time: 12:14
 */

namespace EasyCron\Async\Coroutine;


use EasyCron\EasyCronException;

class Task
{
    protected $callbackData;
    protected $taskId;
    protected $corStack;
    protected $coroutine;
    protected $exception = null;

    /**
     * [__construct 构造函数，生成器+taskId, taskId由 scheduler管理]
     * @param [type]    $task      [description]
     * @param \Generator $coroutine [description]
     */
    public function __construct($taskId, \Generator $coroutine)
    {
        $this->taskId = $taskId;
        $this->coroutine = $coroutine;
        $this->corStack = new \SplStack();
    }

    /**
     * [getTaskId 获取task id]
     * @return [type] [description]
     */
    public function getTaskId()
    {
        return $this->taskId;
    }

    /**
     * [setException  设置异常处理]
     * @param [type] $exception [description]
     */
    public function setException($exception)
    {
        $this->exception = $exception;
    }

    /**
     * [run 协程调度]
     * @param \Generator $gen
     * @throws \Exception
     */
    public function run(\Generator $gen)
    {
        while (true) {
            try {
                /* 异常处理 */
                if ($this->exception) {
                    $gen->throw($this->exception);
                    $this->exception = null;
                    continue;
                }
                $value = $gen->current();

//                Logger::write(__METHOD__ . " value === " . var_export($value, true), Logger::INFO);
                /* 中断内嵌 继续入栈 */
                if ($value instanceof \Generator) {
                    $this->corStack->push($gen);
//                    Logger::write(__METHOD__ . " corStack push ", Logger::INFO);
                    $gen = $value;
                    continue;
                }
                /* if value is null and stack is not empty pop and send continue */
                if (is_null($value) && !$this->corStack->isEmpty()) {
//                    Logger::write(__METHOD__ . " values is null stack pop and send ", Logger::INFO);
                    $gen = $this->corStack->pop();
                    $gen->send($this->callbackData);
                    continue;
                }

                if ($value instanceof EndTask) {
                    $res = $value->getValue();
                    if(is_object($res) && ($res instanceof \swoole_process)){
                        $res->exit(0);
                    }
                    // end yeild
//                    Logger::write(__METHOD__ . " yield end words == " . var_export($value, true), Logger::INFO);
                    return false;
                }

                /* 中断内容为异步IO 发包 返回 */
                if ($value instanceof \EasyCron\Async\ClientAdapter) {
                    //async send push gen to stack
                    $this->corStack->push($gen);
                    $value->send(array($this, 'callback'));
                    return;
                }
                /* 出栈，回射数据 */
                if ($this->corStack->isEmpty()) {
                    return;
                }
//                Logger::write(__METHOD__ . " corStack pop ", Logger::INFO);
                $gen = $this->corStack->pop();
                $gen->send($value);
            } catch (EasyCronException $e) {

                if ($this->corStack->isEmpty()) {
                    throw $e;
                }

                $gen = $this->corStack->pop();
                $this->exception = $e;
            }
        }
    }

    /**
     * [callback description]
     * @param  [type]   $r        [description]
     * @param  [type]   $key      [description]
     * @param  [type]   $calltime [description]
     * @param  [type]   $res      [description]
     */
    public function callback($r, $key, $calltime, $res)
    {
        /* 继续run的函数实现 ，栈结构得到保存 */
        $gen = $this->corStack->pop();
        $this->callbackData = ['r' => $r, 'calltime' => $calltime, 'data' => $res];
        $gen->send($this->callbackData);
        $this->run($gen);
    }

    /**
     * [isFinished 判断该task是否完成]
     * @return boolean [description]
     */
    public function isFinished()
    {
        return !$this->coroutine->valid();
    }

    public function getCoroutine()
    {
        return $this->coroutine;
    }
}
