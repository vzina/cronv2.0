<?php
/**
 * ClientAdapter.php.
 * Author: yeweijian
 * E-mail: yeweijian@hoolai.com
 * Date: 2016/1/15
 * Time: 15:30
 */

namespace EasyCron\Async;


interface ClientAdapter
{
    public function send(callable $callback);
}