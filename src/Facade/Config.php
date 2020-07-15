<?php
namespace Numst\Facade;
use Numst\Facade;

class Config extends Facade{

    public static function getFacadeAccessor():string{
        return 'Config';
    }
}