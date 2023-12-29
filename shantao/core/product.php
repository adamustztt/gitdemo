<?php

use Illuminate\Support\Facades\DB;

class Product
{
	public static function addInternal($name, $thumb, $cost_price, $weight, $channel_id, $ext_id, $status)
	{
		$sql ='INSERT INTO product(name, thumb, cost_price, weight, channel_id, ext_id, status)
				VALUES(?, ?, ?, ?, ?, ?, ?)';
		$bind = [ $name, $thumb, $cost_price, $weight, $channel_id, $ext_id, $status ];
		DB::insert($sql, $bind);
		return DB::getPdo()->lastInsertId();
	}
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
		$sql = 'SELECT product.id AS product_id, product.alias_name as name, thumb, product.cost_price,weight, product.status,
					isort,product.channel_id, product.ext_id, product.create_time, product.othumb, product.sales,
					site_product.selling_price AS site_price,site_product.profit,site_product.price,
					warehouse.id AS warehouse_id, warehouse.name AS warehouse_name, warehouse.price AS shipping_fee,
					warehouse.alias_name AS warehouse_alias_name,
					product.stock,product.user_source
				FROM product
				INNER JOIN warehouse ON product.warehouse_id = warehouse.id
				LEFT JOIN site_product ON product.id = site_product.product_id
				' . DBHelper::getFilterSQLs('WHERE', $filter, $bind) . '
				' . DBHelper::getSortSQLs($sort, $bind) . '
				' . DBHelper::getRangeSQL($range);
		Log::info($sql);
		return DB::select($sql, $bind);
	}
	/*public static function getList($filter = null, $range = null, $sort = null)
	{
		$bind = [];
		$sql = 'SELECT product.id AS product_id, product.name, thumb, product.cost_price,weight, product.status,
					isort,product.channel_id, product.ext_id, product.create_time, product.othumb, product.sales,
					site_product.price AS site_price,
					warehouse.id AS warehouse_id, warehouse.name AS warehouse_name, warehouse.price AS shipping_fee,
					warehouse.alias_name AS warehouse_alias_name,
					product_stock.stock
				FROM product
				INNER JOIN product_warehouse ON product.id = product_warehouse.product_id
				INNER JOIN warehouse ON product_warehouse.warehouse_id = warehouse.id
				LEFT JOIN site_product ON product.id = site_product.product_id
				LEFT JOIN product_stock ON product.id = product_stock.product_id
				' . DBHelper::getFilterSQLs('WHERE', $filter, $bind) . '
				' . DBHelper::getSortSQLs($sort, $bind) . '
				' . DBHelper::getRangeSQL($range);
		return DB::select($sql, $bind);
	}*/

	public static function getCount($filter = null)
	{
		$bind = [];
		$sql = 'SELECT COUNT(*) AS total
				FROM product
				INNER JOIN product_warehouse ON product.id = product_warehouse.product_id
				INNER JOIN warehouse ON product_warehouse.warehouse_id = warehouse.id
				LEFT JOIN site_product ON product.id = site_product.product_id
				LEFT JOIN product_stock ON product.id = product_stock.product_id
				' . DBHelper::getFilterSQLs('WHERE', $filter, $bind);
		return DB::select($sql, $bind)[0]['total'] ?? 0;
	}
	

	public static function getInfo($product_id, $site_id)
	{
		$sql = 'SELECT product.*,product.alias_name as name,product.alias_name as product_alias_name, product_additional.additional AS descrption,site_product.selling_price AS product_price,
					warehouse.alias_name,warehouse.alias_typename,site_product.price,site_product.profit
				FROM product
				LEFT JOIN product_additional ON product.id = product_additional.product_id
				LEFT JOIN site_product ON product.id = site_product.product_id
				LEFT JOIN warehouse ON product.warehouse_id = warehouse.id
				WHERE product.id = ? AND site_product.site_id = ?
				LIMIT 1';
		$bind = [ $product_id, $site_id ];
		return DB::select($sql, $bind)[0];
	}
	
	public static function getWarehouseList($product_id)
	{
		$sql = 'SELECT warehouse.id AS warehouse_id, warehouse.alias_name, warehouse.alias_typename
				FROM warehouse
				INNER JOIN product ON warehouse.id = product.warehouse_id
				WHERE product.id = ?
				';
		$bind = [ $product_id ];
		return DB::select($sql, $bind);
	}
}
