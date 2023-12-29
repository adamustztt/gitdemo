<?php

class CryptRSA extends CryptBase
{
	public function __construct($encoding = CryptBase::ENCODING_BASE64)
	{
		parent::__construct($encoding);
	}

	/**
	 * 用公钥加密
	 *
	 * @param string $public_key 用BEGIN END包起来的公钥信息
	 * @param string $input
	 * @return string
	 *         null key error
	 */
	public function encryptWithPublicKey($public_key, $input)
	{
		$res = openssl_pkey_get_public($public_key);
		if ($res === false) {
			return null;
		}
		openssl_public_encrypt($input, $encrypted, $res);
		return parent::encode($encrypted);
	}

	/**
	 * 用私钥解密
	 *
	 * @param string $private_key 用BEGIN END包起来的私钥信息
	 * @param string $input
	 * @return string
	 *         null key error
	 */
	public function decryptWithPrivateKey($private_key, $input)
	{
		$res = openssl_pkey_get_private($private_key);
		if ($res === false) {
			return null;
		}
		openssl_private_decrypt(parent::decode($input), $decrypted, $res);
		return $decrypted;
	}


	/**
	 * 用私钥签名
	 *
	 * @param string $private_key 用BEGIN END包起来的私钥信息
	 * @param integer $algo OPENSSL_ALGO_*
	 * @param string $input
	 * @return string
	 *         null key error
	 */
	public function signWithPrivateKey($private_key, $algo, $input)
	{
		$res = openssl_get_privatekey($private_key);
		if ($res === false) {
			return null;
		}
		openssl_sign($input, $sign, $res, $algo);
		openssl_free_key($res);
		return parent::encode($sign);
	}


	/**
	 * 验证签名
	 *
	 * @param string $public_key 用BEGIN END包起来的公钥信息
	 * @param integer $algo OPENSSL_ALGO_*
	 * @param string $input
	 * @param string $signature
	 * @return boolean
	 *         null key error
	 */
	public function verifySignatureWithPublicKey($public_key, $algo, $input, $signature)
	{
		$res = openssl_get_publickey($public_key);
		if ($res === false) {
			return null;
		}
		$result = (bool)openssl_verify($input, parent::decode($signature), $res, $algo);
		openssl_free_key($res);
		return $result;
	}


	/**
	 * 读取证书信息
	 *
	 * @param string $cert_data 用BEGIN END包起来的证书信息
	 * @return array
	 */
	public static function getCertificateID($cert_data)
	{
		$cert_data = openssl_x509_parse($cert_data);
		return $cert_data['serialNumber'];
	}


	/**
	 * 将原生的key数据封装成openssl能读取的key数据（加入头尾、做截断）
	 * @param string $raw_key_data
	 * @param string $type x509数据名称（即base64数据开头----BEGIN xxxx-----）
	 * @return string
	 */
	public static function wrapRawKeyData($raw_key_data, $type)
	{
		return '-----BEGIN ' . $type . '-----' . "\n"
			. chunk_split($raw_key_data, 64, "\n")
			. '-----END ' . $type . '-----';
	}


	const X509_PEM_TYPE_PUBLIC_KEY_PKCS8 = 'PUBLIC KEY';
	const X509_PEM_TYPE_PRIVATE_KEY_PKCS8 = 'PRIVATE KEY';
	const X509_PEM_TYPE_CERT = 'CERTIFICATE';
}
