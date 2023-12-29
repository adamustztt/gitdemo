<?php

/**
 * 注意：此类依赖于 .env 配置文件中 TERMINAL_SECRET_KEY 配置项
 */
class TerminalSig
{
	
	public static function generateSignatureForRequest($data)
	{
		return strtolower(md5(sha1($data) . config('terminal.secret_key')) );
	}


	/**
	 * 对数据做验证
	 *
	 * @param string $sig
	 * @param string $data
	 * @return bool
	 */
	public static function verifySignatureForIncomingRequest($sig, $data)
	{
		$correct_sig = self::generateSignatureForRequest($data);
		if ($correct_sig === $sig) {
			return true;
		}
		return false;
	}
	public static function verifySignatureForResponse($sig, $data)
	{
		
	}
}
