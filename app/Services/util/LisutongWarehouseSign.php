<?php


namespace App\Services\util;


class LisutongWarehouseSign
{
	/**
	 * 方法名: 封装接口请求
	 * @param $url 地址
	 * @param $content 参数内容
	 * @param $APP_KEY
	 * @param $APPSECRET
	 * @return mixed 返回结果
	 * 注意: $content参数传入方式: json_encode($content,JSON_UNESCAPED_SLASHES)
	 */
	public static function http_post_param($url,$content,$APP_KEY,$APPSECRET){
//		dd($content,$url);
		//生成参数，加密数据 
		 $parStr = self::makeParam($content,$APP_KEY,$APPSECRET);
		 return $parStr;
		//post 提交json
//		return self::http_request($url,$parStr);
	}

	/**
	 * 方法名: 签名处理
	 * @param $method 请求类型 POST\GET
	 * @param array $parameter 请求参数
	 * @param $accessKeySecret
	 * @return string 签名
	 */
	private static function  generate($method, array $parameter, $accessKeySecret) {
		$signString = self::generateSignString($method, $parameter);
		$signature = self::hmacSHA1Signature($accessKeySecret."&", $signString);
		$signature = self::newStringByBase64($signature);
		if ("POST"==$method){
			return $signature;
		}
		return urlencode($signature);
	}

	/**
	 * 方法名: 签名字符串处理
	 * @param $httpMethod 请求类型
	 * @param array $parameter 参数
	 * @return null|string
	 */
	private static function generateSignString($httpMethod, array $parameter) {
		$sortParameter = $parameter;
		ksort($sortParameter);
		$canonicalizedQueryString = self::generateQueryString($sortParameter, true);
		if (null == $httpMethod) {
			return null;
		}
		$SEPARATOR = "&";
		$stringToSign = $httpMethod.$SEPARATOR;
		$stringToSign .= self::percentEncode('/').$SEPARATOR;
		$stringToSign .= self::percentEncode($canonicalizedQueryString);
		return $stringToSign;
	}

	/**
	 * 方法名: 签名Hash
	 * @param $secret
	 * @param $baseString 数据
	 * @return string
	 */
	private static function hmacSHA1Signature($secret, $baseString){
		return hash_hmac("sha1", $baseString, $secret, true);
	}

	private static function newStringByBase64($bytes) {
		return base64_encode($bytes);
	}

	/**
	 * 方法名: 字符串拼接GET(不推荐)
	 * @param array $queries
	 * @return string
	 */
	private static function composeStringToSign(array  $queries) {
		$sortedKeys = $queries;
		ksort($sortedKeys);
		$canonicalizedQueryString = '';
		foreach ($sortedKeys as $key => $value) {
			$canonicalizedQueryString .="&".self::percentEncode($key)."=".self::percentEncode($value);
		}
		$stringToSign = 'GET';
		$stringToSign .= '&';
		$stringToSign .= self::percentEncode("/");
		$stringToSign .= '&';
		$stringToSign .= self::percentEncode(substr($canonicalizedQueryString));
		return $stringToSign;
	}

	/**
	 * 方法名: 字符串拼接处理(推荐)
	 * @param array $params
	 * @param $isEncodeKV
	 * @return string
	 */
	private static function generateQueryString(array $params, $isEncodeKV) {
		$canonicalizedQueryString = '';
		foreach ($params as $key => $value) {
			if ($isEncodeKV){
				$canonicalizedQueryString .= self::percentEncode($key);
				$canonicalizedQueryString .= '=';
				$canonicalizedQueryString .= self::percentEncode($value);
				$canonicalizedQueryString .= '&';
			}else{
				$canonicalizedQueryString .= $key;
				$canonicalizedQueryString .= '=';
				$canonicalizedQueryString .= $value;
				$canonicalizedQueryString .= '&';
			}
		}
		$canonicalizedQueryString = rtrim($canonicalizedQueryString,'&');
		return $canonicalizedQueryString;
	}

	/**
	 * 方法名: 数据编码
	 * @param $value
	 * @return mixed|string
	 */
	private static function percentEncode($value) {
		if($value == null){
			return '';
		}
		$str = str_replace("+", "%20",urlencode($value));
		$str = str_replace("*", "%2A",$str);
		$str = str_replace("%7E", "~",$str);
		return $str;
	}

	/**
	 * 方法名: HTTP请求
	 * @param $url 地址
	 * @param null $data 参数
	 * @return mixed 返回值
	 */
	private static  function http_request($url, $data = null)
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		if (!empty($data)) {
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		}
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		$output = curl_exec($curl);
		curl_close($curl);
		return $output;
	}

	/**
	 * 方法名: 时间戳
	 * @return string
	 */
	private static function get_millistime()
	{
		$microtime = microtime();
		$comps = explode(' ', $microtime);
		return sprintf('%d%03d', $comps[1], $comps[0] * 1000);
	}

	/**
	 * 方法名: 处理请求参数
	 * @param $content 请求参数
	 * @param $APP_KEY
	 * @param $APPSECRET
	 * @return string 请求参数
	 */
	private static  function makeParam($content,$APP_KEY,$APPSECRET) {
		$to = array();
		$to['appkey'] = $APP_KEY;
		$to['timestamp'] = self::get_millistime();//根式: 1601972639151
		$to['content'] = $content;
		$to['sign'] = self::generate('POST', $to, $APPSECRET);
		return json_encode($to,JSON_UNESCAPED_SLASHES);
	}

}
