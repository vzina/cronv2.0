<?php
/**
 * Redis.class.php.
 * Author: yeweijian
 * E-mail: yeweijian@hoolai.com
 * Date: 2016/2/22
 * Time: 14:44
 */

namespace EasyCron;


class Redis
{
    static protected $_instance = [];

    public static function instance($name = '_redis', $async_mode = true)
    {
        $key = (int)$async_mode > 0 ? 1 : 0;

        if (isset(static::$_instance[$key])) {
            return static::$_instance[$key];
        }

        if(is_array($name)){
            $config = $name;
        }else{
            $config = (array)\Main::config($name);
        }

        if ($async_mode) {
            $redis = new \EasyCron\Async\Adapter\Redis($config);
        } else {
            $redis = new \EasyCron\Sync\Redis($config);
        }

        return static::$_instance[$key] = $redis;
    }
}