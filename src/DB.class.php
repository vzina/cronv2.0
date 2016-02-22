<?php
/**
 * DB.class.php .
 * Author: yexiaojian
 * E-mail: yeweijian188@163.com
 * Date: 16/2/19
 * Time: 23:03
 */

namespace EasyCron;


use EasyCron\Async\DBAsync;
use EasyCron\Sync\EasyDB;

class DB
{
    static protected $_instance = [];

    public static function instance($name = '_db', $async_mode = true)
    {
        $key = (int)$async_mode > 0 ? 1 : 0;

        if (isset(static::$_instance[$key])) {
            return static::$_instance[$key];
        }

        if(is_array($name)){
            $config = $name;
        }else{
            $config = \Main::config($name);
        }

        if ($async_mode) {
            $db = new DBAsync($config);
        } else {
            $db = new EasyDB($config);
        }

        return static::$_instance[$key] = $db;
    }
}