<?php

use App\Helper\CommonUtil;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use App\Enums\ErrorEnum;
use App\Models\User as UserModel;

class User
{

	/**
	 * 获取列表
	 * @param array $filter
	 * @param array $range
	 * @param array $sort
	 * @return array
	 */
	public static function getList($filter = null, $range = null, $sort = null)
	{
		$bind = [];
		$sql = 'SELECT user.id, username, balance, user.mobile, app_id, app_secret, user.status,
					user.create_time, password, user.access_token, user.is_api,user.notify_url,
					site.id AS site_id
				FROM user
				LEFT JOIN site ON user.id = site.user_id
				' . DBHelper::getFilterSQLs('WHERE', $filter, $bind) . '
				' . DBHelper::getSortSQLs($sort, $bind) . '
				' . DBHelper::getRangeSQL($range);
		return DB::select($sql, $bind);
	}


	public static function getInfo($uid)
	{
		$filter = [ Filter::makeDBFilter('user.id', $uid, Filter::TYPE_EQUAL) ];
		return self::getList($filter, [ 0, 1])[0];
	}
	
	public static function getInfoByUserName(string $username)
	{
		$filter = [ Filter::makeDBFilter('username', $username, Filter::TYPE_EQUAL) ];
		return self::getList($filter, [ 0, 1 ])[0];
	}


	/**
	 * 获取余额
	 * @param $uid
	 * @return mixed
	 */
	public static function getBalance($uid)
	{
		return self::getInfo($uid)['balance'];
	}
	
	public static function getBalanceForLock($uid)
	{
		$sql = 'SELECT balance FROM user WHERE id = ? LIMIT 1 FOR UPDATE';
		$bind = [ $uid ];
		return DB::select($sql, $bind)[0]['balance'];
	}

	/**
	 * 注册
	 * @param $username
	 * @param $mobile
	 * @param $password
	 * @param $site_id
	 * @return bool
	 */
	public static function registerInternal($invite_code, $site_id, $username, $mobile, $password,$parent = 0, 
											$parent_path = 0, $app_id = null, $app_secret = null)
	{
		$sql = 'INSERT INTO user (username, mobile, password, status, app_id, app_secret,site_id,invite_code,parent,
                parent_path,create_time)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)';
		$bind = [ $username, $mobile, password_hash($password, PASSWORD_DEFAULT), USER_STATUS_NORMAL, $app_id,
			$app_secret, $site_id, $invite_code, $parent, $parent_path,date("Y-m-d H:i:s",time())];
		try {
			$ret = DB::insert($sql, $bind);
		} catch (QueryException $queryException) {
			$ret = false;
		}
		return $ret;
	}



	/**
	 * 登录
	 * @param string $username
	 * @param string $password
	 * @return int
	 */
	public static function login(string $username, string $password)
	{
		$user_info = self::getInfoByUserName($username);
		if ($user_info === null) {
			CommonUtil::throwException(ErrorEnum::USER_NOT_EXISTS);
//			return ERROR_USER_NOT_EXISTS;
		}
		if ($user_info['status'] === USER_STATUS_FROZEN) {
			CommonUtil::throwException(ErrorEnum::USER_STATUS_OlREADY_FROZEN);
//			return ERROR_USER_STATUS_FROZEN;
		}
		
		$ret = password_verify($password, $user_info['password']);
		if ($ret === false) {
			CommonUtil::throwException(ErrorEnum::PASSWORD_ERROR);
//			return ERROR_INVALID_PASSWORD;
		}
		return true;
	}

	/**
	 * 通过APP_ID, APP_SECRET 登录
	 * 登录成功，返回用户id，否则返回错误码
	 */
	public static function apiUserLogin($app_id, $app_secret) {
		$filter = [
			Filter::makeDBFilter('app_id', $app_id, Filter::TYPE_EQUAL),
			Filter::makeDBFilter('app_secret', $app_secret, Filter::TYPE_EQUAL),
		];
		$user_info = self::getList($filter,[0,1])[0];
		if($user_info === null) {
			return ErrorEnum::USER_NOT_EXISTS;
//			return ERROR_USER_NOT_EXISTS;
		}
		if ($user_info['status'] === USER_STATUS_FROZEN) {
			return ErrorEnum::USER_STATUS_OlREADY_FROZEN;
//			return ERROR_USER_STATUS_FROZEN;
		}
		// 找到之前的token 更新数据 并且从redis中删除
		$token = TokenUtil::encrypt(TokenUtil::createToken($user_info['id']));
		$ret = self::apiRefreshAccessToken($user_info['id'], $user_info['site_id'], $user_info['access_token'], $token);
		if ($ret === false) {
			return ErrorEnum::INTERNAL_ERROR;
//			return ERROR_INTERNAL;
		}
		return $token;
	}

	/**
	 * 先从redis里面删除，删除成功后才更新数据库
	 * @param $uid
	 * @param $token
	 */
	public static function apiRefreshAccessToken($uid, $site_id, $old_token, $new_token)
	{
		app('redis')->del($old_token);
		app('redis')->set($new_token, json_encode([ 'user_id' => $uid, 'site_id' => $site_id ], JSON_UNESCAPED_UNICODE));
		app('redis')->expire($new_token, 3600);
		$ret = self::apiRefreshAccessTokenInternal($uid, $new_token);
		return $ret === 1;
	}

	public static function apiRefreshAccessTokenInternal($uid, $token)
	{
		$sql = 'UPDATE user SET access_token = ? WHERE id = ? LIMIT 1';
		$bind = [ $token , $uid ];
		return DB::update($sql, $bind);
	}
	
	public static function getUserInfoByAccessToken($access_token)
	{
		$user_info = json_decode(app('redis')->get($access_token), true);
		return $user_info;
	}

	 /**
	 * 发送验证码
	  * @param int $mobile
	 * @return int
	 */
	public static function sendCode($mobile)
	{
		$code = rand(10000, 99999);
		app('redis')->set("sms_code:".$mobile,$code);
		SMSQiDianYun::send($mobile, $code);
		return $code;
	}

	/**
	 * 验证验证码
	 * @param $code
	 * @return bool
	 */
	public static function verifyCode($code,$mobile)
	{
//		session_start();
//		if ($code !== $_SESSION['code']) {
//			return false;
//		}
//		return true;
		$oldCode = app('redis')->get("sms_code:".$mobile);
		if($code && $code==$oldCode ) {
			return true;
		}
		return false;
	}

	/**
	 * token => user
	 * @param $user_token
	 * @return mixed
	 */
	public static function tokenWrapInfo($user_token)
	{
//		return json_decode(app('redis')->get($user_token), true);
		return json_decode(app('redis')->get('user_info:'.$user_token), true);
	}

	/**
	 * 修改密码
	 */
	public static function changePwdInternal()
	{
		
	}

	/**
	 * 余额充值
	 * @param int $uid
	 * @param int $amount
	 * @return int
	 */
	public static function balanceChargeInternal(int $uid, int $amount)
	{
		$sql = 'UPDATE user SET balance = balance + ? WHERE id = ? LIMIT 1';
		$bind = [ $amount, $uid ];
		return DB::update($sql, $bind);
	}
	
	public static function chargeBalance($uid, $paid_type, $amount, $trade_sn, $paid_time)
	{
		DB::beginTransaction();
		self::balanceChargeInternal($uid, $amount);
		RechargeRecord::addInternal($uid, $paid_type, $trade_sn, $amount);
		
		DB::commit();
	}

	/**
	 * 使用余额买商品
	 * @param $uid
	 * @param int $amount
	 */
	public static function balanceBuyInternal($uid, int $amount, $order_id)
	{
		$balance = self::getBalanceForLock($uid);
		$balance = $balance - $amount;
		if ($balance < 0){
			CommonUtil::throwException(ErrorEnum::ERROR_EXT_BALANCE_NOT_ENOUGH);
		}
		$res = UserModel::updateById($uid, ['balance' => $balance]);
		if (!$res) {
			return false;
		}
		$ret = UserBalanceLog::addInternal($uid, $balance, USER_BALANCE_TYPE_PAY, $amount,
				$order_id, '下单扣款，订单号：' . $order_id);
		if ($ret === false) {
			return false;
		}
		return true;
	}

	public static function refundInternal($uid, int $amount, $package_id)
	{
		
		$sql = 'UPDATE user SET balance = balance + ? WHERE id = ? LIMIT 1';
		$bind = [ $amount, $uid ];
		$ret = DB::update($sql, $bind);
		if ($ret !== 1) {
			return false;
		}

		$ret = UserBalanceLog::addInternal($uid, self::getBalance($uid),
			USER_BALANCE_TYPE_REFUND, $amount, $package_id,
			'退款-取消订单-包裹单号：' . $package_id);
		if ($ret === false) {
			return false;
		}
		return true;
	}
}
