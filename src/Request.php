<?php
namespace Numst;
use Numst\Kernel\Request as HttpRequest;
use Numst\Traits\InteractsContent;
use Numst\Support\Arr;


class Request extends HttpRequest{
    use InteractsContent;

    protected $json;
    /**
     * The user resolver callback.
     *
     * @var \Closure
     */
    protected $userResolver;

    /**
     * The route resolver callback.
     *
     * @var \Closure
     */
    protected $routeResolver;

    public function __construct(){
        
    }

    public static function capture(){
        return HttpRequest::createFromGlobals();
    }

    public function instance(){
        return $this;
    }
    /**
     * 检测是否是AJAX请求
     */
    public function ajax(){
        return $this->isXmlHttpRequest();
    }

    public function method(){
        return $this->getMethod();
    }

    public function root(){
        return rtrim($this->getSchemeAndHttpHost().$this->getBaseUrl(),'/');
    }
    /**
     * 获取url
     */
    public function url(){
        return rtrim(preg_replace('/\?.*/','',$this->getUri()));
    }
    /**
     * 获取path
     */
    public function path(){
        $pattern = trim($this->getPathInfo(),'/');

        return $pattern == '' ?'/':$pattern;
    }

    public function pjax(){
        return $this->headers->get('X-PJAX') == true;
    }

    public function secure(){
        return $this->isSecure();
    }

    public function ip(){
        return $this->getClientIp();
    }

    public function ips(){
        return $this->getClientIps();
    }

    public function userAgent(){
        return $this->headers->get('User-Agent');
    }

    /**
     * 合并请求参数
     */
    public function merge(array $input){
        $this->getInputSource()->add($input);

        return $this;
    }

    /**
     * 替换参数
     */
    public function replace(array $input) {
        $this->getInputSource()->replace($input);

        return $this;
    }

    public function get($key, $default = null){
        return parent::get($key,$default);
    }

    /**
     * 获取输入参数
     */
    public function getInputSource(){
        if ($this->isJson()) {
            return $this->json();
        }

        return in_array($this->getRealMethod(),['GET','HEAD']) ? $this->query : $this->request;
    }
    /**
     * 验证session并获取
     */
    public function session(){
        if (!$this->hasSession()){
            throw new \RuntimeException('Session store not set on request');
        }
        return $this->session;
    }
    /**
     * 获取session
     */
    public function getSession(){
        return $this->session;
    }

    public function route($param = null, $default = null) {
        $route = call_user_func($this->getRouteResolver());

        if (is_null($route) || is_null($param)) {
            return $route;
        }

        return $route->parameter($param, $default);
    }

    public function getRouteResolver(){
        return $this->routeResolver ?: function(){
            //
        };
    }

    public function toArray(){
        return $this->all();
    }
    /**
     * 从请求中获取json数据
     */
    public function json($key = null, $default = null){
        if (!isset($this->json)) {
            $this->json = new ParameterBag((array) json_decode($this->getContent(),true));
        }

        if (is_null($key)) {
            return $this->json;
        }

        return $this->data_get($this->json->all(),$key,$default);
    }

    public function data_get($target,$key,$default = null){
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.',$key);

        while(!is_null($segment = array_shift($key))) {
            if (Arr::accessible($value) && Arr::exists($target,$segment)) {
                $target = $target[$segment];
            } else if (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return $default instanceof \Closure ? $default() : $default;
            }
        }

        return $target;
    }
}