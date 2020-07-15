<?php
namespace Numst\Facade;

class Container extends Facade{
    public static function getFacadeAccessor():string{
        return 'App';
    }
}