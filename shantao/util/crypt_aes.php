<?php

class CryptAES extends CryptBase
{
	/**
	 * @param string $key 密钥 16, 24, 32个字符
	 * @param string $mode CryptAES::AES_*
	 * @param string $padding_mode CryptBase::PADDING_*
	 * @param string $encoding CryptBase::ENCODING_*
	 */
	function __construct($key, $mode, $padding_mode, $encoding = CryptAES::ENCODING_BASE64)
	{
		parent::__construct($encoding, $padding_mode);
		assert((int)substr($mode, 4, 3) / 8 === strlen($key), 'invalid key size');
		parent::setModeAndKey($mode, $key);
		parent::setPaddingBlockSize(16);
		parent::setIVSize(16);
	}

	// 密钥16字节，iv 16字节
	const AES_128_ECB = 'AES-128-ECB';
	const AES_128_CBC = 'AES-128-CBC';

	// 密钥24字节，iv 16字节
	const AES_192_ECB = 'AES-192-ECB';
	const AES_192_CBC = 'AES-192-CBC';

	// 密钥32字节，iv 16字节
	const AES_256_ECB = 'AES-256-ECB';
	const AES_256_CBC = 'AES-256-CBC';
}
