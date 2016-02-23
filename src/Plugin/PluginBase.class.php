<?php
namespace EasyCron\Plugin;
/**
 * Created by PhpStorm.
 * User: ClownFish 187231450@qq.com
 * Date: 14-12-27
 * Time: 下午3:13
 */
abstract class PluginBase
{
    public $worker;

    abstract public function run($task);
}