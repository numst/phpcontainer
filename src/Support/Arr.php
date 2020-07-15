<?php
namespace Numst\Support;

class Arr {

    /**
     * 判断是否为数组或SPL数组对象
     */
    public static function accessible($value){
        return is_array($value) || $value instanceof \ArrayAccess;
    }

    /**
     * 检查是否在数组或对象中
     */
    public static function exists($array,$key){
        if ($array instanceof \ArrayAccess) {
            return $array->offsetExists($key);
        }

        return array_key_exists($key,$array);
    }
}