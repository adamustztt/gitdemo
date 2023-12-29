<?php

class HTTP
{
	/**
	 * 发起网络请求，返回正文内容（不返回response header）
	 *
	 * @param string $url
	 * @param string|array|null $post_data
	 * @param int $timeout
	 * @param string $header
	 * @return string (response)
	 *         null (failed)
	 */
	public static function request($url, $post_data = null, $timeout = null, $request_headers = null)
	{
		$arr = self::requestEx($url, null, $post_data, $request_headers, $timeout, self::FLAG_FOLLOW_REDIRECT);
		if (!is_array($arr) || $arr['http_code'] !== 200) {
			return null;
		}
		return $arr['body'];
	}

	/**
	 * 发起网络请求，返回正文内容和response header
	 *
	 * @param string $url
	 * @param string $ua user agent
	 * @param string|array|null $post_data
	 * @param array $request_headers
	 * @param integer $timeout
	 * @param integer $flag mask of FLAG_*
	 * @return array [
	 *                 'http_code' => <http response code>,
	 *                 'header' => [ '<key>' => '<value>', ... ],
	 *                 'body' => '<返回正文内容>',
	 *             ]
	 *         integer (error number)
	 */
	public static function requestEx($url, $ua = null, $post_data = null, $request_headers = null,
		$timeout = null, $flag = 0)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);				// 如果不设置，请求内容会直接输出
		//curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . '/../certs/cacert.pem');
		if ($post_data !== null) {
			curl_setopt($ch, CURLOPT_POST, 1);
			if (is_array($post_data)) {
				$post_data = http_build_query($post_data);
			}
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		} else {
			curl_setopt($ch, CURLOPT_POST, 0);
		}

		if ($timeout !== null) {
			curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout);
		}
		if ($ua !== null) {
			curl_setopt($ch, CURLOPT_USERAGENT, $ua);
		}
		if ($request_headers !== null) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
		}
		//if ($flag & self::FLAG_SKIP_HTTPS_CERT_ERROR) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		//}
		if ($flag & self::FLAG_DISABLE_DH_CIPHER) {
			curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT:!DH');
		}

		curl_setopt($ch, CURLOPT_HEADER, true);
		$response = ($flag & self::FLAG_FOLLOW_REDIRECT) ? self::curlRedirectExec($ch, $url, 0) : curl_exec($ch);
		$curl_errno = (int)curl_errno($ch);
		if ($curl_errno !== 0) {
			$response = $curl_errno;
		} else {
			$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$header_map = [];
			foreach (explode("\r\n", substr($response, 0, $header_size)) as $header_line)
			{
				$header_info = explode(': ', $header_line, 2);
				if (count($header_info) === 2) {
					$header_map[$header_info[0]][] = $header_info[1];
				}
			}
			$response = array_merge([
				'http_code' => (int)curl_getinfo($ch, CURLINFO_HTTP_CODE),
				'header' => $header_map,
			], strlen($response) > $header_size ? [ 'body' => substr($response, $header_size) ] : []);
		}
		curl_close($ch);
		return $response;
	}


	/**
	 * 发起多个网络请求，返回正文内容和response header
	 *
	 * @param array [
	 *					<id1> => [
	 *						'url' => <url>,
	 *						'post_data' => <post_data>,
	 *						'request_headers' => <request_headers>,
	 *					],
	 *					<id2> => ...,
	 *					<id3> => ...,
	 *				]
	 * @param string $ua
	 * @param integer $timeout
	 * @param integer $flag
	 * @return array [
	 *					<id1> => [
	 *						'http_code' => <http response code>,
	 *						'header' => [ '<key>' => '<value>', ... ],
	 *						'body' => '<返回正文内容>',
	 *					] (| integer (error number)),
	 *					<id2> => ...,
	 *					<id3> => ...,
	 *				]
	 */
	public static function multiRequest(array $requests, $ua = null, $timeout = null, $flag = 0)
	{
		$chs = [];
		foreach ($requests as $id => $v) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $v['url']);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);				// 如果不设置，请求内容会直接输出
			// curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . '/../certs/cacert.pem');

			if ($v['post_data'] !== null) {
				curl_setopt($ch, CURLOPT_POST, 1);
				$post_data = is_array($v['post_data']) ? http_build_query($v['post_data']) : $v['post_data'];
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
			} else {
				curl_setopt($ch, CURLOPT_POST, 0);
			}

			if ($timeout !== null) {
				curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout);
			}
			if ($ua !== null) {
				curl_setopt($ch, CURLOPT_USERAGENT, $ua);
			}
			if ($v['request_headers'] !== null) {
				curl_setopt($ch, CURLOPT_HTTPHEADER, $v['request_headers']);
			}
			if ($flag & self::FLAG_SKIP_HTTPS_CERT_ERROR) {
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			}
			if ($flag & self::FLAG_DISABLE_DH_CIPHER) {
				curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT:!DH');
			}
			curl_setopt($ch, CURLOPT_HEADER, true);

			$chs[$id] = $ch;
		}

		$resp = [];
		if (count($chs) === 1) {
			reset($chs);
			$resp[key($chs)] = curl_exec(current($chs));
		} elseif (!empty($chs)) {
			$mh = curl_multi_init();
			foreach ($chs as $id => $ch) {
				curl_multi_add_handle($mh, $ch);
			}
			$active = null;
			do {
				curl_multi_exec($mh, $active);
				usleep(10000);
			} while ($active);

			foreach ($chs as $id => $ch) {
				$resp[$id] = curl_multi_getcontent($ch);
				curl_multi_remove_handle($mh, $ch);
			}
			curl_multi_close($mh);
		}

		$ret = [];
		foreach ($chs as $id => $ch) {
			$curl_errno = (int)curl_errno($ch);
			if ($curl_errno !== 0) {
				$ret[$id] = $curl_errno;
			} else {
				$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
				$header_map = [];
				foreach (explode("\r\n", substr($resp[$id], 0, $header_size)) as $header_line)
				{
					$header_info = explode(': ', $header_line, 2);
					if (count($header_info) === 2) {
						$header_map[$header_info[0]][] = $header_info[1];
					}
				}
				$ret[$id] = array_merge([
					'http_code' => (int)curl_getinfo($ch, CURLINFO_HTTP_CODE),
					'header' => $header_map,
				], strlen($resp[$id]) > $header_size ? [ 'body' => substr($resp[$id], $header_size) ] : []);
			}
			curl_close($ch);
		}

		return $ret;
	}

	/**
	 * 支持跳转的curl
	 *
	 * @param resource $ch
	 * @param string $old_url
	 * @param int $redirects
	 * @return mixed
	 */
	private static function curlRedirectExec($ch, $old_url, $redirects)
	{
		$data = curl_exec($ch);
		$http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($http_code === 301 || $http_code === 302) {
			$header = substr($data, 0, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
			preg_match('/(Location:|URI:)(.*?)\n/', $header, $matches);
			$new_url = trim(array_pop($matches));
			$url_parsed = parse_url($new_url);
			if (isset($url_parsed)) {
				curl_setopt($ch, CURLOPT_HTTPHEADER, [ 'Referer: ' . $old_url ]);
				curl_setopt($ch, CURLOPT_URL, $new_url);
				if ($redirects < 10) {
					return self::curlRedirectExec($ch, $new_url, $redirects + 1);
				} else {
					return false;
				}
			}
		}
		return $data;
	}


	/**
	 * @param string $url
	 * @param string|array|null $post_data
	 * @param array|null $headers
	 * @return boolean
	 */
	public static function fastPing($url, $post_data = null, $headers = null)
	{
		$url_info = parse_url($url);
		if (!isset($url_info['host'])) {
			return false;
		}

		$fp = @fsockopen($url_info['host'], isset($url_info['port']) ? $url_info['port'] : 80, $syserror, $error, 1);
		if (!$fp) {
			return false;
		}

		$get_string = isset($url_info['path']) ? $url_info['path'] : '/';
		if (isset($url_info['query'])) {
			$get_string .= '?' . $url_info['query'];
		}

		if (is_array($post_data)) {
			$post_data = http_build_query($post_data);
		}

		$out = ($post_data === null ? 'GET' : 'POST') . ' ' . $get_string . ' HTTP/1.1' . "\r\n";
		$out .= 'Host: ' . $url_info['host'] . "\r\n";
		if ($headers !== null) {
			foreach ($headers as $header) {
				$out .= $header . "\r\n";
			}
		}
		if ($post_data !== null) {
			$out .= 'Content-Type: application/x-www-form-urlencoded' . "\r\n";
			$out .= 'Content-Length: ' . strlen($post_data) . "\r\n";
		}
		$out .= 'Connection: Close' . "\r\n\r\n";
		if ($post_data !== null) {
			$out .= $post_data . "\r\n\r\n";
		}

		fwrite($fp, $out);
		fclose($fp);

		return true;
	}


	const FLAG_FOLLOW_REDIRECT			= 0x01;	// 遇到302自动跳转
	const FLAG_SKIP_HTTPS_CERT_ERROR	= 0x02;	// 忽略https证书错误
	const FLAG_DISABLE_DH_CIPHER		= 0x04;	// 禁止DH交换算法（openssl 升级后，要求服务器 DH key 必须大于 2048，有些时候对方服务器无法及时配合，用这个选项）
}
