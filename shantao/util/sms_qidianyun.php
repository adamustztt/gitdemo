<?php

/**
 * 奇点云短信通道，暂只支持验证码短信接口，营销短信有需要再写
 */
class SMSQiDianYun
{
	public static function encodePercent($str)
	{
		$res = urlencode($str);
		$res = preg_replace('/\+/', '%20', $res);
		$res = preg_replace('/\*/', '%2A', $res);
		$res = preg_replace('/%7E/', '~', $res);
		return $res;
	}

	/**
	 * @param $apiParams
	 * @return string
	 */
	public static function buildSign($apiParams)
	{
		ksort($apiParams);
		$canonicalizedQueryString = '';
		foreach ($apiParams as $key => $value) {
			$canonicalizedQueryString .= '&' . self::encodePercent($key) . '=' . self::encodePercent($value);
		}
		$methodType = 'POST';
		return $methodType . '&%2F&' . self::encodePercent(substr($canonicalizedQueryString, 1));
	}
	
	public static function hmac($stringToSign, $accessSecret)
	{
		return base64_encode(hash_hmac('sha1', $stringToSign, $accessSecret . '&', true));
	}
	

	
	public static function send($mobile, $code, $sign_name = null, $template_code = null)
	{
		$content = array(
			'content' => "您的验证码是: $code ，请勿向任何人提供短信验证码，五分钟内有效。",
		);
		$api_params = array(
			'templateParam' => json_encode($content, JSON_UNESCAPED_UNICODE),
			'receiver'      => $mobile,
			'smsSignName'   => $sign_name ?? config('qidianyun.sign_name'),
			'templateCode'  => $template_code ?? config('qidianyun.template_code'),
			'timestamp'     => time() * 1000,
			'signType'      => 'HMAC',
			'account'       => config('qidianyun.account'),
		);

		// 组合请求参数
		$sign = self::buildSign($api_params);
		// 根据组合参数和密钥进行签名
		$sign = self::hmac($sign, config('qidianyun.secret'));
		// 将签名增加到参数中
		$api_params['sign'] = $sign;
		// 格式化准备发送参数
		$sendJson = json_encode($api_params, JSON_UNESCAPED_UNICODE);
		$ret = HTTP::request(self::API, $sendJson, null, [
			'Content-Type: application/json; charset=utf-8',
			'Content-Length: ' . strlen($sendJson)
		]);
		return $ret;
	}
	
	const API = 'https://smsapi.startdt.com/v2/sms/send';
}
