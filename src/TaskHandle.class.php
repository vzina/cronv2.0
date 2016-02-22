<?php
/**
 * TaskHandle.class.php.
 * Author: yeweijian
 * E-mail: yeweijian@hoolai.com
 * Date: 2016/2/19
 * Time: 16:02
 */

namespace EasyCron;


use EasyCron\Async\Coroutine\Scheduler;

class TaskHandle
{
    static public $async_mode = false;
    /**
     * @var Scheduler
     */
    static protected $_scheduler;

    protected function __construct()
    {

    }

    static public function parse($result)
    {
        if(is_object($result) && $result instanceof \Generator){
            static::$async_mode = true;
            static::getScheduler()->newTask($result)
                ->run();
        }else{
            return $result;
        }
    }

    static protected function getScheduler()
    {
        if(is_null(static::$_scheduler)){
            static::$_scheduler = new Scheduler();
        }
        return static::$_scheduler;
    }
}