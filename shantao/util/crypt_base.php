<?php

class CryptBase
{
	public function __construct($encoding, $padding_mode = self::PADDING_ZERO)
	{
		$this->encoding = $encoding;
		$this->padding_mode = $padding_mode;
	}


	/**
	 * 按照指定方式编码
	 *
	 * @param string $data
	 * @return string
	 */
	public function encode($data)
	{
		switch ($this->encoding) {
			case self::ENCODING_BASE64: return base64_encode($data);
			case self::ENCODING_HEX: return bin2hex($data);
			case self::ENCODING_RAW_BIN:
			default:
				return $data;
		}
	}

	/**
	 * 按照指定方式解码
	 *
	 * @param $data
	 * @return string
	 */
	public function decode($data)
	{
		switch ($this->encoding) {
			case self::ENCODING_BASE64: return base64_decode($data);
			case self::ENCODING_HEX: return hex2bin($data);
			case self::ENCODING_RAW_BIN:
			default:
				return $data;
		}
	}


	/**
	 * 设置padding加密块的大小
	 *
	 * @param integer $size 必须是8的整数倍
	 */
	public function setPaddingBlockSize($size)
	{
		assert($size >= 8 && ($size % 8) === 0);
		$this->padding_block_size = $size;
	}

	/**
	 * padding
	 *
	 * @param string $text
	 * @return string
	 */
	protected function padding($text)
	{
		switch ($this->padding_mode) {
		case self::PADDING_ZERO:
			$pad = ($this->padding_block_size - strlen($text) % $this->padding_block_size) % $this->padding_block_size;
			return $text . str_repeat(chr(0), $pad);
		case self::PADDING_PKCS7:
			$pad = $this->padding_block_size - (strlen($text) % $this->padding_block_size);
			return $text . str_repeat(chr($pad), $pad);
		default:
			return $text;
		}
	}

	/**
	 * unpadding
	 *
	 * @param string $text
	 * @return string
	 */
	protected function unpadding($text)
	{
		switch ($this->padding_mode) {
		case self::PADDING_ZERO:
			return rtrim($text, "\0");
		case self::PADDING_PKCS7:
			$pad = ord($text{strlen($text) - 1});
			if ($pad > strlen($text) || strspn($text, chr($pad), strlen($text) - $pad) != $pad) {
				return $text;
			}
			return substr($text, 0, -1 * $pad);
		default:
			return $text;
		}
	}


	/**
	 * 设置iv的大小
	 *
	 * @param integer $size 必须是8的整数倍
	 */
	public function setIVSize($size)
	{
		assert($size >= 8 && ($size % 8) === 0);
		$this->iv_size = $size;
	}

	/**
	 * 设置key和mode
	 *
	 * @param string $mode
	 * @param string $key
	 */
	public function setModeAndKey($mode, $key)
	{
		$this->mode = $mode;
		$this->key = $key;
	}

	/**
	 * 加密
	 *
	 * @param string $input
	 * @param string|null $iv
	 * @return string|null
	 */
	public function encrypt($input, $iv = null)
	{
		assert($iv === null || strlen($iv) === $this->iv_size, 'invalid lenght of iv');
		$ret = openssl_encrypt(self::padding($input), $this->mode, $this->key,
			OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
		if ($ret === false) {
			return null;
		}
		return self::encode($ret);
	}

	/**
	 * 解密
	 *
	 * @param string $encrypted
	 * @param string|null $iv
	 * @return string|null
	 */
	public function decrypt($encrypted, $iv = null)
	{
		assert($iv === null || strlen($iv) === $this->iv_size, 'invalid lenght of iv');
		$ret = openssl_decrypt(self::decode($encrypted), $this->mode, $this->key,
			OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
		if ($ret === false) {
			return null;
		}
		return self::unpadding($ret);
	}



	const ENCODING_RAW_BIN = 'bin';
	const ENCODING_HEX = 'hex';
	const ENCODING_BASE64 = 'base64';

	const PADDING_ZERO = 'zero';
	const PADDING_PKCS7 = 'pkcs7';


	private $encoding;
	private $padding_mode;
	private $padding_block_size;
	private $iv_size;
	private $mode;
	private $key;
}
