<?php
/**
 * TestTask.class.php.
 * Author: yeweijian
 * E-mail: yeweijian@hoolai.com
 * Date: 2016/2/19
 * Time: 15:33
 */

namespace App\Tasks;


use EasyCron\Async\Coroutine\EndTask;
use EasyCron\DB;
use EasyCron\Redis;

class TestTask
{
    /**
     * 异步任务处理
     * @param $worker
     * @param $a
     * @return \Generator
     */
    public function runAsync($worker, $a)
    {
        echo $a, time(), PHP_EOL;
        $db = DB::instance();
        $data = (yield $db->queryOne('select * from documents where id=11'));
        /** @var \Redis $redis */
        $redis = Redis::instance();
        yield $redis->lPush('abc', json_encode($data),'exit');
        echo 'runAsync send data!', PHP_EOL;
    }

    /**
     * 同步任务处理
     * @param $worker
     * @param $a
     */
    public function runSync($worker, $a)
    {
        echo $a, time(), PHP_EOL;
        $db = DB::instance('_db', false);
        $data = $db->queryOne('select * from documents');
        $redis = Redis::instance('_redis', false);
        $redis->lPush('abc', json_encode($data),'exit');
        echo 'runSync send data!', PHP_EOL;
    }
}