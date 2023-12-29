<?php
/**
 * 秀品街同步操作
 * 同步仓库、同步商品、同步物流
 */
use Illuminate\Support\Facades\DB;


class XiuPinJieSync
{
	/**
	 * 同步仓库数据到本地
	 * {
	 * "code":1,
	 * "msg":"返回成功",
	 * "time":"1593329404",
	 * "data":{
	 * "storelist":[
	 * {
	 * "id":107,
	 * "store_name":"圆通广州仓",
	 * "apiprice":"1.80",
	 * "typname":"圆通速递",
	 * "address":"广东省 广州市 花都区 百兴路【请在18:40分前下单，否则晚一天出物流】"
	 * }]
	 * }
	 * }
	 */
	public static function warehouseSync2Local()
	{
		$resp = self::sync(self::API_WAREHOUSE);
		if ($resp === null) {
			// @todo
			return false;
		}
		$resp = json_decode($resp, true);
		if ($resp['code'] !== 1) {
			return false;
		} 
		
		if (isset($resp['data']['storelist']) === false) {
			return false;
		}
		$warehouse_list = $resp['data']['storelist'];
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
				'name'			=> $warehouse['store_name'],
				'typename'		=> $warehouse['typname'],
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
	
	public static function sync($api, $data = [], $extra = null)
	{
		$auth_data = [
			'user'=>config('xiupinjie.user'),
			'pass'=>substr(md5(config('xiupinjie.password')),8,16),
		];
		$request_data = array_merge($auth_data, $data);
		$resp = HTTP::request(config('xiupinjie.url') . $api, $request_data);
		SimpleLog::info('请求地址:' . config('xiupinjie.url') . $api . '  请求数据：'
			. json_encode($request_data, JSON_UNESCAPED_UNICODE));
		SimpleLog::info('响应数据:' . $resp);
		return $resp;
	}
	
	/**
	 * 同步商品数据到本地
	 * 第一步: 先同步仓库
	 * 第二步：同步商品
	 */
	public static function productSync2Local()
	{
		self::warehouseSync2Local();
		
		$resp = self::sync(self::API_GOODS_LIST);
		$resp = json_decode($resp, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			return false;
		}
		
		if (isset($resp['data']['goodslist']) === false) {
			return false;
		}
		$new_products = [];
		$products = $resp['data']['goodslist'];
		
		foreach ($products as $product) {
			$filter = [
				Filter::makeDBFilter('product.channel_id', self::CHANNEL_ID, Filter::TYPE_EQUAL),
				Filter::makeDBFilter('product.ext_id', $product['id'], Filter::TYPE_EQUAL)
			];
			if(Product::getList($filter, [ 0, 1])[0] !== null) continue;
			
			//重量进行处理
			if(strpos($product['weight'],'g') !== false){
				$product['weight'] = intval(str_replace('g','',$product['weight']));
			}else{
				$product['weight'] = $product['weight'] * 1000; //KG转换为g
			}

			// 商品对应的仓库信息
			$warehouse_filter = [
				Filter::makeDBFilter('ext_id', $product['store_id'], Filter::TYPE_EQUAL),
				Filter::makeDBFilter('channel_id', self::CHANNEL_ID, Filter::TYPE_EQUAL)
			];
			$warehouse = WareHouse::getList($warehouse_filter,[ 0, 1])[0];
			
			$new_products[] = [
				'name' => $product['name'],
				'thumb' => $product['goods_image'],
				'cost_price' => $product['apiprice'] * 100,
				'warehouse_id' => $warehouse['warehouse_id'],
				'warehouse_name' => $product['store_name'],
				'weight' => $product['weight'],
				'ext_id' => $product['id'],
			];
		}
		DB::beginTransaction();
		foreach ($new_products as $product) {
			$pid = Product::addInternal($product['name'], $product['thumb'], $product['cost_price'],
				$product['weight'], self::CHANNEL_ID, $product['ext_id'], PRODUCT_STATUS_OFFLINE);
			ProductWarehouse::addInternal($pid, $product['warehouse_id']);
		}
		DB::commit();
		return true;
	}

	/**
	 * 实时仓库余额查询
	 * @return bool|mixed
	 */
	public static function queryBalance()
	{
		$resp = self::sync(self::API_BALANCE);
		$resp = json_decode($resp, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			return false;
		}
		if ($resp['code'] !== 1) {
			return false;
		}
		return $resp['data']['usermoney'];
	}

	/**
	 * 同步订单，每次同步100个订单
	 */
	public static function syncOrder()
	{
		$filter = [
			Filter::makeDBFilter('sync_status', USER_ORDER_SYNC_STATUS_PENDING, Filter::TYPE_EQUAL),
			Filter::makeDBFilter('status', USER_ORDER_STATUS_PAID, Filter::TYPE_EQUAL)
		];
		$order_list = OrderConsignee::getList($filter, [0, 100]);
		foreach ($order_list as $order) {
			$resp = self::syncSingleOrder($order);
			$resp = json_decode($resp, true);
			if ($resp['code'] !== 1) {
				continue;
			}
			if (isset($resp['data']['taskid']) === false) {
				continue;
			}
			OrderConsignee::syncSuccessInternal($order['id'], $resp['data']['taskid']);
		}
	}
	
	public static function syncSingleOrder($package)
	{
		$request_data = [
			'orderid'				=> $package['order_sn'],
			'send_order_no'			=> $package['order_sn'],
			'goodsid'				=> $package['product_ext_id'],
			'storesid'				=> $package['warehouse_ext_id'],
			'num'					=> $package['product_number'],
			'receiver_name'			=> $package['consignee'],
			'receiver_phone'		=> $package['mobile'],
			'receiver_province'		=> $package['province'],
			'receiver_city'			=> $package['city'],
			'receiver_district'		=> $package['district'],
			'receiver_address'		=> $package['address'],
			'sendname'				=> config('xiupinjie.param_name'),
			'sendphone'				=> config('xiupinjie.param_mobile'),
		];
		$resp_json = self::sync(self::API_ORDER, $request_data);
		OrderConsignee::changeAdditionalInternal($package['id'], $resp_json);
		$resp = json_decode($resp_json, true);
		if ($resp['code'] !== 1) {
			OrderConsignee::syncFailInternal($package['id']);
			return false;
		}
		if (isset($resp['data']['taskid']) === false) {
			return false;
		}
		DB::beginTransaction();
		OrderConsignee::syncSuccessInternal($package['id'], $resp['data']['taskid']);
		OrderConsignee::shipSuccessInternal($package['id']);
		DB::commit();
		return true;
	}
	
	public static function syncExpress2Local()
	{
		
	}
	
	public static function syncSingleExpress($package_id, $ext_order_sn)
	{
		$resp_json =  self::sync(self::API_EXPRESS, ['taskid' => intval($ext_order_sn) ]);
		OrderConsignee::changeAdditionalInternal($package_id, $resp_json);
		$resp = json_decode($resp_json, true);
		if ($resp['code'] !== 1) {
			return false;
		}
		$express_no = $resp['data']['express_no'];
		OrderConsignee::syncExpressInternal($package_id, $express_no);
		return true;
	}
	
	public static function cancelSingleOrder($package_id, $ext_order_sn)
	{
		$resp_json =  self::sync(self::API_CALL_OFF, ['ids' => intval($ext_order_sn) ]);
		OrderConsignee::changeAdditionalInternal($package_id, $resp_json);
		$resp = json_decode($resp_json, true);
		if ($resp['code'] !== 1) {
			OrderConsignee::syncFailInternal($package_id);
			return false;
		}
		DB::beginTransaction();
		OrderConsignee::syncSuccessInternal($package_id, $ext_order_sn);
		OrderConsignee::shipCancelInternal($package_id);
		DB::commit();
		return true;
	}
	
	const CHANNEL_ID = 1;								// 数据库中默认写死的ID号 不允许更改
	const API_WAREHOUSE = '/api/goods/storelist';		// 仓库列表
	const API_GOODS_LIST = '/api/goods/goodslist';		// 商品列表
	const API_ORDER = '/api/goods/order';				// 订单接口
	const API_BALANCE = '/api/goods/getmoney';			// 查询余额
	const API_EXPRESS = '/api/goods/get_express';		// 查询物流单号
	CONST API_CALL_OFF = '/api/goods/calloff';			// 取消订单
}
