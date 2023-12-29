<?php

use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;

class Util
{
	/**
	 * @param integer $id
	 * @return boolean
	 */
	public static function checkID($id)
	{
		return is_int($id) && $id > 0;
	}

	/**
	 * 检查字符串是否符合指定规则的数字
	 *
	 * @param string $digit
	 * @param int $len
	 * @return boolean
	 */
	public static function checkDigit($digit, $len = -1)
	{
		$reg = $len > 0 ? ('/^[0-9]{' . $len . '}$/') : ('/^[0-9]+$/');
		return (is_string($digit) || is_int($digit)) && preg_match($reg, (string)$digit);
	}

	/**
	 * 检测名字，里面不能出现非法字符
	 *
	 * @param string $name
	 * @return boolean
	 */
	public static function checkRealName($name)
	{
		$len = is_string($name) ? mb_strlen($name) : 0;
		if ($len < 1 || $len > 20) 
			CommonUtil::throwException([422, '收货人姓名不正确']);
//		if (preg_match('/[\s\t\r\n\`\~\!\@\#\$\%\^\&\*\(\)\-\_\=\+\[\{\]\}\\\|\;\:\'\"\,\<\.\>\/\?]+/', $name))
//			CommonUtil::throwException([422, '收货人姓名存在特殊字符']);
		return true;
	}

	/**
	 * 检测手机号
	 * @param $mobile
	 * @return bool
	 */
	public static function checkMobile($mobile)
	{
//		$reg = '/^1\d{10}$/';
		$reg = "/^1[34578]\d{9}$/";
		$ret = preg_match($reg, $mobile);
		if ( $ret=== false || $ret === 0) {
			return false;
		}
		return true;
	}

	/**
	 * 检测用户登录等名字，里面只能包含特定字符
	 *
	 * @param string $name name
	 * @return boolean
	 */
	public static function checkUserName($name)
	{
		// 登录名只允许包含“_.@-”和字母数字，长度要>2
		$reg = '/^[A-Za-z0-9\_\.\@\-]*$/';
		$len = strlen($name);
		return is_string($name) && preg_match($reg, $name) && $len > 2 && $len < 64;
	}

	/**
	 * 检查身份证
	 *
	 * @param string $str
	 * @return boolean
	 */
	public static function checkIDCardNumber($str)
	{
		if (!is_string($str) || !preg_match('/^([\d]{17}[xX\d]|[\d]{15})$/', $str)) {
			return false;
		}

		$city = [
			'11','12','13','14','15','21','22',
			'23','31','32','33','34','35','36',
			'37','41','42','43','44','45','46',
			'50','51','52','53','54','61','62',
			'63','64','65','71','81','82','91'
		];
		if (!in_array(substr($str, 0, 2), $city, true)) {
			return false;
		}

		$str = preg_replace('/[xX]$/i', 'a', $str);
		$len = strlen($str);

		if ($len === 18) {
			$birth = substr($str, 6, 4) . '-' . substr($str, 10, 2) . '-' . substr($str, 12, 2);
		} else {
			$birth = '19' . substr($str, 6, 2) . '-' . substr($str, 8, 2) . '-' . substr($str, 10, 2);
		}
		if (date('Y-m-d', strtotime($birth)) != $birth) {
			return false;
		}

		if ($len === 18) {
			$sum = 0;
			for ($i = 17; $i >= 0; $i--) {
				$sub = substr($str, 17 - $i, 1);
				$sum += (pow(2, $i) % 11) * (($sub === 'a') ? 10 : intval($sub, 11));
			}
			if ($sum % 11 != 1) {
				return false;
			}
		}

		return true;
	}

	/**
	 * 检查营业执照编号
	 *
	 * @param string $code
	 * @return boolean
	 */
	public static function checkLicense($code)
	{
		if (!is_string($code)) {
			return false;
		}

		$check_org = function($org) {
			$factor = [ 3, 7, 9, 10, 5, 8, 4, 2 ];
			$sum = 0;
			for ($i = 0; $i < 8; $i++) {
				if (is_numeric($org[$i])) {
					$sum += $org[$i] * $factor[$i];
				} else {
					$sum += (ord($org[$i]) - ord('A') + 10) * $factor[$i];
				}
			}
			$c = (11 - $sum % 11) % 11;
			$chk = $c === 10 ? 'X' : $c . '';
			return $chk === $org[8];
		};

		if (strlen($code) === 9 && is_numeric($code)) { // 老的9为机构代码
			// 机构校验位
			return $check_org($code);
		} elseif (strlen($code) === 15 && is_numeric($code)) { // 老的15位营业执照
			$p = 10;
			for ($i = 0; $i < 14; $i++) {
				$p += (int)$code[$i];
				$p = $p % 10 ?: 10;
				$p = ($p * 2) % 11;
			}
			$p += (int)$code[14];
			return $p % 10 == 1;
		} elseif (strlen($code) === 18) { // 三证合一
			if (preg_match('/^(1(1|2|3|9)|5(1|2|3|9)|9(1|2|3)|Y1)[0-9]{6}[0-9A-HJ-NP-RTUW-Y]{10}$/', $code) !== 1) {
				return false;
			}

			// 校验省份
			$city = [
				'11','12','13','14','15','21','22',
				'23','31','32','33','34','35','36',
				'37','41','42','43','44','45','46',
				'50','51','52','53','54','61','62',
				'63','64','65','71','81','82','91'
			];
			if (!in_array(substr($code, 2, 2), $city, true)) {
				return false;
			}

			// 机构校验位
			if (!$check_org(substr($code, 8, 9))) {
				return false;
			}

			// 统一校验位
			$map = [
				'A' => 10, 'B' => 11, 'C' => 12, 'D' => 13, 'E' => 14, 'F' => 15, 'G' => 16,
				'H' => 17, 'J' => 18, 'K' => 19, 'L' => 20, 'M' => 21, 'N' => 22, 'P' => 23,
				'Q' => 24, 'R' => 25, 'T' => 26, 'U' => 27, 'W' => 28, 'X' => 29, 'Y' => 30
			];
			$factor = [ 1, 3, 9, 27, 19, 26, 16, 17, 20, 29, 25, 13, 8, 24, 10, 30, 28 ];
			$sum = 0;
			for ($i = 0; $i < 17; $i++) {
				if (is_numeric($code[$i])) {
					$sum += (int)$code[$i] * $factor[$i];
				} else {
					$sum += $map[$code[$i]] * $factor[$i];
				}
			}

			$c = (31 - $sum % 31) % 31;
			if ($c < 10) {
				$chk = $c . '';
			} else {
				$chk = array_flip($map)[$c];
			}
			if ($chk !== $code[17]) {
				return false;
			}
			return true;
		}

		return false;
	}


	/**
	 * 检查字符串规范符合银行卡号
	 *
	 * @param string $bankcard
	 * @return boolean
	 */
	public static function checkBankcard($bankcard)
	{
		$len = is_string($bankcard) ? strlen($bankcard) : -1;
		return $len >= 15 && $len <= 20 && preg_match('/^[0-9]+$/', $bankcard);
	}

	/**
	 * 检查字符串符合日期规范 yyyy-mm-dd
	 *
	 * @param string $date
	 * @return boolean
	 */
	public static function checkDate($date)
	{
		return is_string($date) && date('Y-m-d', strtotime($date)) === $date;
	}

	/**
	 * 检查字符串符合日期时间规范 yyyy-mm-dd HH:MM:SS
	 *
	 * @param string $datetime
	 * @return boolean
	 */
	public static function checkDateTime($datetime)
	{
		return is_string($datetime) && date('Y-m-d H:i:s', strtotime($datetime)) === $datetime;
	}

	/**
	 * 检查数组中的每一项是否都在全集中出现，以及不得重复
	 *
	 * @param array $target array to be checked
	 * @param array $full_set array of full set
	 * @return boolean
	 */
	public static function checkSet($target, $full_set)
	{
		if (!is_array($target) || count(array_unique($target)) !== count($target)) {
			return false;
		}

		foreach ($target as $item) {
			if (!in_array($item, $full_set, true)) {	// 元素在全集中，且类型一致
				return false;
			}
		}
		return true;
	}


	/**
	 * 对密码求hash
	 *
	 * @param string $pass_md5
	 * @return string
	 */
	public static function encPassword($pass_md5)
	{
		$left_salt = '_lancai@2015_password_md5_05432654326321614535254_salt_value_left_';
		$right_salt = '_right_value_salt_02975429654023780514392_md5_password_lancai@2015_';
		return (is_string($pass_md5) && $pass_md5 !== '') ? md5($left_salt . strtolower($pass_md5) . $right_salt) : '';
	}

	/**
	 * 使用bcrypt对密码进行hash
	 *
	 * @param string $password
	 * @param integer $cost
	 * @return bool|string
	 */
	public static function bcryptHash($password, $cost = 12)
	{
		return password_hash($password, PASSWORD_BCRYPT, [ 'cost' => $cost ]);
	}

	/**
	 * 按照指定的规则给字符串打码
	 *
	 * @param string $info
	 * @param int $max_visible_len_left
	 * @param int $max_visible_len_right
	 * @return string
	 */
	public static function maskKeyInfo($info, $max_visible_len_left = 5, $max_visible_len_right = 5)
	{
		$len = mb_strlen((string)$info);
		if ($max_visible_len_left + $max_visible_len_right > (int)($len * 2 / 3)) {
			$max_visible_len_left = min((int)($len / 3), $max_visible_len_left);
			$max_visible_len_right = min((int)($len / 3), $max_visible_len_right);
		}
		return mb_substr($info, 0, $max_visible_len_left)
			. str_repeat('*', $len - $max_visible_len_left - $max_visible_len_right)
			. mb_substr($info, $len - $max_visible_len_right, $max_visible_len_right);
	}


	/**
	 * 计算两个日期之间的天数
	 *
	 * @param string|null $date1 null = today
	 * @param string|null $date2 null = today
	 * @return int days($date1 - $date2)
	 */
	public static function deltaDays($date1, $date2)
	{
		if ($date1 === null)
			$date1 = date('Y-m-d');
		if ($date2 === null)
			$date2 = date('Y-m-d');
		return (int)round((strtotime(date('Y-m-d', strtotime($date1)))
			- strtotime(date('Y-m-d', strtotime($date2)))) / 3600 / 24);
	}

	/**
	 * 返回指定天数之后/之前的日期
	 *
	 * @param string $date yyyy-mm-dd
	 * @param integer $delta_day
	 * @return string yyyy-mm-dd
	 */
	public static function genDate($date, $delta_day)
	{
		if ($date === null) {
			$date = date('Y-m-d');
		}
		if ($delta_day === 0) {
			return $date;
		}
		return date('Y-m-d', strtotime($date . ' ' . ($delta_day > 0 ? '+' : '-') . abs($delta_day) . ' day'));
	}

	/**
	 * FUCK strtotime +month
	 *
	 * @param string $datetime
	 * @param int $months
	 * @return string
	 */
	public static function monthAdd($datetime, $months)
	{
		$format = strpos($datetime, ' ') !== false ? 'Y-m-d H:i:s' : 'Y-m-d';
		$current = new DateTime($datetime);
		$next = new DateTime($datetime);
		$next->modify('last day of +' . $months . ' month');
		if ($current->format('d') >= $next->format('d')) {
			return $next->format($format);
		} else {
			return $current->add(new DateInterval('P' . $months . 'M'))->format($format);
		}
	}


	/**
	 * 返回用户的真实ip
	 *
	 * @return string ip addr
	 */
	public static function getUserIP()
	{
		if (isset($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return preg_match('/[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+/', $ip) ? $ip : '0.0.0.0';
	}

	/**
	 * 返回指定前缀的define常量
	 *
	 * @param string $prefix
	 * @return array of string
	 */
	public static function getDefinedVars($prefix)
	{
		$prefix_len = strlen($prefix);
		$arr = [];
		foreach (get_defined_constants() as $key => $val) {
			if (substr($key, 0, $prefix_len) == $prefix)
				$arr[] = $val;
		}
		return $arr;
	}

	/**
	 * 将金额转换成元的形式
	 *
	 * @param integer $amount
	 * @return string x.xx
	 */
	public static function formatMoney($amount)
	{
		return (string)number_format($amount / 100, 2, '.', '');
	}

	/**
	 * 将金额转换成分的形式
	 *
	 * @param string $amt_string x.xx
	 * @return integer $amount
	 */
	public static function extractAmount($amt_string)
	{
		return (int)round(100 * (float)str_replace(',', '', $amt_string));
	}

	/**
	 * 金额转换成大写的
	 *
	 * @param integer $num
	 * @return string
	 */
	public static function convertMoney($num)
	{
		$c1 = '零壹贰叁肆伍陆柒捌玖';
		$c2 = '分角元拾佰仟万拾佰仟亿';
		$i = 0;
		$c = '';
		while (1) {
			if ($i == 0) {
				$n = substr($num, -1, 1);
			} else {
				$n = $num % 10;
			}
			$p1 = substr($c1, 3 * $n, 3);
			$p2 = substr($c2, 3 * $i, 3);
			if ($n != '0' || ($n == '0' && ($p2 == '亿' || $p2 == '万' || $p2 == '元'))) {
				$c = $p1 . $p2 . $c;
			} else {
				$c = $p1 . $c;
			}
			$i = $i + 1;
			$num = $num / 10;
			$num = (int)$num;
			if ($num == 0) {
				break;
			}
		}
		$j = 0;
		$slen = strlen($c);
		while ($j < $slen) {
			$m = substr($c, $j, 6);
			if ($m == '零元' || $m == '零万' || $m == '零亿' || $m == '零零') {
				$left = substr($c, 0, $j);
				$right = substr($c, $j + 3);
				$c = $left . $right;
				$j = $j - 3;
				$slen = $slen - 3;
			}
			$j = $j + 3;
		}

		if (substr($c, -3, 3) == '零') {
			$c = substr($c, 0, -3);
		}
		if (empty($c)) {
			return '零元整';
		} else {
			return $c . '整';
		}
	}

	/**
	 * 把文件压缩成一个zip，然后返回zip数据
	 *
	 * @param array $file_list [ 'file_name' => '<file_content>', ... ]
	 * @return string
	 */
	public static function zip($file_list)
	{
		$zip = new ZipFile();
		foreach ($file_list as $file_name => $content) {
			$zip->addFile($content, $file_name);
		}

		return $zip->file();
	}
}
