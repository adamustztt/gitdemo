<?php
/**
 * 云快递
 * http://51kb658.com
 */
use Illuminate\Support\Facades\DB;

class KB658Sync
{
	public static function syncTypeList()
	{
		$resp = self::sync(self::API_EMPTY_TYPE_LIST);
		$resp = json_decode($resp, true);
		if ($resp['issuccess'] === false) {
			return false;
		}
		return $resp['data'];
	}

	public static function warehouseSync2Local()
	{
		$warehouse_list = self::syncTypeList();
		$new_warehouses = [];
		foreach ($warehouse_list as $warehouse) {
			$filter = [
				Filter::makeDBFilter('channel_id', self::CHANNEL_ID, Filter::TYPE_EQUAL),
				Filter::makeDBFilter('ext_id', $warehouse['typeId'], Filter::TYPE_EQUAL)
			];
			// 不是礼品 过滤掉 不要
			if ($warehouse['packettype'] !== self::TYPE_GIFT) {
				continue;
			}
			
			// 该仓库已添加过，自动忽略
			if(WareHouse::getList($filter, [ 0, 1])[0] !== null) {
				continue;
			}
			$new_warehouses[] = [
				'ext_id'		=> $warehouse['typeId'],
				'name'			=> $warehouse['typeName'],
				'typename'		=> $warehouse['typeName'],
				'cost_price'	=> 140,
				'price'			=> 140,
				'address'		=> null,
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

	public static function productSync2Local()
	{
		
		$new_products = [];
		$products = self::syncTypeList();

		foreach ($products as $product) {
			// 不是礼品 过滤掉 不要
			if ($product['packettype'] !== self::TYPE_GIFT) {
				continue;
			}
			if ($product['price'] < 1) {
				continue;
			}
			$filter = [
				Filter::makeDBFilter('product.channel_id', self::CHANNEL_ID, Filter::TYPE_EQUAL),
				Filter::makeDBFilter('product.ext_id', $product['typeId'], Filter::TYPE_EQUAL)
			];
			if(Product::getList($filter, [ 0, 1])[0] !== null) continue;
			
			// 商品对应的仓库信息
			$warehouse_filter = [
				Filter::makeDBFilter('ext_id', $product['typeId'], Filter::TYPE_EQUAL),
				Filter::makeDBFilter('channel_id', self::CHANNEL_ID, Filter::TYPE_EQUAL)
			];
			$warehouse = WareHouse::getList($warehouse_filter,[ 0, 1])[0];

			$new_products[] = [
				'name' => $product['typeName'],
				'thumb' => null,
				'cost_price' => $product['price'] * 100 - 140,
				'warehouse_id' => $warehouse['warehouse_id'],
				'warehouse_name' => $warehouse['name'],
				'weight' => 0,
				'ext_id' => $product['typeId'],
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
	
	
	public static function sync($api, $data = [])
	{
		$auth_data = [
			'userName' => config('kb658.username'),
			'key' => config('kb658.password'),
		];
		$request_data = array_merge($data, $auth_data);
		return HTTP::request(config('kb658.api_domain') . $api, $request_data);
	}
	
	const API_EMPTY_TYPE_LIST = '/openApi/emptyTypeList';
	const API_ORDER_CREATE = '/openApi/orderCreate';
	const API_ORDER_CANCEL = '/openApi/orderCancel';
	const TYPE_GIFT = 2;
	const CHANNEL_ID = 3;
}
