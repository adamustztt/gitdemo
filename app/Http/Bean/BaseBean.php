<?php


namespace App\Http\Bean;

use Illuminate\Database\Eloquent\Model;

class BaseBean
{
    /**
     * 构造函数
     * @param $data 数据集
     */
    public function __construct($data = [])
    {
        if ($data instanceof Model) {
            $data = $data->toArray();
        }
        if (!empty($data)) {
            foreach ($data as $key => $val) {
                if (property_exists($this, $key) && !is_null($val)) {
                    $setMethodName = $this->getSetMethodName($key);
                    if (method_exists($this, $setMethodName)) {
                        $this->$setMethodName($val);
                    } else {
                        $this->$key = $val;
                    }
                }
            }
        }
    }

    /**
     * 获取set方法名称
     * @param $key
     * @return string
     */
    protected function getSetMethodName($key)
    {
        return "set" . ucfirst($key);
    }

    /**
     * 获取get方法名称
     * @param $key
     * @return string
     */
    protected function getGetMethodName($key)
    {
        return "get" . ucfirst($key);
    }

    public function toArray()
    {
        $data = [];
        //获取所有属性
        $reflectInstance = new \ReflectionClass(get_class($this));
        $properties = $reflectInstance->getProperties();
        foreach ($properties as $property){
            $key = $property->getName();
            $getMethodName = $this->getGetMethodName($key);
            $data[$key] = method_exists($this, $getMethodName) ? $this->$getMethodName() : $this->$key;
            if(is_null($data[$key])){
                unset($data[$key]);
            }
        }
        return $data;
    }
}
