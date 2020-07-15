<?php
namespace Numst\Router;


class Router{
    //路由事件
    protected $events;
    //容器对象
    protected $container;
    //路由集合
    protected $routes;
    //当前路由
    protected $current;
    //当前请求
    protected $currentRequest;
    //中间件
    protected $middleware;
    //中间件组
    protected $middlewareGroup;
    //请求方式
    public static $verbs = ['GET','HEAD','POST','OPTIONS','PUT','PATCH','DELETE'];

    public function __construct(){

    }
    //注册get请求
    public function get($uri,$action=null){
        $this->addRoute(['GET','HEAD'],$uri,$action);
    }
    //注册post请求
    public function post($uri,$action=null){
        $this->addRoute('POST',$uri,$action);
    }
    //注册PUT
    public function put($uri,$action=null){
        $this->addRoute('PUT',$uri,$action);
    }
    //注册PATCH
    public function patch($uri,$action=null){
        $this->addRoute('PATCH',$uri,$action);
    }
    //注册delete
    public function delete($uri,$action=null){
        $this->addRoute('DELETE',$uri,$action);
    }
    //注册options
    public function options($uri,$action=null){
        $this->addRoute('OPTIONS',$uri,$action);
    }
    //注册全部
    public function any($uri,$action=null){
        $this->addRoute(self::$verbs,$uri,$action);
    }

    //重定向
    public function redirect($uri,$destination,$status = 302){
        return $this->any($uri,'Numst\Router\RedirectController')->
                    defaults($destination)->
                    defaults($status);
    }

    public function addRoute($methods,$uri,$action){
        return $this->routes->add($this->createRoute($methods,$uri,$action));
    }

    public function createRoute($methods,$uri,$action){
        if (! $action instanceof Closure) {
            //正常路由
            if (is_string($action)) {
                $controller = $action;
                $action = [
                    'uses'          => $action,
                    'controller'    => $controller
                ];
            } else {
                throw new \Exception("路由错误！");
            }
        } 

        $route = $this->newRoute(
            $methods,$this->prefix($uri),$action
        );

        
    }

    protected function prefix($uri){
        return trim('/'.trim($uri, '/'), '/') ?: '/';
    }
}