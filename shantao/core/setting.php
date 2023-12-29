<?php
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class Setting
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
		$sql = 'SELECT *
				FROM setting
				' . DBHelper::getFilterSQLs('WHERE', $filter, $bind) . '
				' . DBHelper::getSortSQLs($sort, $bind) . '
				' . DBHelper::getRangeSQL($range);
		return DB::select($sql, $bind);
	}


	public static function getInfo($id)
	{
		$filter = [ Filter::makeDBFilter('id', $id, Filter::TYPE_EQUAL) ];
		return self::getList($filter, [ 0, 1])[0];
	}
}
