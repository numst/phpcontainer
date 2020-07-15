<?php
namespace Numst;

class Context{
    protected $when;

    protected $needs;

    protected $container;

    public function __construct($when, Container $container){
        $this->when = $when;
        $this->container = $container;
    }

    public function needs($needs){
        $this->needs = $needs;

        return $this;
    }

    public function give($give){
        // 调用容器绑定依赖上下文
        $this->container->addContextualBinding($this->when, $this->needs, $give);
    }
}