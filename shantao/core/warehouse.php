<?php

use Illuminate\Support\Facades\DB;

class WareHouse
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
		$sql = 'SELECT warehouse.id AS warehouse_id, name, alias_name, price, cost_price, typename, alias_typename,
					address,channel_id,status, create_time, ext_id,ext_express_id
				FROM warehouse
				' . DBHelper::getFilterSQLs('WHERE', $filter, $bind) . '
				' . DBHelper::getSortSQLs($sort, $bind) . '
				' . DBHelper::getRangeSQL($range);
		return DB::select($sql, $bind);
	}

	public static function getInfo($warehouse_id)
	{
		$filter = [ Filter::makeDBFilter('warehouse.id', $warehouse_id, Filter::TYPE_EQUAL) ];
		return self::getList($filter, [ 0, 1])[0];
	}

	public static function addInternal($ext_id, $name, $price, $cost_price, $typename, $address, $channel_id, $status)
	{
		$sql = 'INSERT INTO warehouse (ext_id, name, price, cost_price, typename, address,
					channel_id, status)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
		$bind = [ $ext_id, $name, $price, $cost_price, $typename, $address, $channel_id, $status ];
		return DB::insert($sql, $bind);
	}
}
