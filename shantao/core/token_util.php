<?php

class TokenUtil
{
	
	public static function encrypt($token)
	{
		return base64_encode($token);
	}
	
	public static function decrypt($token)
	{
		return base64_decode($token);
	}
	/**
	 * 规则：当前时间戳 分隔符 UID 分隔符 随机码
	 * @param int $uid
	 */
	public static function createToken(int $uid)
	{
		return time() . self::SEPARATOR . $uid . self::SEPARATOR . rand(0, 9999);
	}

	/**
	 * 从token中获得UID
	 * @param string $token
	 * @return mixed|string|null
	 */
	public static function getUidFromToken(string $token)
	{
		if (empty($token) === true) {
			return null;
		}
		$param = explode(self::SEPARATOR, $token);
		if (count($param) !== 3) {
			return null;
		}
		return $param[1];
	}
	
	
	
	
	const SEPARATOR = '-';
}
