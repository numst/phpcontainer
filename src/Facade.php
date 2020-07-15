<?php
namespace Numst;

class Facade {
    public static $app;
    public static function setFacadeApplication($app){
        self::$app = $app;
    }

    public static function getFacadeRoot(){
        $facadeAccessor = static::getFacadeAccessor();
        $instance = self::$app->make($facadeAccessor);
        return $instance;
    }


    public static function __callStatic($method,$params){
        $instance = self::getFacadeRoot();
        if (!$instance) {
            throw new RuntimeException("A facade root has not been set!");
        }
        return $instance->$method(...$params);
    }
}