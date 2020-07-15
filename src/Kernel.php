<?php
/**
 * 核心类，再次完成整个程序的生命周期
 */
namespace Numst;
use Numst\Kernel\Http\Kernel as httpKernel;
use Numst\Router\Router;

class Kernel implements httpKernel{
    
    protected $app;

    public function __construct(Container $app,Router $router){
        $this->app = $app;
        $this->router = $router;
    }

    public function bootstrap(){
        //TODO
    }

    public function handle($request){
        try{
            $response = $this->sendRequestThrouthRouter($request);
        } catch(\Exception $e) {
            throw new \Exception($e);
        } catch(\Throwable $e) {
            throw new \Throwable($e); 
        }
        //发布事件
        //$this->app['events']->dispatch();
        return $response;
    }

    public function terminate($request, $response){

    }

    public function getApplication(){
        return $this->app;
    }
}