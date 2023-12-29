<?php

class Crypt3DES extends CryptBase
{
	/**
	 * @param string $key 24个字符密钥
	 * @param string $mode Crypt3DES::TDES_*
	 * @param string $padding_mode CryptBase::PADDING_*
	 * @param string $encoding CryptBase::ENCODING_*
	 */
	public function __construct($key, $mode, $padding_mode, $encoding = Crypt3DES::ENCODING_BASE64)
	{
		parent::__construct($encoding, $padding_mode);
		assert(strlen($key) === 24, 'invalid key size');
		parent::setModeAndKey($mode, $key);
		parent::setPaddingBlockSize(8);
		parent::setIVSize(8);
	}

	// 密钥24字节，iv 8字节
	const TDES_ECB = 'DES-EDE3';
	const TDES_CBC = 'DES-EDE3-CBC';
}
