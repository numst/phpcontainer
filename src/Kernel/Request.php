<?php
/**
 * 请求处理核心类
 */
namespace Numst\Kernel;
use Numst\Kernel\Http\FileBag;
use Numst\Kernel\Http\HeaderBag;
use Numst\Kernel\Http\ServerBag;
use Numst\Kernel\Http\ParameterBag;
use Numst\Kernel\Session\SessionInterface;

class Request {
    /**
     * 允许的方法
     */
    public $allowMethods = ['GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'CONNECT', 'OPTIONS', 'PATCH', 'PURGE', 'TRACE'];
    
    //请求属性
    public $attributes;
    /**
     * $_POST
     */
    public $request;
    /**
     * $_GET
     */
    public $query;
    /**
     * $_SERVER
     */
    public $server;
    /**
     * $_FILES
     */
    public $files;
    /**
     * $_COOKIE
     */
    public $cookies;
    /**
     * $_SERVER;
     */
    public $headers;
    /**
     * @var string|resource|false|null
     */
    protected $content;
    /**
     * sessionInterface
     */
    protected $session;

    protected $pathInfo;

    protected $requestUri;

    protected $baseUrl;

    protected $basePath;

    protected $method;
    /**
     * 请求重写，默认开启，可以使用_method 生成PUT ,DELETE 等请求
     */
    protected static $httpMethodParameterOverride = true;

    public function __construct(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null){
        $this->initialize($query, $request, $attributes, $cookies, $files, $server, $content);
    }

    public function initialize(array $query = [],array $request = [],array $attributes = [],array $cookies = [],array $files = [],array $server = [], $content = null){
        $this->query        = new ParameterBag($query);
        $this->request      = new ParameterBag($request);
        $this->attributes   = new ParameterBag($attributes);
        $this->cookies      = new ParameterBag($cookies); 
        $this->file         = new FileBag($files);
        $this->server       = new ServerBag($server);
        $this->headers      = new HeaderBag($this->server->getHeaders());

        $this->content = $content;
        $this->pathInfo = null;
        $this->requestUri = null;
        $this->baseUrl = null;
        $this->basePath = null;
        $this->method = null;
    }


    public static function createFromGlobals(){
        $request = self::createRequestFromFactory($_GET, $_POST, [], $_COOKIE, $_FILES, $_SERVER);
        if (0 === strpos($request->headers->get('CONTENT_TYPE'),'application/x-www-form-urlencoded') 
        && \in_array(strtoupper($request->server->get('REQUEST_METHOD','GET')),['PUT','DELETE','PATCH'])){
            parse_str($request->getContent(), $data);
            $request->request = new ParameterBag($data);
        }

        return $request;
    }

    public static function createRequestFromFactory(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null):self{
        
        return new static($query, $request, $attributes, $cookies, $files, $server, $content);
    }

    public function __clone(){
        $this->query = clone $this->query;
        $this->request = clone $this->request;
        $this->attributes = clone $this->attributes;
        $this->cookies = clone $this->cookies;
        $this->files = clone $this->files;
        $this->server = clone $this->server;
        $this->headers = clone $this->headers;
    }

    public function __toString(){
        try{
            $content = $this->getContent();
        } catch(\LogicException $e) {
            if (\PHP_VERSION_ID >= 10400) {
                throw $e;
            }
            return trigger_error($e,E_USER_ERROR);
        }

        $cookieHeader = '';
        $cookies = [];
        foreach ($this->cookies as $k => $v) {
            $cookies[] = $k.'='.$v;
        }

        if (!empty($cookies)) {
            $cookieHeader = 'Cookie: '.implode('; ',$cookie)."\r\n";
        }

        return sprintf('%s %s %s',$this->getMethod(),$this->getRequestUri(),$this->server->get('SERVER_PROTOCOL'))."\r\n". 
        $this->headers.$cookieHeader."\r\n".$content;
    }

    public function get($key,$detault = null){
        if ($this !== $result = $this->attributes->get($key,$this)) {
            return $result;
        }
        if ($this !== $result = $this->query->get($key,$this) ){
            return $result;
        }
        if ( $this !== $result = $this->request->get($key,$this) ){
            return $result;
        }

        return $default;
    }
    /**
     * 设置请求重写开关，默认为true
     */
    public static function setHttpMethodParameterOverride(bool $bool = true){
        self::$httpMethodParameterOverride = $bool;
    }

    /**
     * 获取session
     */
    public function getSession(){
        $session = $this->session;
        if (!$session instanceof SessionInterface && null !== $session) {
            $this->setSession($session = $session());
        }

        if (null === $session) {
            @trigger_error(sprintf('Calling "%s()" when no session has been set is deprecated since Symfony 4.1 and will throw an exception in 5.0. Use "hasSession()" instead.', __METHOD__), E_USER_DEPRECATED);
            // throw new \BadMethodCallException('Session has not been set.');
        }

        return $session;
    }

    public function hasSession(){
        return null !== $this->session;
    }

    public function setSession(SessionInterface $session){
        $this->session = $session;
    }

    /**
     * 获取客户端ip
     */
    public function getClientIps(){
        $ip = false;
        if (!empty($this->server->get('CLIENT_IP'))) {
            $ip = $this->server->get('CLIENT_IP');
        }

        if (!$ip) {
            $ip = $this->server->get('REMOTE_ADDR');
        }
        $ips = null;
        if (!empty($this->server->get('X_FORWARDED_FOR'))) {
            $ips = explode(',',$this->server->get('X_FORWARDED_FOR'));
            if ($ip) { array_unshift($ips,$ip);$ip = false; }
        }

        return null !== $ips ? $ips : [$ip];
    }

    /**
     * 获取客户端ip
     */
    public function getClientIp(){
        $ipAddress = $this->getClientIps();

        return $ipAddress[0];
    }

    /**
     * 获取运行文件的名称
     */
    public function getScriptName(){
        return $this->server->get('SCRIPT_NAME',$this->server->get('ORIG_SCRIPT_NAME',''));
    }

    /**
     * 获取请求协议
     */
    public function getScheme(){
        $https = $this->server->get('HTTPS');
        $fhttps = $this->server->get('HTTP_FRONT_END_HTTPS');
        if (!empty($https) && 'off' !== strtolower($https)){
            return 'https';
        } else if ( null !== $this->server->get('HTTP_X_FORWARDED_PROTO')  && 'https' === $this->server->get('HTTP_X_FORWARDED_PROTO') ){
            return 'https';
        } else if (!empty($fhttps) && 'off' !== strtolower($fhttps)) {
            return 'https';
        }

        return 'http';
    }

    /**
     * returns the http host being requested
     */
    public function getHttpHost(){
        $scheme = $this->getScheme();
        $port   = $this->getPort();
        if ( ('http' == $scheme && 80 == $port) || ('https' == $scheme && 443 == $port) ) {
            return $this->getHost();
        }

        return $this->getHost().':'.$port;
    }

    public function getHost(){
        $host = $this->headers->get('HOST');

        if ($host) {
            $host = strval($this->server->get('HTTP_X_FORWARDED_HOST') ?: $this->server->get('HTTP_HOST'));
        }
        $host = strtolower(preg_replace('/:\d+$/', '', trim($host)));
        return $host;
    }

    public function getPort(){
        $host = $this->headers->get('HOST');
        if ($host) {
            $port = $this->server->get('SERVER_PORT');
        } 
        return $port;
    }
   
    /**
     * 获取请求实体内容，处理php://input
     */
    public function getContent($asResource = false){
        $currentContentIsResource = \is_resource($this->content);
        if (true === $asResource) {
            if ($currentContentIsResource) {
                rewind($this->content);

                return $this->content;
            }
            
            if (\is_string($this->content)) {
                $resource = fopen("php://temp".'r+');
                fwrite($resource,$this->content);
                rewind($resource);

                return $resource;
            }

            $this->content = false;
            return fopen("php://input",'rb');
        }

        if ($currentContentIsResource) {
            rewind($this->content);

            return stream_get_contents($this->content);
        }

        if (null === $this->content || false === $this->content) {
            $this->content = file_get_contents('php://input');
        }

        return $this->content;
    }

    /**
     * 检测请求头的X-Requested-With
     */
    public function isXmlHttpRequest(){
        return 'XMLHttpRequest' == $this->headers->get('X-Requested-With');
    }

    /**\
     * 获取请求方式
     */
    public function getMethod(){
        if (null !== $this->method) {
            return $this->method;
        }

        $this->method = strtoupper($this->server->get('REQUEST_METHOD'),'GET');

        if ('POST' !== $this->method) {
            return $this->method;
        }
        //X-HTTP-METHOD-OVERRIDE可以重写请求类型
        $method = $this->headers->get('X-HTTP-METHOD-OVERRIDE');

        if (!$method && self::$httpMethodParameterOverride) {
            $method = $this->request->get('_method',$this->query->get('_method','POST'));
        }

        if (!\is_string($method)) {
            return $this->method;
        }

        if (\in_array($method,$this->allowMethods,true)) {
            return $this->method = $method;
        }

        if (!preg_match('/^[A-Z]++$/D',$method)) {
            throw new \UnexpectedValueException(spritf("Invalid method override '%s' "),$method);
        }

        return $this->method = $method;
    }
    
    /**
     * 获取原始的请求方法
     */
    public function getRealMethod(){
        return strtoupper($this->server->get('REQUEST_METHOD','GET'));
    }

    /**
     * 判断方法
     */
    public function isMethod($method){
        return $this->getMethod() === strtoupper($method); 
    }

    /**
     * 获取请求协议半吧呢
     */
    public function getProtocolVersion(){
        return $this->server->get('SERVER_PROTOCOL');
    }
     /**
     * 获取pathInfo
     */
    public function getPathInfo(){
        if ( null === $this->pathInfo) {
            $this->pathInfo = $this->preparePathInfo();
        }

        return $this->pathInfo;
    }
    /**
     * 获取uri
     */
    public function getUri(){
        if (null !== $qs = $this->getQueryString()){
            $qs = '?'.$qs;
        }

        return $this->getScheme().'://'.$this->getHttpHost().$this->getBaseUrl().$this->getPathInfo().$qs;
    }

    public function getQueryString(){
        $queryString = $this->server->get('QUERY_STRING');
        if ('' === ($queryString ?? '') ){
            $queryString = '';
        } else {
            parse_str($queryString);
            ksort($queryString);
            $queryString = http_build_query($queryString,'','&',PHP_QUERY_RFC3986);
        }
       
        return '' === $queryString ? null : $queryString;
    }

    public function getSchemeAndHttpHost(){
        return $this->getScheme().'://'.$this->getHttpHost();
    }

    
    /**
     * 获取请求uri
     */
    public function getRequestUri(){
        if ( null == $this->requestUri ) {
            $this->requestUri = $this->prepareRequestUri();
        }

        return $this->requestUri;
    }

    /**
     * 获取基本路径
     */
    public function getBasePath(){
        if (null === $this->basePath) {
            $this->basePath = $this->prepareBasePath();
        }
        return $this->basePath;
    }

    /**
     * 获取基本url
     */
    public function getBaseUrl(){
        if (null === $this->baseUrl) {
            $this->baseUrl = $this->prepareBaseUrl();
        }
        return $this->baseUrl;
    }

    protected function preparePathInfo(){
        if ( null === ($requestUri = $this->getRequestUri())) {
            return '/';
        }

        if ( false !== $pos = strpos($requestUri,'?')) {
            $requestUri = substr($requestUri,0,$pos);
        }
        if ('' !== $requestUri && '/' !== $requestUri[0]) {
            $requestUri = '/'.$requestUri;
        }

        if ( null === ($baseUrl = $this->getBaseUrl()) ){
            return $requestUri;
        }

        $baseInfo = substr($requestUri,\strlen($baseUrl));
        if ( false === $baseInfo || '' === $baseInfo) {
            return '/';
        }

        return (string) $baseInfo;
    }

    protected function prepareRequestUri(){
        $requestUri = '';
        if ('1' == $this->server->get('IIS_WasUrlRewritten') && '' != $this->server->get('UNENCODE_URL')){
            $requestUri = $this->server->get('UNENCODED_URL');
            $this->server->remove('UNENCODED_URL');
            $this->server->remove('IIS_WasUrlRewritten');
        } else if ($this->server->has('REQUEST_URI')) {
            $requestUri = $this->server->get('REQUEST_URI');

            if ('' !== $requestUri && '/' === $requestUri[0]) {
                //过滤掉类似vue的 history 路由 #
                if ( false !== $pos = strpos($requestUri,'#')) {
                    $requestUri = substr($requestUri,0,$pos);
                }
            } else {
                $uriComponents = parse_url($requestUri);

                if (isset($uriComponents['path'])) {
                    $requestUri = $uriComponents['path'];
                }

                if (isset($uriComponents['query'])) {
                    $requestUri .= '?'.$uriComponents['query'];
                }
            }
        } else if ($this->server->has('ORIG_PATH_INFO')) {
            $requestUri = $this->server->get('ORIG_PATH_INFO');
            if ('' != $this->server->get('QUERY_STRING')) {
                $requestUri .= '?'.$this->server->get('QUERY_STRING');
            }
            $this->server->remove('ORIG_PATH_INFO');
        }

        $this->server->set('REQUEST_URI', $requestUri);

        return $requestUri;
    }

    protected function prepareBasePath(){
        $baseUrl = $this->getBaseUrl();
        if (empty($baseUrl)) {
            return '';
        }

        $filename = basename($this->server->get('SCRIPT_FILENAME'));
        if (basename($baseUrl) == $filename) {
            $basePath = \dirname($baseUrl);
        } else {
            $basePath = $baseUrl;
        }

        if ('\\' === \DIRECTORY_SEPARATOR) {
            $basePath = str_replace('\\','/',$basePath);
        }
        return rtrim($basePath,'/');
    }

    protected function prepareBaseUrl(){
        $filename = basename($this->server->get('SCRIPT_FILENAME'));

        if (basename($this->server->get('SCRIPT_NAME')) === $filename) {
            $baseUrl = $this->server->get('SCRIPT_NAME');
        } elseif (basename($this->server->get('PHP_SELF')) === $filename) {
            $baseUrl = $this->server->get('PHP_SELF');
        } elseif (basename($this->server->get('ORIG_SCRIPT_NAME')) === $filename) {
            $baseUrl = $this->server->get('ORIG_SCRIPT_NAME'); // 1and1 shared hosting compatibility
        } else {
            // Backtrack up the script_filename to find the portion matching
            // php_self
            $path = $this->server->get('PHP_SELF', '');
            $file = $this->server->get('SCRIPT_FILENAME', '');
            $segs = explode('/', trim($file, '/'));
            $segs = array_reverse($segs);
            $index = 0;
            $last = \count($segs);
            $baseUrl = '';
            do {
                $seg = $segs[$index];
                $baseUrl = '/'.$seg.$baseUrl;
                ++$index;
            } while ($last > $index && (false !== $pos = strpos($path, $baseUrl)) && 0 != $pos);
        }
        // Does the baseUrl have anything in common with the request_uri?
        $requestUri = $this->getRequestUri();
       
        if ('' !== $requestUri && '/' !== $requestUri[0]) {
            $requestUri = '/'.$requestUri;
        }
        if ($baseUrl && null !== $prefix = $this->getUrlencodedPrefix($requestUri, $baseUrl)) {
            // full $baseUrl matches
            return $prefix;
        }
        if ($baseUrl && null !== $prefix = $this->getUrlencodedPrefix($requestUri, rtrim(\dirname($baseUrl), '/'.\DIRECTORY_SEPARATOR).'/')) {
            // directory portion of $baseUrl matches
            return rtrim($prefix, '/'.\DIRECTORY_SEPARATOR);
        }

        $truncatedRequestUri = $requestUri;
        if (false !== $pos = strpos($requestUri, '?')) {
            $truncatedRequestUri = substr($requestUri, 0, $pos);
        }

        $basename = basename($baseUrl);
        if (empty($basename) || !strpos(rawurldecode($truncatedRequestUri), $basename)) {
            // no match whatsoever; set it blank
            return '';
        }

        // If using mod_rewrite or ISAPI_Rewrite strip the script filename
        // out of baseUrl. $pos !== 0 makes sure it is not matching a value
        // from PATH_INFO or QUERY_STRING
        if (\strlen($requestUri) >= \strlen($baseUrl) && (false !== $pos = strpos($requestUri, $baseUrl)) && 0 !== $pos) {
            $baseUrl = substr($requestUri, 0, $pos + \strlen($baseUrl));
        }

        return rtrim($baseUrl, '/'.\DIRECTORY_SEPARATOR);
    }

    private function getUrlencodedPrefix(string $string,string $prefix):?string{
        if (0 !== strpos(rawurldecode($string), $prefix)) {
            return null;
        }

        $len = \strlen($prefix);
        
        if (preg_match(sprintf('#^(%%[[:xdigit:]]{2}|.){%d}#', $len), $string, $match)) {
            return $match[0];
        }

        return null;
    }

}