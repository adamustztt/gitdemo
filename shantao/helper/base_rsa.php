<?php

class BaseRSA extends Base
{
	/**
	 * 对数据做签名
	 *
	 * @param string $data
	 * @return null|array [ '<method>', '<data>' ]
	 */
	public static function generateSignatureForRequest($data)
	{
		return self::signWithPrivateKey($data);
	}
	public static function generateSignatureForDie($data)
	{
		return self::signWithPrivateKey($data);
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
		return self::verifyWithPublicKey($method, $sig, $data);
	}
	public static function verifySignatureForResponse($method, $sig, $data)
	{
		return self::verifyWithPublicKey($method, $sig, $data);
	}


	/**
	 * 对数据做签名
	 *
	 * @param string $data
	 * @return null|array [ '<method>', '<data>' ]
	 */
	private static function signWithPrivateKey($data)
	{
		$key = static::getPrivateKey();
		if (!empty($key)) {
			$rsa = new CryptRSA(CryptRSA::ENCODING_HEX);
			$sig = $rsa->signWithPrivateKey($key, OPENSSL_ALGO_SHA256, $data);
			return [ 'SHA256withRSA', $sig ];
		}

		return null;
	}


	/**
	 * 对数据做验证
	 *
	 * @param string $method
	 * @param string $sig
	 * @param string $data
	 * @return bool
	 */
	private static function verifyWithPublicKey($method, $sig, $data)
	{
		$key = static::getPublicKey();
		if (empty($key)) {
			return true;
		}

		if ($method !== 'SHA256withRSA') {
			return false;
		}

		$rsa = new CryptRSA(CryptRSA::ENCODING_HEX);
		return $rsa->verifySignatureWithPublicKey($key, OPENSSL_ALGO_SHA256, $data, $sig);
	}


	/**
	 * 返回用BEGIN END包起来的公私钥信息
	 *
	 * @return string
	 */
	public static function getPrivateKey()
	{
		assert(false);
		// 生成方法：
		// openssl genrsa -out private_pkcs1.pem 2048
		// openssl pkcs8 -topk8 -inform PEM -in private_pkcs1.pem -outform PEM -nocrypt -out private_pkcs8.pem
		return null;
	}
	public static function getPublicKey()
	{
		assert(false);
		// 生成方法：
		// openssl rsa -in private_pkcs8.pem -pubout -out public.pem
		return null;
	}
}
