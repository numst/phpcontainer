<?php
/**
 * 
 * DI IOC 
 * php version 7
 *
 * @category Container
 * @package  numst
 * @author 	 luanxd <894797541@qq.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://www.luanxiaodong.com
 */

namespace Numst;
class Container
{
    // 已绑定的服务
    protected $instances = [];
    // 已绑定的回调函数
    protected $bindings = [];
    // 服务别名
    protected $aliases = [];
    // 存放扩展器的数组
    protected $extenders = [];
    // 依赖上下文
    protected $context = [];

    // 绑定服务实例
    public function instance($name, $instance){
        $this->instances[$name] = $instance;
    }

    // 绑定服务
    public function bind($name, $instance, $shared = false){
        
        if ($instance instanceof \Closure) {
            // 如果$instance是一个回调函数，就绑定到bindings。
            $this->bindings[$name] = [
                'callback' => $instance,
                // 标记是否单例
                'shared' => $shared
            ];
        } else {
            // 调用make方法，创建实例
            $this->instances[$name] = $this->make($instance);
        }
    }

    // 绑定一个单例
    public function singleton($name, $instance){
        $this->bind($name, $instance, true);
    }

    // 给服务绑定一个别名
    public function alias($alias, $name){
        $this->aliases[$alias] = $name;
    }

    // 给服务绑定扩展器
    public function extend($name, $extender){
        if (isset($this->instances[$name])) {
            // 已经实例化的服务，直接调用扩展器
            $this->instances[$name] = $extender($this->instances[$name]);
        } else {
            $this->extenders[$name][] = $extender;
        }
    }


    // 获取服务
    public function make($name, array $parameters = []){
        // 先用别名查找真实服务名
        $name = isset($this->aliases[$name]) ? $this->aliases[$name] : $name;

        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        if (isset($this->bindings[$name])) {
            // 执行回调函数并返回
            $instance = call_user_func($this->bindings[$name]['callback']);

            if ($this->bindings[$name]['shared']) {
                // 标记为单例时，存储到服务中
                $this->instances[$name] = $instance;
            }
        } else {
            // 使用build方法构建此类
            $instance = $this->build($name, $parameters);
        }

        if (isset($this->extenders[$name])) {
            // 调用扩展器
            foreach ($this->extenders[$name] as $extender) {
                $instance = $extender($instance);
            }
        }

        return $instance;
    }

    // 构建一个类，并自动注入服务
    public function build($class, array $parameters = []){
        $reflector = new \ReflectionClass($class);

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            // 没有构造函数，直接new
            return new $class();
        }

        $dependencies = [];

        // 获取构造函数所需的参数
        foreach ($constructor->getParameters() as $dependency) {
            if (isset($this->context[$class]) && isset($this->context[$class][$dependency->getName()])) {
                // 先从上下文中查找
                $dependencies[] = $this->context[$class][$dependency->getName()];
                continue;
            }

            if (isset($parameters[$dependency->getName()])) {
                // 从自定义参数中查找
                $dependencies[] = $parameters[$dependency->getName()];
                continue;
            }

            if (is_null($dependency->getClass())) {
                // 参数类型不是类或接口时，无法从容器中获取依赖
                if ($dependency->isDefaultValueAvailable()) {
                    // 查找默认值，如果有就使用默认值
                    $dependencies[] = $dependency->getDefaultValue();
                } else {
                    // 无法提供类所依赖的参数
                    throw new \Exception('找不到依赖参数：' . $dependency->getName());
                }
            } else {
                // 参数类型是一个类时，就用make方法构建该类
                $dependencies[] = $this->make($dependency->getClass()->name);
            }
        }

        return $reflector->newInstanceArgs($dependencies);
    }

    // 绑定上下文
    public function addContextualBinding($when, $needs, $give){
        $this->context[$when][$needs] = $give;
    }

    // 支持链式方式绑定上下文
    public function when($when){
        return new Context($when, $this);
    }

    /**
     * 调用其他方法
     */
    public static function __callStatic($method,$arguments){
        return $this->$method($arguments);
    }
}

//   Test

// class Redis {
// 	public $name;

//     public function __construct($name = 'default')
//     {
//         $this->name = $name;
//     }

//     public function setName($name)
//     {
//         $this->name = $name;
//     }
// }
// $container = new Container();
// $container->singleton(Redis::class, function () {
//     return new Redis();
// });

// $redis = $container->make(Redis::class);
// var_dump($redis->name);