<?php
namespace Numst\Kernel\Http;

interface Kernel {
    //初始化
    public function bootstrap();

    /**
     *   处理请求 request
     *   返回response 
     */
    public function handle($request);
    /**
     * 执行请求生命周期内的最后操作
     */
    public function terminate($request, $response);
    //获取应用对象
    public function getApplication();
}