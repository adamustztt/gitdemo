<?php

class CryptDES extends CryptBase
{
	/**
	 * @param string $key 8个字符密钥
	 * @param string $mode CryptAES::DES_*
	 * @param string $padding_mode CryptBase::PADDING_*
	 * @param string $encoding CryptBase::ENCODING_*
	 */
	public function __construct($key, $mode, $padding_mode, $encoding = CryptDES::ENCODING_BASE64)
	{
		parent::__construct($encoding, $padding_mode);
		assert(strlen($key) === 8, 'invalid key size');
		parent::setModeAndKey($mode, $key);
		parent::setPaddingBlockSize(8);
		parent::setIVSize(8);
	}

	// 密钥8字节，iv 8字节
	const DES_ECB = 'DES-ECB';
	const DES_CBC = 'DES-CBC';
}
