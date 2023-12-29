<?php
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class RechargeRecord
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
		$sql = 'SELECT recharge_record.*
				FROM recharge_record
				' . DBHelper::getFilterSQLs('WHERE', $filter, $bind) . '
				' . DBHelper::getSortSQLs($sort, $bind) . '
				' . DBHelper::getRangeSQL($range);
		return DB::select($sql, $bind);
	}
	
	public static function addInternal($user_id, $pay_type, $trade_sn, $amount, $additional = null)
	{
		$sql = 'INSERT INTO recharge_record (user_id, recharge_sn, pay_type, trade_sn, amount, additional)
				VALUES (?, ?, ?, ?, ?, ?)';
		$bind = [ $user_id, 'R'. time(), $pay_type, $trade_sn, $amount, $additional ];
		return DB::insert($sql, $bind);
	}
}
