<?php
namespace Numst\File;
use Numst\File\Process;

class UploadFile extends File{
    private $error;
    private $test;
    private $originalName;
    private $mimeType;

    public function __construct(string $path, string $originalName, string $mimeType = null, int $error = null, $test = false){
        $this->originalName = $this->getName($originalName);
        $this->mimeType = $mimeType ?: 'application/octet-stream';
        if (4 < \func_num_args() ? !\is_bool($test) : null !== $error && @filesize($path) === $error) {
            @trigger_error(sprintf('Passing a size as 4th argument to the constructor of "%s" is deprecated since Symfony 4.1.', __CLASS__), E_USER_DEPRECATED);
            $error = $test;
            $test = 5 < \func_num_args() ? func_get_arg(5) : false;
        }

        $this->error = $error ?: UPLOAD_ERR_OK;
        $this->test = $test;

        parent::__construct($path, UPLOAD_ERR_OK === $this->error);
    }

    /**
     * 获取文件名
     */
    public function getOriginName(){
        return $this->originalName;
    }

    /**
     * 获取扩展名
     */
    public function getOriginExtension(){
        return pathinfo($this->originalName,PATHINFO_EXTENSION);
    }

    /**
     * 获取mime类型
     */
    public function getClientMimeType(){
        return $this->mimeType;
    
    }

    /**
     * 获取文件大小
     */
    public function getClientSize(){
        return $this->getSize();
    }
    /**
     * 获取错误信息
     */
    public function getError(){
        return $this->error;
    }

    /**
     * 验证上传
     */
    public function isValid(){
        $isOk = UPLOAD_ERR_OK === $this->error;

        return $this->test ? $isOk : $isOk && is_uploaded_file($this->getPathname);
    }


    public function move($directory, $name=null){
        if ($this->isValid){
            if ($this->test) {
                return parent::move($directory,$name);
            }
            $target = $this->getTargetFile($directory,$name);
            set_error_handler(function($type,$msg) use (&$error) {$error = $msg; });
            $moved = move_uploaded_file($this->getPathname,$target);
            restore_error_handler();
            if (!$moved) {
                throw new FileException(sprintf('Could not move the file "%s" to "%s" (%s). ',$this->getPathname(),$target,strip_tags($error)));
            }

            @chmod($target,0666 & ~umask());

            return $target;
        }
        throw new \FileException($this->getErrorMessage());
    }

    /**
     * 获取错误信息
     */
    public function getErrorMessage(){
        static $error = [
            UPLOAD_ERR_INI_SIZE => 'The file "%s" exceeds your upload_max_filesize ini directive (limit is %d KiB).',
            UPLOAD_ERR_FORM_SIZE => 'The file "%s" exceeds the upload limit defined in your form.',
            UPLOAD_ERR_PARTIAL => 'The file "%s" was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_CANT_WRITE => 'The file "%s" could not be written on disk.',
            UPLOAD_ERR_NO_TMP_DIR => 'File could not be uploaded: missing temporary directory.',
            UPLOAD_ERR_EXTENSION => 'File upload was stopped by a PHP extension.',
        ];

        $errorCode = $this->error;
        $maxFilesize = UPLOAD_ERR_INI_SIZE === $errorCode ? self::getMaxFileSize() / 1024 : 0;
        $message = isset($errors[$errorCode]) ? $error[$errorCode] : 'The file "%s" was not uploaded due to an unknown error.';

        return sprintf($message,$this->getOriginName(),$maxFilesize);
    }

    /**
     * 获取限制文件上传大小
     */
    public static function getMaxFilesize(){
        $sizePostMax = self::parseFileSize(ini_get('post_max_size'));
        $sizeUploadMax = self::parseFileSize(ini_get('upload_max_filesize'));

        return min($sizePostMax ?: PHP_INI_MAX,$sizeUploadMax ?: PHP_INI_MAX);
    }

    public static function parseFileSize($size):int{
        if ( '' == $size) {
            return 0;
        }
        $size = strtolower($size);
        
        $max = ltrim($size,'+');
        if (0 === strpos($max,'0x')) {
            $max = \intval($max, 16);
        } else if (0 === strpos($max,'0')) {
            $max = \inval($max, 8);
        } else {
            $max = (int)$max;
        }
        switch(substr($size,-1)){
            case 't':
                $max *= 1024;
            case 'g':
                $max *= 1024;
            case 'm':
                $max *= 1024;
            case 'k':
                $max *= 1024;
        }
        return $max;
    }
}