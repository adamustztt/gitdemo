<?php


namespace App\Services\Shop;


class JdShop
{
// 获取sign
	public static function getSignature($params)
	{
		$secret = env("JD_URL_SECRET");
		$str = '';
		ksort($params);
		foreach ($params as $k => $v) {
			$str .= $k . $v;
		}
		$str .= $secret;
		return md5($str);
	}
}

