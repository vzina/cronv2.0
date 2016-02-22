<?php
/**
 * Created by PhpStorm.
 * User: ClownFish 187231450@qq.com
 * Date: 15-11-2
 * Time: 下午9:36
 */

return [
    'crontabdb' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'username' => 'root',
        'password' => 'root',
        'dbname' => 'crontab',
        'charset' => 'utf8'
    ],
    '_db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'username' => 'root',
        'password' => 'root',
        'dbname' => 'test',
        'charset' => 'utf8'
    ],
    "log" => [
        "level" => "debug",
        "type" => "file",
        "log_path" => ROOT_PATH . "logs/",
    ]
];

