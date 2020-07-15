<?php
namespace Numst\Kernel\Http;


class HeaderBag implements \IteratorAggregate, \Countable{
    protected const UPPER = '_ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    protected const LOWER = '-abcdefghijklmnopqrstuvwxyz';

    protected $headers = [];
    protected $cacheControl = [];

    public function __construct(array $headers = []){
        foreach($headers as $key => $val) {
            $this->set($key, $val);
        }
    }

    public function __toString(){
        if (!$headers = $this->all() ) {
            return '';
        }
        ksort($headers);
        $max = max(array_map('strlen',array_keys($headers))) + 1;
        $content = '';
        foreach($headers as $name => $values){
            $name = ucwords($name,'-');
            foreach($values as $value){
                $content .= sprintf("%-{$max}s %s\r\n",$name.':',$value);
            }
        }

        return $content;
    }
    
    /**
     * 获取全部元素
     */
    public function all(){
        if (1 <= \func_num_args() && null !== $key = func_get_arg(0)) {
            return $this->headers[strtr($key, self::UPPER, self::LOWER)] ?? [];
        }
        return $this->headers;
    }

    /**
     * 获取全部的索引
     */
    public function keys(){
        return array_keys($this->all());
    }

    public function get($key, $default = null){
        $headers = $this->all((string) $key);
        if (2 < \func_num_args()) {
            @trigger_error(sprintf('Passing a third argument to "%s()" is deprecated, use method "all()" instead', __METHOD__), E_USER_DEPRECATED);

            if (!func_get_arg(2)) {
                return $headers;
            }
        }

        if (!$headers) {
            return $default;
        }

        if (null === $headers[0]) {
            return null;
        }

        return (string) $headers[0];
    }

    /**
     * 添加元素
     */
    public function add(array $headers){
        foreach($headers as $key => $values){
            $this->set($key, $values);
        }
    }

    /**
     * 替换变量
     */
    public function replace(array $headers = []){
        $this->headers = [];
        $this->add($headers);
    }

    public function set($key, $values, $replace = true){
        $key = strtr($key, self::UPPER, self::LOWER);
        if (\is_array($values)) {
            $values = array_values($values);

            if (true === $replace || !isset($this->headers[$key]) ) {
                $this->headers[$key] = $values;
            } else {
                $this->headers[$key] = array_merge($this->headers[$key],$values);
            }
        } else {
            if (true === $replace || !isset($this->headers[$key]) ) {
                $this->headers[$key] = [$values];
            } else {
                $this->headers[$key][] = $values;
            }
        }

        if ('cache-control' === $key) {
            $this->cacheControl = implode(',',$this->headers[$key]);
        }
    }

    /**
     * 是否存在
     */
    public function has($key){
        return \array_key_exists(strtr($key,self::UPPER,self::LOWER),$this->all());
    }

    /**
     * 是否存在
     */
    public function contains($key, $value){
        return \in_array($value,$this->all((string)$key));
    }

    /**
     * 移除
     */
    public function remove($key){
        $key = strtr($key, self::UPPER, self::LOWER);

        unset($this->headers[$key]);

        if ( 'cache-control' === $key) {
            $this->cacheControl = [];
        }
    }

    /**
     * 来自接口 IteratorAggregate
     */
    public function getIterator(){
        return new \ArrayIterator($this->headers);
    }

    /**
     * 来自接口 Countable
     */
    public function count(){
        return \count($this->headers);
    }
}