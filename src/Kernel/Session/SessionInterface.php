<?php
namespace Numst\Kernel\Session;

interface SessionInterface{

    /**
     * start session storage
     */
    public function start();

    /**
     * 获取session ID
     */
    public function getId();

    /**
     * 设置session ID
     */
    public function setId($id);

    /**
     * 获取session Name
     */
    public function getName();

    /**
     * 设置Session Name
     */
    public function setName();

    /**
     * 使session 无效
     */
    public function invalidate($lifetime = null);

    /**
     * 将当前Session迁移到新的session id，同时维护所有session
     */
    public function migrate($destory = false, $lifetime = null);

    /**
     * 强制保存并关闭会话
     */
    public function save();

    /**
     * 检查session 中的属性
     */
    public function has($name);

    /**
     * 获取session中属性的值
     */
    public function get($name, $default = null);

    /**
     * 获取全部session
     */
    public function all();

    /**
     * 替换session中的属性
     */
    public function replace(array $attibutes);

    /**
     * 移除一个属性
     */
    public function remove($name);

    /**
     * 清空session
     */
    public function clear();

    /**
     * 检测session是否开始
     */
    public function isStarted();

}