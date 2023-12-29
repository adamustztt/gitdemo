<?php

/*
 * This file is part of ibrand/EC-Open-Core.
 *
 * (c) 果酱社区 <https://guojiang.club>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Http\Request;

if (!function_exists('collect_to_array')) {
    /**
     * @param $collection
     *
     * @return array
     */
    function collect_to_array($collection)
    {
        $array = [];
        foreach ($collection as $item) {
            $array[] = $item;
        }

        return $array;
    }
}

if(!function_exists('create_order_sn')) {
    /**
     * 随机订单号
     */

    function create_order_sn() {
        //$extno = microtime(true)*10000;
        $extno = trim(substr(microtime(),2,9)).rand(0,9999);
        $yCode = array('AC', 'BD', 'CE', 'DH', 'EF', 'FG', 'GH', 'HI', 'IJ', 'JK');
        $yCode1 = array('CA', 'DB', 'EC', 'HD', 'FE', 'GF', 'HG', 'IH', 'JI', 'KJ');
        $orderSn = $yCode[array_rand($yCode,1)] .intval(date('Y')-2019).date('mdHis').$extno;

        return $orderSn.$yCode1[array_rand($yCode1,1)];
    }

}

if(!function_exists('getRandom')) {
    /**
     * 随机字符串
     * @param $param
     * @return string
     */
     function getRandom($param,$length=16){
        $str="0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $key = "";
        for($i=0;$i<$param;$i++) {
            $key .= $str{mt_rand(0,$length)};    //生成随机字符串
        }
        return $key;
    }
}

/**
 * 是否为手机号码
 * @param $string
 * @return bool
 */
if (! function_exists('isMobile')) {
    function isMobile($string) {
        return !!preg_match('/^1[3|4|5|6|7|8|9]\d{9}$/', $string);
    }
}

if (!function_exists('request')) {
	/**
	 * Get an instance of the current request or an input item from the request.
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return Request|string|array
	 */
	function request($key = null, $default = null)
	{
		if (is_null($key)) {
			return app('request');
		}
		return app('request')->input($key, $default);
	}
}
/**
 * Select选择字符串拼接 可以处理 多表联查
 * 要table.field 例如 ： user.name
 * @param string $table array $field
 */
if(!function_exists('selectList')){
	function selectList($table, $field)
	{
		if(!empty($table)){
			$field = explode(',',$field);
			$where = [];
			foreach($field as $value){
				$where[] = $table.'.'.trim($value);
			}
			return $where;
		}
		$field = explode(',',$field);
		$where = [];
		foreach($field as $value){
			$where[] = trim($value);
		}
		return $where;
	}
}
