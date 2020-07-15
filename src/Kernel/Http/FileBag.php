<?php
namespace Numst\Kernel\Http;

class FileBag extends ParameterBag{

    private static  $fileKeys = ['error', 'name', 'size', 'tmp_name', 'type'];

    public function __construct(array $parameters = []){
        $this->replace($parameters);
    }

    public function replace(array $files = []){
        $this->parameters = [];
        $this->add($files);
    }

    public function add(array $files = []){
        foreach ($files as $key => $file) {
            $this->set($key, $file);
        }
    }

    public function set($key, $value){
        if (!\is_array($value) && !$value instanceof UploadedFile) {
            throw new \InvalidArgumentException('An uploaded file must be an array or an instance of UploadedFile.');
        }

        parent::set($key, $this->convertFileInformation($value));
    }

    protected function convertFileInformation($file){
        if ($file instanceof UploadedFile) {
            return $file;
        }

        if (\is_array($file)) {
            $file = $this->fixPhpFilesArray($file);
            $keys = array_keys($file);
            sort($keys);

            if ($keys == self::$fileKeys) {
                if (UPLOAD_ERR_NO_FILE == $file['error']) {
                    $file = null;
                } else {
                    $file = new UploadedFile($file['tmp_name'], $file['name'], $file['type'], $file['error'], false);
                }
            } else {
                $file = array_map([$this, 'convertFileInformation'], $file);
                if (array_keys($keys) === $keys) {
                    $file = array_filter($file);
                }
            }
        }

        return $file;
    }

    /**
     * 多文件处理
     */
    protected function fixPhpFilesArray($data){
        $keys = array_keys($data);

        sort($keys);
        if (self::$fileKeys != $keys || !isset($data['name']) || !\is_array($data['name'])){
            return $data;
        }

        $files = $data;
        foreach(self::$fileKeys as $k){
            unset($files[$k]);
        }

        foreach($data['name'] as $key => $name){
            $files[$key] = $this->fixPhpFilesArray(
                [
                    'error' => $data['error'][$key],
                    'name'  => $name,
                    'type'  => $data['type'][$key],
                    'tmp_name' => $data['tmp_name'][$key],
                    'size' => $data['size'][$key],
                ]
            );
        }

        return $files;
    }
}