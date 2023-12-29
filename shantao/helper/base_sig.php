<?php

/**
 * 使用本类需要定义如下常量：
 *
 * class ImportantBase {
 *   const VERIFY_SALT;
 * }
 */
class BaseSig extends Base
{
	
	/**
	 * 对数据做签名
	 *
	 * @param string $data
	 * @return null|array [ '<method>', '<data>' ]
	 */
	public static function generateSignatureForRequest($data)
	{
		
	}
	public static function generateSignatureForDie($data)
	{
		return self::generateSignatureForRequest($data);
	}


	/**
	 * 对数据做验证
	 *
	 * @param string $method
	 * @param string $sig
	 * @param string $data
	 * @return bool
	 */
	public static function verifySignatureForIncomingRequest($method, $sig, $data)
	{
		
	}
	public static function verifySignatureForResponse($method, $sig, $data)
	{
		
	}
}
