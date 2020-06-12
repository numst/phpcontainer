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

class Container {
	/**
	 * 容器绑定
	 * @var array()
	 */
	public building = [];

	/**
	 * 注册一个绑定到容器
	 * @param  abstract  $abstract 抽象类
	 * @param  class  $concrete 抽象类的具体实现
	 * @param  boolean $shared   是否共享
	 */
	public function bind($abstract,$concrete = null, $shared = false){
		if (is_null($concrete)) {
			$concrete = $abstract;
		}

		if (!$concrete instanceOf Closure){
			$concrete = $this->getClosure($abstract,$concrete);
		}

		$this->building[$abstract] = compact("concrete","shared");
	}

	//注册一个共享的绑定单利
	public function singleton($abstract,$concrete,$shared = true){
		$this->bind($abstract,$concrete,$shared);
	}
	/**
	 * 生成回调闭包
	 * @param  class $abstract 抽象类
	 * @param  class $concrete 抽象类具体实现
	 * @return closure         闭包函数
	 */
	public function getClosure($abstract,$concrete){
		return function ($c) use($abstract,$concrete) {
			$method = ($abstract == $concrete)?'build':'make';
			return $c->$method($concrete);
		}
	}

	/**
	 * 生成容器
	 * @param  class $abstract  抽象类
	 * @return object           注入到容器内的实例
	 */
	public function make($abstract){
		$concrete = $this->getConcrete($abstract);

		if ($this->isBuildable($concrete,$abstract)) {
			$object = $this->build($concrete);
		} else {
			$object = $this->make($concrete);
		}

		return $object;
	}

	/**
	 * 获取绑定的回调函数
	 * @param  class $abstract  抽象类
	 * @return object           注入到容器内的实例
	 */
	public function getConcrete($abstract){
		if (!isset($this->building[$abstract])){
			return $abstract;
		}

		return $this->building[$abstract]['concrete'];
	}

	/**
	 * 判断是否可以创建服务实体
	 * @param  [type]  $concrete [description]
	 * @param  [type]  $abstract [description]
	 * @return boolean           [description]
	 */
	public function isBuildable($concrete,$abstract){
		return $concrete == $abstract || $concrete instanceOf Closure;
	}

	/**
	 * 根据实例名称实例具体对象
	 * @return [type] [description]
	 */
	public function build($concrete){
		if ($concrete instanceof Closure){
			return $concrete($this);
		}

		//创建反射对象
		$reflector = new ReflectionClass($concrete);
		if (!$reflector->isInstanceable()) {
			//抛出异常
			throw new \Exception("无法实例化");
		}
		$constructor = $reflector->getConstructor();
		if (is_null($constructor)){
			return new $concrete;
		}

		$dependencies = $constructor->getParameters();
		$instance = $this->getDependencies($dependencies);
		// 从给出的参数创建一个新的类实例
		return $reflector->newInstanceArgs($instance);
	}

	/**
	 * 通过反射解决参数依赖
	 * @param  array  $dependencies [description]
	 * @return [type]               [description]
	 */
	public function getDependencies(array $dependencies){
		$results = [];
		foreach ($dependencies as $dependency) {
			$results[] = is_null($dependency->getClass())
				?$this->resolvedNonClass($dependency)
				:$this->resolvedClass($dependency);
		}
		return $results;
	}

	/**
	 * 解决一个没有类型提示的依赖
	 * @return [type] [description]
	 */
	public function resolvedNonClass(ReflectionParameter $parameter){
		if ($parameter->isDefaultValueAvailable){
			return $parameter->getDefaultValue();
		}
		throw new \Exception('反射解析变量出错');
	}

	/**
	 * 通过容器解决依赖
	 */
	public function resolvedClass(ReflectionParameter $parameter){
		return $this->make($parameter->getClass()->name);
	}
}