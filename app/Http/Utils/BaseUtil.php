<?php


namespace App\Http\Utils;


use Illuminate\Support\Facades\DB;

class BaseUtil
{
    /**
     * sql监听
     */
    public static function listenSql()
    {
        DB::listen(function ($sql) {
            $i = 0;
            $bindings = $sql->bindings;
            $rawSql = preg_replace_callback('/\?/', function ($matches) use ($bindings, &$i) {
                $item = isset($bindings[$i]) ? $bindings[$i] : $matches[0];
                $i++;
                return gettype($item) == 'string' ? "'$item'" : $item;
            }, $sql->sql);
            //记录sql
            LoggerFactoryUtil::addSqlMessage($rawSql);
//            echo $rawSql, "\n<br /><br />\n";
        });
    }

    /**
     * 将驼峰变量和下划线风格的变量名互转
     * @param string $name
     * @param int $type 0 驼峰转下划线 1 下划线转小驼峰 2 下划线转大驼峰
     * @return string
     */
    public static function parseName($name, $type=0) {
        if ($type == 1) {
            // 下划线转小驼峰
            return preg_replace_callback('/_([a-zA-Z])/', function($match){
                return strtoupper($match[1]);
            }, $name);
        }elseif ($type == 2){
            // 下划线转大驼峰
            return preg_replace_callback('/_([a-zA-Z])/', function($match){
                return strtoupper($match[1]);
            }, $name);
        }else {
            // 驼峰转下划线
            return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
        }
    }

    /**
     * 数组键转大写
     * @param array $data
     */
    public static function parseToArray($datas = [])
    {
        $tmpDatas = [];
        //将数组中的键转为驼峰
        foreach ($datas as $key=>$val){
            $key = BaseUtil::parseName($key,2);
            if(is_array($val)){//数组
                $tmp = [];
                foreach ($val as $tmpKey=>$tmpVal){
                    $tmpKey = BaseUtil::parseName($tmpKey,2);
                    $tmp[$tmpKey] = $tmpVal;
                }
            }elseif(is_object($val)){//对象
                //判断是否存在toArray方法
                if(method_exists($val,"toArray")){
                    $val = $val->toArray();
                    $tmp = [];
                    foreach ($val as $tmpKey=>$tmpVal){
                        $tmpK = BaseUtil::parseName($tmpKey,2);
                        $tmp[$tmpK] = $tmpVal;
                    }
                }else{
                    $tmp = $val;
                }
            }else{
                $tmp = $val;
            }
            $tmpDatas[$key] = $tmp;
        }
        return $tmpDatas;
    }
	/**
	 * 数组键驼峰转下划线
	 * @param array $data
	 */
	public static function parseArrayToLine($datas = [])
	{
		$tmpDatas = [];
		//将数组中的键转为下划线
		foreach ($datas as $key=>$val){
			$key = BaseUtil::parseName($key,0);
			if(is_array($val)){//数组
				$tmp = [];
				foreach ($val as $tmpKey=>$tmpVal){
					$tmpKey = BaseUtil::parseName($tmpKey,0);
					$tmp[$tmpKey] = $tmpVal;
				}
			}elseif(is_object($val)){//对象
				//判断是否存在toArray方法
				if(method_exists($val,"toArray")){
					$val = $val->toArray();
					$tmp = [];
					foreach ($val as $tmpKey=>$tmpVal){
						$tmpK = BaseUtil::parseName($tmpKey,0);
						$tmp[$tmpK] = $tmpVal;
					}
				}else{
					$tmp = $val;
				}
			}else{
				$tmp = $val;
			}
			$tmpDatas[$key] = $tmp;
		}
		return $tmpDatas;
	}
	public static function platformOrderNum()
	{
		return 100;
	}
	public static function getAddress($address)
	{
		return $address.",联系不到收货人派件员可以代收";
	}
	
	public static function randOrderNumber()
	{
		$r = rand(10000,99999);
		return time().$r;
	}
}
