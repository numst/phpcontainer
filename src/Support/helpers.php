<?php
use Numst\Support\Arr;


if (!function_exists('class_basename')) {
    /**
     * 获取类的名字
     */
    function class_basename($class){
        $class = is_object($class) ? get_class($class): $class;

        return basename(str_replace('\\','/',$class));
    }
}