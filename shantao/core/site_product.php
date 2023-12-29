<?php

use Illuminate\Support\Facades\DB;

class SiteProduct
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
		$sql = 'SELECT product_id, site_id, price, site.name AS site_name
				FROM site_product
				INNER JOIN site ON site.id = site_product.site_id
				' . DBHelper::getFilterSQLs('WHERE', $filter, $bind) . '
				' . DBHelper::getSortSQLs($sort, $bind) . '
				' . DBHelper::getRangeSQL($range);
		return DB::select($sql, $bind);
	}


	/**
	 * 获取指定商品价格
	 * @param $product_id 商品id
	 * @return mixed
	 */
	public static function getInfoByProductId($product_id)
	{	
		$filter = [ Filter::makeDBFilter('site_product.site_product_id', $product_id, Filter::TYPE_EQUAL) ];
		return self::getList($filter, [ 0, 1])[0];
	}
	
	
	public static function getInfo($id)
	{
		$filter = [ Filter::makeDBFilter('id', $id, Filter::TYPE_EQUAL) ];
		return self::getList($filter, [ 0, 1])[0];
	}
	
	public static function getSitePriceForLock($site_id, $product_id)
	{
		$sql = 'SELECT selling_price FROM site_product WHERE site_id = ? AND product_id = ? LIMIT 1 FOR UPDATE';
		$bind = [ $site_id, $product_id ];
		return DB::select($sql, $bind)[0]['selling_price'];
	}

	public static function getSitePrice($site_id, $product_id)
	{
		$sql = 'SELECT price FROM site_product WHERE site_id = ? AND product_id = ? LIMIT 1';
		$bind = [ $site_id, $product_id ];
		return DB::select($sql, $bind)[0]['price'];
	}
}
