<?php
/**
 * 文件核心处理类
 */
namespace Numst\File\Process;

class File extends SplFileObject {

    public function __construct(string $path, bool $checkPath = true){
        if ($checkPath && !is_file($path)){
            throw new \FileException(sprintf('The file "$s" does not exists!',$path));
        }
        parent::__construct($path);
    }

    /**
     * 获取文件Mime类型 
     */
    public function getMimeType(){
        //判断是否开启fileinfo扩展
        if (!$this->isGuesserSupported()) {
            throw new LogicException("PHP version is less than 5.3.0 or fileinfo extension is not support!");
        }

        if (false === $finfo = new \finfo(FILEINFO_MIME_TYPE)){
            return null;
        }
        return $finfo->file($this->getPathname());
    }

    /**
     * 移动文件到指定目录
     */
    public function move($directory, $name = null){
        $target = $this->getTargetFile($directory,$name);

        set_error_handler(function($type,$msg) use (&$error) { $error = $msg; });
        $renamed = rename($this->getPathname(), $target);
        restore_error_handler();
        if (!$renamed) {
            throw new FileException(sprintf('Could not move the file "%s" to "%s" (%s).', $this->getPathname(), $target, strip_tags($error)));
        }

        @chmod($target, 0666 & ~umask());

        return $target;
    }

    /**
     * @return self
     */
    protected function getTargetFile($directory, $name = null){
        if (!is_dir($directory)) {
            if (false === @mkdir($directory, 0777, true) && !is_dir($directory)) {
                throw new FileException(sprintf('Unable to create the "%s" directory.', $directory));
            }
        } elseif (!is_writable($directory)) {
            throw new FileException(sprintf('Unable to write in the "%s" directory.', $directory));
        }

        $target = rtrim($directory, '/\\').\DIRECTORY_SEPARATOR.(null === $name ? $this->getBasename() : $this->getName($name));

        return new self($target, false);
    }

    /**
     * 获取文件名
     */
    protected function getName($name){
        $originalName = str_replace('\\', '/', $name);
        $pos = strrpos($originalName, '/');
        $originalName = false === $pos ? $originalName : substr($originalName, $pos + 1);

        return $originalName;
    }

    /**
     * 判断是否支持finfo_open
     */
    protected function isGuesserSupported(){
        return \function_exists('finfo_open');
    }
}