<?php
/**
 * ReturnValue.php.
 * Author: yeweijian
 * E-mail: yeweijian@hoolai.com
 * Date: 2016/1/15
 * Time: 12:18
 */

namespace EasyCron\Async\Coroutine;


class EndTask
{
    protected $value;

    public function __construct($value) {
        $this->value = $value;
    }

    public function getValue() {
        return $this->value;
    }
}