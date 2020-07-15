<?php
namespace Numst\Traits;
use Numst\Support\Str;

trait InteractsContent {

    public function isJson(){
        return Str::contains($this->header('CONTENT_TYPE'),['/json','+json']);
    }

    public function header($key = null, $default = null){
        return $this->retrieveItem('headers',$key,$default);
    }

    public function retrieveItem($source, $key, $default){
        if (is_null($key)) {
            return $this->$source->all();
        }

        return $this->$source->get($key,$default);
    }
}