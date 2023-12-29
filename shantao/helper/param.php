<?php

/**
 * Param check 用法示例：
 *
 * 例：检查 $req['data_int'] 是整数，$req['data_string'] 是字符串 $req['data_bool'] 是bool，$req['data_float'] 是浮点数
 * Base::checkAndDie([
 *     'data_int'    => Param::IS_INT_ID  . ERROR_1,
 *     'data_string' => Param::IS_STRING  . ERROR_2,
 *     'data_bool'   => Param::IS_BOOLEAN . ERROR_3,
 *     'data_float'  => Param::IS_FLOAT   . ERROR_4,
 * ], $req);
 *
 * 例：检查 $req['arr'] 是一个长度为 4~10 的数组，若不满足，报 ERROR_1 错误；
 *    且数组中每一项是字符串，若不满足，报 ERROR_2 错误
 * Base::checkAndDie([
 *     'arr' => [ ERROR_1 . Param::arrayLength(4, 10), [ Param::IS_STRING . ERROR_2 ] ]
 * ]), $req);
 *
 * 例：检查 $req['arr'] 是一个至少有一项的数组，若不满足，报 ERROR_1 错误；
 *    且数组中的每一项是一个对象，该对象的 data_int 属性为整数，否则报 ERROR_11；该对象的 data_string 为字符串，否则报 ERROR_12 错误；
 *    该对象的 data_array 属性为一个最多有5项的数组，否则报 ERROR_13 错误，此数组每一项必须是字符串，否则报 ERROR_131 错误
 * Base::checkAndDie([
 *     'arr' => [ ERROR_1 . Param::arrayLength(1), [
 *         'data_int'    => Param::IS_INT_ID  . ERROR_11,
 *         'data_string' => Param::IS_STRING  . ERROR_12,
 *         'data_array'  => [ ERROR_13 . Param::arrayLength(0, 5), [ Param::IS_STRING . ERROR_131 ] ]
 *     ]
 * ]), $req);
 */
class Param
{
	/**
	 * 对任意条件取反
	 *
	 * @param string $p
	 * @return string
	 */
	public static function not($p)
	{
		return str_replace('|#', '|!', $p);
	}

	/**
	 * 判断string的长度
	 *
	 * @param integer $min
	 * @param integer $max -1表示不设置最大值
	 * @return string
	 */
	public static function stringLength($min, $max = -1)
	{
		return self::wrap(self::IS_STRING_SPECIFIED_LEN, [ $min, $max === -1 ? PHP_INT_MAX : $max ]);
	}

	/**
	 * 判断string是否符合正则表达式
	 *
	 * @param string $regex
	 * @return string
	 */
	public static function regexMatch($regex)
	{
		return self::wrap(self::IS_STRING_MATCH_REGEX, $regex);
	}

	/**
	 * 判断string是否符合filter
	 *
	 * @param integer $filter FILTER_VALIDATE_*
	 * @return string
	 */
	public static function filterMatch($filter)
	{
		$valid_filters = [
			FILTER_VALIDATE_REGEXP, FILTER_VALIDATE_URL, FILTER_VALIDATE_EMAIL,
			FILTER_VALIDATE_IP, FILTER_VALIDATE_MAC,
		];
		assert(in_array($filter, $valid_filters, true), 'invalid filter');
		return self::wrap(self::IS_STRING_MATCH_FILTER, $filter);
	}

	/**
	 * 给定的值是否属于某个数组
	 *
	 * @param array $arr
	 * @return string
	 */
	public static function inArray($arr)
	{
		return self::wrap(self::IS_IN_ARRAY, $arr);
	}

	/**
	 * 给定值是否属于某个op
	 *
	 * @param string $op_prefix
	 * @return string
	 */
	public static function isOP($op_prefix)
	{
		return self::inArray(Util::getDefinedVars($op_prefix));
	}

	/**
	 * 给定的数组里面的所有值是否属于某个数组
	 *
	 * @param array $arr
	 * @return string
	 */
	public static function isSubset($arr)
	{
		return self::wrap(self::IS_SUBSET, $arr);
	}

	/**
	 * 给定的数组里面的所有值是否属于某个op
	 *
	 * @param string $op_prefix
	 * @return string
	 */
	public static function isOPSubset($op_prefix)
	{
		return self::isSubset(Util::getDefinedVars($op_prefix));
	}

	/**
	 * 判断数组的长度
	 *
	 * @param integer $min
	 * @param integer $max -1表示不设置最大值
	 * @return string
	 */
	public static function arrayLength($min, $max = -1)
	{
		return self::wrap(self::IS_ARRAY_SPECIFIED_LEN, [ $min, $max === -1 ? PHP_INT_MAX : $max ]);
	}

	/**
	 * 通过回调函数来验证
	 *
	 * @param callable $callable
	 * @return string
	 */
	public static function func($callable)
	{
		return self::wrap(self::CALLBACK, func_get_args());
	}

	/**
	 * 封装捆绑数据
	 *
	 * @param string $type self::*
	 * @param mixed $data
	 * @return string
	 */
	public static function wrap($type, $data)
	{
		$idx = self::saveBufferedData($data);
		$str = str_replace('$', $idx, $type, $c);
		assert($c === 1, 'invalid usage of ::wrap');
		return $str;
	}

	/**
	 * 封装额外数据
	 *
	 * @param mixed $data
	 * @return string
	 */
	public static function extra($data)
	{
		$idx = self::saveBufferedData($data);
		return '|^' . $idx . '|';
	}


	/**
	 * 输出调试信息
	 */
	public static function debug()
	{
		echo '<pre>';
		array_walk(self::$debug_info, function($v) { echo $v . "\n"; });
		echo '</pre>';
	}


	/**
	 * 执行检查
	 *
	 * @param array $rule
	 * @param array $v
	 * @return boolean true 数据合法
	 *         integer 数据非法，表示错误号（0表示没有指定错误号）
	 */
	public static function check($rule, &$v)
	{
		// 如果规则是数组，需要递归检查
		if (is_array($rule)) {
			// 这里面规则分两种情况：
			// 1. $v是对象数组：则rule为[0](可选) + 一组kv，$v必需跟kv符合
			// 2. $v是纯数组：则rule为[0](可选) + [1]，$v中的每一项必需满足[1]的规则
			// [0]表示对本数组的整体规则

			$pure_array = (count($rule) === 1 && isset($rule[0]))
				|| (count($rule) === 2 && isset($rule[0]) && isset($rule[1]));

			// 为了后续处理方便，先将[0]移出，这样rule就变成了数组子项的规则
			if ($pure_array ? (count($rule) === 2) : (isset($rule[0]))) {
				$rule_info = self::parseRule(array_shift($rule));
			} else {
				$rule_info = self::parseRule('');
			}

			if (!is_array($v)) {		// 压根不是数组，报错
				return self::saveDebugInfo($rule_info['error'], 'this', 'invalid');
			}

			if ($pure_array) {
				// 纯数组，对每个item进行检查
				foreach ($v as &$sub_v) {
					$ret = self::check($rule[0], $sub_v);
					if ($ret !== true) {
						return self::saveDebugInfo($ret ?: $rule_info['error'], 'subitem', 'invalid');
					}
				}
				unset($sub_v);
			} else {
				// assoc数组，对每组kv进行检查
				$scanned = [];		// 记录扫描过的key
				foreach ($v as $k => &$sub_v) {
					$r = $rule[$k] ?? null;
					// 数据中多了未知项，出错
					if (!isset($r)) {
						return self::saveDebugInfo($rule_info['error'], $k, 'unrecognized');
					}
					$ret = self::check($r, $sub_v);
					if ($ret !== true) {
						return self::saveDebugInfo($ret ?: $rule_info['error'], $k, 'invalid');
					}

					// 检查成功后，即从rule中删除，这样剩下的都是待查数据中不包含的
					$scanned[$k] = 1;
				}
				unset($sub_v);

				// 待检查数据是否缺失了必要项
				foreach ($rule as $k => $r) {
					if (!isset($scanned[$k])) {
						$r2 = self::parseRule(is_array($r) ? ($r[0] ?: '') : $r);
						if (!$r2['optional']) {
							return self::saveDebugInfo($r2['error'] ?: $rule_info['error'], $k, 'missing');
						}
					}
				}
			}
		} elseif (is_string($rule)) {
			$rule_info = self::parseRule($rule);
		} else {
			assert(0, 'invalid rule');
			return 0;
		}

		// 检查项
		foreach ($rule_info['check'] as $c) {
			$ret = self::checkSingleData($v, $c);
			if ($ret !== true) {
				return self::saveDebugInfo($rule_info['error'], 'this', 'invalid');
			}
		}

		// 取反检查项
		foreach ($rule_info['uncheck'] as $c) {
			$ret = self::checkSingleData($v, $c);
			if ($ret === true) {
				return self::saveDebugInfo($rule_info['error'], 'this', 'invalid');
			}
		}

		// 成功后，如果需要后续操作则执行
		foreach ($rule_info['post_action'] as $p) {
			self::processPostAction($v, $p, $rule_info, $rule);
		}
		return true;
	}




	/**
	 * 解析rule，生成结构化的数据
	 *
	 * @param string $rule
	 * @return array 参见$info定义
	 */
	private static function parseRule($rule)
	{
		$info = [
			'optional' => false,
			'check' => [],
			'uncheck' => [],
			'post_action' => [],
			'extra' => null,
			'error' => 0,
		];
		foreach (explode('|', $rule) as $r) {
			if ($r === '') {
				continue;
			}
			switch ($r[0]) {
				case 'o':		// 此项是否可以不存在
					$info['optional'] = true;
					break;
				case '#':		// 检查项
				case '!':		// 检查项取反
					$check = [];
					foreach (explode('*', substr($r, 1)) as $sr) {
						$par = explode(',', $sr);
						for ($i = 1; $i < count($par); $i++) {
							$par[$i] = self::$buffered_data[(int)$par[$i] - 1];
						}
						$check[] = $par;
					}
					$info[$r[0] === '#' ? 'check' : 'uncheck'][] = $check;
					break;
				case '@':		// post action
					$info['post_action'][] = substr($r, 1);
					break;
				case '^':		// 额外信息
					assert(empty($info['extra']), 'you cannot declare more than one extra info');
					$info['extra'] = self::$buffered_data[(int)substr($r, 1) - 1];
					break;
				default:		// 不认识的都当成错误号
					assert($info['error'] === 0, 'already have an error: ' . $info['error']);
					if (preg_match('/^[0-9]+$/', $r)) {
						$info['error'] = (int)$r;
					} elseif (preg_match('/^\{([A-Za-z0-9_]+)\}$/', $r, $matches)) {
						$info['error'] = $matches[1];
					}
					assert($info['error'] !== 0, 'this is not an valid error:' . $rule);
					break;
			}
		}
		return $info;
	}


	/**
	 * 执行单个检查
	 *
	 * @param mixed $v
	 * @param array $rule 每项check [
	 *          [  // 每个用*分割的子规则
	 *            0 => 规则名称，
	 *            1 => 规则wrap参数,
	 *            ...
	 *          ], ...
	 *        ]
	 * @return boolean
	 */
	private static function checkSingleData($v, $rule)
	{
		switch ($rule[0][0]) {		// 主规则的名字
			case 'b':
				return is_bool($v);

			case 'i':
				if (!is_int($v)) {
					return false;
				}
				if (is_null($rule[1])) {
					return true;
				}
				switch ($rule[1][0]) {		// 子规则的名字
					case null: return true;
					case '>=0': return $v >= 0;
					case '>0': return $v > 0;
					case 'mob':
						$reg = '/^1((3[0-9])|(4[57])|(5[0-35-9])|(7[0135678])|(8[0-9]))[0-9]{8}$/';
						return preg_match($reg, (string)$v) === 1;
				}
				break;

			case 'f':
				if (!is_float($v)) {
					return false;
				}
				switch ($rule[1][0]) {		// 子规则的名字
					case null: return true;
					case '>0': return $v > 0;
				}
				break;

			case 's':
				if (!is_string($v)) {
					return false;
				}
				if (!isset($rule[1])) {
					return true;
				}
				switch ($rule[1][0]) {		// 子规则的名字
					case null: return true;
					case '64': return preg_match('/^[0-9a-zA-Z=\/\+\-_]+$/', $v) === 1;
					case 'md5': return preg_match('/^[0-9a-f]{32}$/', $v) === 1;
					case 'd': return Util::checkDate($v);
					case 'dt': return Util::checkDateTime($v);
					case 'regex': return preg_match($rule[1][1], $v) === 1;
					case 'filter': return filter_var($v, $rule[1][1]) !== false;
					case 'user': return Util::checkUserName($v);
					case 'real': return Util::checkRealName($v);
					case 'idcard': return Util::checkIDCardNumber($v);
					case 'bank': return Util::checkBankcard($v);
					case 'len':
						$len = strlen($v);
						return $len >= $rule[1][1][0] && $len <= $rule[1][1][1];	// $rule[1][1]子规则的参数
				}
				break;

			case 'a':
				if (!is_array($v)) {
					return false;
				}
				switch ($rule[1][0]) {		// 子规则的名字
					case null: return true;
					case 'len': return count($v) >= $rule[1][1][0] && count($v) <= $rule[1][1][1];
					case 'sub': return Util::checkSet($v, $rule[1][1]);
					case 'r':
						if (count($v) !== 2 || $v[1] < $v[0]) {
							return false;
						}
						switch ($rule[2][0]) {		// 孙规则的名字
							case 'i': return is_int($v[0]) && is_int($v[1]) && $v[0] >= 0;
							case 'is': return is_int($v[0]) && is_int($v[1]);
							case 'f': return (is_float($v[0]) || is_int($v[0]))
								&& (is_float($v[1]) || is_int($v[1])) && $v[0] >= 0;
							case 'd': return Util::checkDate($v[0]) && Util::checkDate($v[1]);
							case 'dt': return Util::checkDateTime($v[0]) && Util::checkDateTime($v[1]);
						}
				}
				break;

			case 'm':
				if ($rule[1][0] === 'inarr') {		// 子规则的名字
					return in_array($v, $rule[1][1], true);
				}
				break;

			case 'c':
				$callable = $rule[0][1][0];
				$rule[0][1][0] = $v;
				return call_user_func_array($callable, $rule[0][1]);
		}
		assert(0, 'invalid check method: ' . var_export($rule, true));
		return false;
	}


	/**
	 * 检查通过后，执行后处理
	 *
	 * @param string|array $v
	 * @param string $post
	 * @param array $this_rule_info
	 * @param array $sub_rule
	 */
	private static function processPostAction(&$v, $post, $this_rule_info, $sub_rule)
	{
		if ($post === 'filter') {
			// 将filter里面的各个子项match到DBFilter上
			// DBFilter映射有如下3类：
			$map = [
				'#a*r' => Filter::TYPE_RANGE_INT,		// range信息（使用between）
				'#a' => Filter::TYPE_SET,				// array信息（使用in）
				'#' => Filter::TYPE_EQUAL,				// 判等（使用=）
			];
			$filters = [];
			foreach ($v as $fk => $fv) {
				$db_filter_type = null;
				if (is_array($sub_rule[$fk])) {
					// 如果规则本身就是数组，则一定db也是数组
					$info = self::parseRule($sub_rule[$fk][0]);
					$db_filter_type = Filter::TYPE_SET;
				} else {
					// 规则是字符串，则按照上面的规则依次寻找
					$info = self::parseRule($sub_rule[$fk]);
					foreach ($map as $mk => $mv) {
						if (strpos($sub_rule[$fk], $mk) !== false) {
							$db_filter_type = $mv;
							break;
						}
					}
				}
				// 如果传入参数的key跟db中使用的key名字不同，则会将alias保存到extra中，直接读取即可
				$filters[] = Filter::makeDBFilter($info['extra'] ?: $fk, $fv, $db_filter_type);
			}
			$v = $filters;
		} elseif ($post === 'sort') {
			$sort_rules = null;
			// sort映射表要从Sort::checkSorts检查函数中提取参数出来
			foreach ($this_rule_info['check'] as $c) {
				if ($c[0][0] === 'c') {		// 某一个check的主规则类型为“回调函数检查”
					$sort_rules = $c[0][1][1];		// 提取主规则的第二个参数（为回传callable的第一个传入参数）
					break;
				}
			}
			assert(isset($sort_rules), 'missing Sort::checkSorts function bind');
			$sorts = [];
			foreach ($v as $s) {
				$sort_replace = $sort_rules[$s['field']];
				if (is_string($sort_replace)) {
					$sort_key = $sort_replace;
				} elseif (is_array($sort_replace) && isset($sort_replace['name'])) {
					$sort_key = $sort_replace['name'];
				} else {
					$sort_key = $s['field'];
				}
				$sorts[] = Sort::makeSort($sort_key, $s['reverse'], $s['custom_order']);
			}
			$v = $sorts;
		} else {
			assert(0, 'invalid post action');
		}
	}


	private static function saveBufferedData($data)
	{
		self::$buffered_data[] = $data;
		return count(self::$buffered_data);
	}


	private static function saveDebugInfo($err, $key, $desc)
	{
		self::$debug_info[] = $desc . ': '. $key;
		return $err;
	}


	/**
	 * 规范：
	 *
	 * | - 分割各个检查条件
	 * * - 分割子规则
	 * , - 分割每个子规则里面的参数
	 * # - 表示检查规则的开头
	 * @ - 表示post action的开头
	 * ^ - 表示extra信息的开头
	 */


	const OPTIONAL					= '|o|';

	// mixed (boolean | int | string)
	const IS_IN_ARRAY				= '|#m*inarr,$|';		// 在指定的数组中出现

	// boolean
	const IS_BOOLEAN				= '|#b|';

	// int
	const IS_INT					= '|#i|';
	const IS_INT_POSITIVE_0			= '|#i*>=0|';		// >= 0
	const IS_INT_ID					= '|#i*>0|';		// > 0
	const IS_INT_AMOUNT				= '|#i*>0|';		// > 0
	const IS_INT_MOBILE				= '|#i*mob|';		// 是一个手机号

	// float
	const IS_FLOAT					= '|#f|';
	const IS_FLOAT_POSITIVE			= '|#f*>0|';		// > 0

	// string
	const IS_STRING					= '|#s|';
	const IS_STRING_BASE64			= '|#s*64|';		// string是一个base64
	const IS_STRING_MD5				= '|#s*md5|';		// string是一个md5
	const IS_STRING_DATE			= '|#s*d|';			// string是date类型
	const IS_STRING_DATETIME		= '|#s*dt|';		// string是datetime类型
	const IS_STRING_SPECIFIED_LEN	= '|#s*len,$|';		// string长度有要求
	const IS_STRING_MATCH_REGEX		= '|#s*regex,$|';	// string符合正则表达式
	const IS_STRING_MATCH_FILTER	= '|#s*filter,$|';	// string符合filter
	const IS_STRING_USER_NAME		= '|#s*user|';		// Util::checkUserName
	const IS_STRING_REAL_NAME		= '|#s*real|';		// Util::checkRealName
	const IS_STRING_IDCARD			= '|#s*idcard|';	// Util::checkIDCardNumber
	const IS_STRING_BANKCARD		= '|#s*bank|';		// Util::checkBankcard

	// array
	const IS_ARRAY					= '|#a|';
	const IS_ARRAY_SPECIFIED_LEN	= '|#a*len,$|';		// 数组长度有要求
	const IS_SUBSET					= '|#a*sub,$|';		// 数组中每一项都在指定的数组中存在
	const IS_RANGE_INT				= '|#a*r*i|';
	const IS_RANGE_INT_SIGNED		= '|#a*r*is|';
	const IS_RANGE_FLOAT			= '|#a*r*f|';
	const IS_RANGE_DATE				= '|#a*r*d|';
	const IS_RANGE_DATETIME			= '|#a*r*dt|';

	// callback
	const CALLBACK					= '|#c,$|';


	const POST_ACTION_REPLACE_TO_DB_FILTERS	= '|@filter|';
	const POST_ACTION_REPLACE_TO_DB_SORTS	= '|@sort|';


	private static $buffered_data = [];

	private static $debug_info = [];
}
