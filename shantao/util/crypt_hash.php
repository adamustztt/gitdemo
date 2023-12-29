<?php

class CryptHash
{
	const MD5 = 'md5';
	const SHA256 = 'sha256';
	const SHA512 = 'sha512';
	const SHA1 = 'sha1';

	/**
	 * HASH一个密码
	 *
	 * @param string $password 密码
	 * @param string $algo MHASH_*
	 * @param string $salt
	 * @param int $rounds 重复次数
	 * @return string
	 */
	public static function hash($password, $algo = CryptHash::SHA256, $rounds = 5000, $salt = null)
	{
		$salt = $salt ?? self::salt(32);
		return json_encode([
			'algo' => $algo,
			'rounds' => $rounds,
			'salt' => $salt,
			'hash' => self::doHash($password, $algo, $salt, $rounds)
		]);
	}

	/**
	 * 验证密码和HASH是否一致
	 *
	 * @param string $password 密码
	 * @param string $hash 散列值
	 * @return bool
	 */
	public static function verify($password, $hash)
	{
		$json = json_decode($hash, true);
		if (JSON_ERROR_NONE !== json_last_error()
			|| !isset($json['hash']) || !is_string($json['hash'])
			|| !isset($json['salt']) || !is_string($json['salt'])
			|| !isset($json['algo']) || !is_string($json['algo'])
			|| !isset($json['rounds']) || !is_int($json['rounds'])
		) {
			return false;
		}

		return hash_equals($json['hash'], self::doHash($password, $json['algo'], $json['salt'], $json['rounds']));
	}

	/**
	 * 生成一个定长的salt
	 *
	 * @param $size
	 * @return string
	 */
	public static function salt($size)
	{
		$ls = [];
		while ($size-- > 0) {
			$ls[] = chr(mt_rand(33, 126));
		}

		return implode('', $ls);
	}

	/**
	 * @param string $pass
	 * @param string $algo
	 * @param string $salt
	 * @param int $rounds
	 * @return string
	 */
	private static function doHash($pass, $algo, $salt, $rounds)
	{
		while ($rounds-- > 0) {
			$pass = hash($algo, $salt . $pass . $salt, true);
		}

		return bin2hex($pass);
	}
}
