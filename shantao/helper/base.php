<?php

use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
/**
 * 用于基本的输入和输出
 */
class Base
{
	/**
	 * 从post数据中读取输入的json
	 *
	 * @return array
	 */
	public static function getRequestJson()
	{
		$post = $_POST['data'] ?? file_get_contents('php://input');
		if (!is_string($post)) {
//			self::dieWithError(ERROR_INVALID_REQUEST);
			CommonUtil::throwException(ErrorEnum::NOT_JSON_OPERATION);
		}
		$json = json_decode($post, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
//			self::dieWithError(ERROR_INVALID_REQUEST);
			CommonUtil::throwException(ErrorEnum::NOT_JSON_OPERATION);
		}

		return $json;
	}


	/**
	 * 返回出错信息
	 *
	 * @param integer $err    ERROR_*
	 * @param mixed   $errmsg 额外的错误信息
	 * @param boolean $continue_exec =true表示脚本不终止，继续执行，只是不向客户端输出内容了
	 *
	 * @return void
	 */
	public static function dieWithError($err, $errmsg = null, $continue_exec = false)
	{
		$arr = array_merge([ 'status' => $err ],
			$errmsg !== null ? [ 'err' => $errmsg ] : [],
			isset($line) ? [ 'magic' => $line ] : []
		);
		$json = json_encode($arr);
		header('Content-Type: application/json; charset=UTF-8');
		$sig = static::generateSignatureForDie($json);
		if ($sig !== null) {
			header('X-Signature: ' . $sig[0] . '=' . $sig[1]);
		}
		echo $json;
		if ($continue_exec) {
			self::dieDelay();
		} else {
			self::dieDirectly();
		}
	}

	/**
	 * 返回正常的结果
	 *
	 * @param array|null $obj           返回值
	 * @param boolean    $continue_exec =true表示脚本不终止，继续执行，只是不向客户端输出内容了
	 *
	 * @return void
	 */
	public static function dieWithResponse($obj = null, $continue_exec = false)
	{
		$json = json_encode(array_merge([ 'status' => ERROR_SUCCESS ], ($obj !== null) ? [ 'data' => $obj ] : []));
		header('Content-Type: application/json; charset=UTF-8');
		$sig = static::generateSignatureForDie($json);
		if ($sig !== null) {
			header('X-Signature: ' . $sig[0] . '=' . $sig[1]);
		}
		echo $json;
		if ($continue_exec) {
			self::dieDelay();
		} else {
			self::dieDirectly();
		}
	}

	/**
	 * 输出信息，并返回http 200
	 *
	 * @param string $msg
	 */
	public static function dieDirectly($msg = null)
	{
		die($msg ?? '');
	}

	/**
	 * 输出信息，并返回http cdoe
	 *
	 * @param integer $code
	 * @param string $msg
	 */
	public static function dieWithHTTPCode($code, $msg = null)
	{
		http_response_code($code);
		self::dieDirectly($msg ?? '');
	}

	/**
	 * 延迟die
	 */
	public static function dieDelay()
	{
		fastcgi_finish_request();
	}


	/**
	 * 执行检查，如果出错就以标准格式die掉
	 *
	 * @param array $rules
	 * @param array $data
	 * @param boolean $debug
	 */
	public static function checkAndDie($rules, &$data, $debug = false)
	{
		$ret = Param::check($rules, $data);
		if ($ret !== true) {
			if ($debug) {
				Param::debug();
			}
			self::dieWithError($ret > 0 ? $ret : ERROR_INVALID_REQUEST);
		}
	}

	/**
	 * 对数据做签名
	 *
	 * @param string $data
	 * @return null|array [ '<method>', '<data>' ]
	 */
	public static function generateSignatureForRequest($data) { return null; }
	public static function generateSignatureForDie($data) { return null; }

	/**
	 * 对数据做验证
	 *
	 * @param string $method
	 * @param string $sig
	 * @param string $data
	 * @return bool
	 */
	public static function verifySignatureForIncomingRequest($method, $sig, $data) { return true; }
	public static function verifySignatureForResponse($method, $sig, $data) { return true; }
}
