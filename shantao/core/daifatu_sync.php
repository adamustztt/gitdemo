<?php

use Illuminate\Support\Facades\DB;

class DaiFaTuSync
{
	/**
	 * 刷新token，按照文档，token时效是2天
	 */
	public static function freshToken()
	{
		$token = self::login();
		if ($token !== false) {
			file_put_contents(self::TOKEN_PATH, $token);
		}
		return $token;
	}

	/**
	 * 1、如果文件不存在就直接创建
	 * 2、存在就读取文件修改时间
	 *    如果文件大于2天，那么刷新token，否则直接取来用
	 */
	public static function getToken()
	{
		if (file_exists(self::TOKEN_PATH) === false) {
			$token = self::login();
			self::storageToken($token);
			return $token;
		}
		$mtime = strtotime(filemtime(self::TOKEN_PATH));
		$now = time();
		if ($now - $mtime < self::FRESH_SECONDS) {
			return file_get_contents(self::TOKEN_PATH);
		}
		return file_get_contents(self::TOKEN_PATH);
	}

	/**
	 * 用户登录 返回token
	 */
	public static function login()
	{
		$resp = self::sync(self::API_USER_LOGIN, [
			'phone' => config('daifatu.username'),
			'password' => config('daifatu.password')
		]);
		if ($resp === null) {
			return false;
		}
		$resp = json_decode($resp, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			return false;
		}
		if ($resp['code'] !== 200) {
			return false;
		}
		return $resp['token'];
	}

	/**
	 * 存储token到文件中
	 * @param $token
	 */
	public static function storageToken($token)
	{
		file_put_contents(self::TOKEN_PATH, $token);
	}
	
	public static function warehouseSync2Local()
	{
		$resp = self::syncGoodsList();
		$resp = json_decode($resp, true);
		$warehouse_list = $resp['storage_list'];
		$new_warehouses = [];
		foreach ($warehouse_list as $warehouse) {
			$filter = [
				Filter::makeDBFilter('channel_id', self::CHANNEL_ID, Filter::TYPE_EQUAL),
				Filter::makeDBFilter('ext_id', $warehouse['id'], Filter::TYPE_EQUAL)
			];
			// 该仓库已添加过，自动忽略
			if(WareHouse::getList($filter, [ 0, 1])[0] !== null) {
				continue;
			}
			$new_warehouses[] = [
				'ext_id'		=> $warehouse['id'],
				'name'			=> $warehouse['name'],
				'typename'		=> '系统未返回，请手动修改',
				'cost_price'	=> $warehouse['apiprice'] * 100,
				'price'			=> $warehouse['apiprice'] * 100,
				'address'		=> $warehouse['address'],
				'channel_id'	=> self::CHANNEL_ID,
				'create_time'	=> date('Y-m-d H:m:s')
			];
		}
		// 开始插入
		DB::beginTransaction();
		foreach ($new_warehouses as $new_warehouse) {
			WareHouse::addInternal($new_warehouse['ext_id'], $new_warehouse['name'], $new_warehouse['price'],
				$new_warehouse['cost_price'], $new_warehouse['typename'], $new_warehouse['address'],
				self::CHANNEL_ID, WARE_HOUSE_STATUS_NORMAL);
		}
		DB::commit();
		return true;
	}
	
	public static function syncGoodsList()
	{
		return self::syncWithToken(self::API_GOODS_LIST);
	}
	
	public static function sync($api, $data = [])
	{
		return HTTP::request(config('daifatu.api_domain') . $api, $data);
	}
	
	public static function syncWithToken($api, $data = [])
	{
		$request_headers = [ 'Authorization:Bearer ' . self::getToken() ];
		return HTTP::request(config('daifatu.api_domain') . $api, $data, null, $request_headers);
	}
	
	const FRESH_SECONDS = 129600;			// token刷新秒数
	const API_USER_LOGIN = 'user/login';
	const API_GOODS_LIST = 'goods/search_goods';
	
	const TOKEN_PATH = '/tmp/daifatu.token';
	const CHANNEL_ID = 2;
}
