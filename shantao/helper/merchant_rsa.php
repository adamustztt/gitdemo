<?php

class MerchantRSA
{
	/**
	 * 对数据做签名
	 *
	 * @param string $data
	 * @return null|array [ '<method>', '<data>' ]
	 */
	public static function generateSignatureForRequest($data, $merchant_name)
	{
		return self::signWithPrivateKey($data, $merchant_name);
	}
	public static function generateSignatureForDie($data, $merchant_name)
	{
		return self::signWithPrivateKey($data, $merchant_name);
	}


	/**
	 * 对数据做验证
	 *
	 * @param string $method
	 * @param string $sig
	 * @param string $data
	 * @param string $merchant_name
	 * @return bool
	 */
	public static function verifySignatureForIncomingRequest($method, $sig, $data, $merchant_name)
	{
		return self::verifyWithPublicKey($method, $sig, $data, $merchant_name);
	}
	public static function verifySignatureForResponse($method, $sig, $data, $merchant_name)
	{
		return self::verifyWithPublicKey($method, $sig, $data, $merchant_name);
	}


	/**
	 * 对数据做签名
	 *
	 * @param string $data
	 * @param string $merchant_name
	 * @return null|array [ '<method>', '<data>' ]
	 */
	private static function signWithPrivateKey($data, $merchant_name)
	{
		$key = static::getPrivateKey($merchant_name);
		if (!empty($key)) {
			$rsa = new CryptRSA(CryptRSA::ENCODING_HEX);
			$sig = $rsa->signWithPrivateKey($key, OPENSSL_ALGO_SHA256, $data);
			return [ 'SHA256withRSA', $sig ];
		}
		
		return null;
	}


	/**
	 * 对数据做验证
	 * SHA256withRSA
	 * @param string $method
	 * @param string $sig
	 * @param string $data
	 * @param string $merchant_name
	 * @return bool
	 */
	private static function verifyWithPublicKey($method, $sig, $data, $merchant_name)
	{
		$key = static::getPublicKey($merchant_name);
		if (empty($key)) {
			return false;
		}

		if ($method !== 'SHA256withRSA') {
			return false;
		}

		$rsa = new CryptRSA(CryptRSA::ENCODING_HEX);
		return $rsa->verifySignatureWithPublicKey($key, OPENSSL_ALGO_SHA256, $data, $sig);
	}


	/**
	 * 根据商户名称去配置文件拿私钥
	 * @param $merchant_name
	 * @return |null
	 */
	public static function getPrivateKey($merchant_name)
	{
		
		// 生成方法：
		// openssl genrsa -out private_pkcs1.pem 2048
		// openssl pkcs8 -topk8 -inform PEM -in private_pkcs1.pem -outform PEM -nocrypt -out private_pkcs8.pem
		return CryptRSA::wrapRawKeyData(config('merchant.' . $merchant_name . '.pri_key'),
			CryptRSA::X509_PEM_TYPE_PRIVATE_KEY_PKCS8);
	}

	/**
	 * @param $merchant_name
	 * @return |null
	 */
	public static function getPublicKey($merchant_name)
	{
		// 生成方法：
		// openssl rsa -in private_pkcs8.pem -pubout -out public.pem
		return CryptRSA::wrapRawKeyData(config('merchant.' . $merchant_name . '.ext_pub_key'),
			CryptRSA::X509_PEM_TYPE_PUBLIC_KEY_PKCS8);
	}
}
