<?php

use Illuminate\Support\Facades\DB;

class Channel
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
		$sql = 'SELECT channel.id AS channel_id, name, mobile
				FROM channel
				' . DBHelper::getFilterSQLs('WHERE', $filter, $bind) . '
				' . DBHelper::getSortSQLs($sort, $bind) . '
				' . DBHelper::getRangeSQL($range);
		return DB::select($sql, $bind);
	}


	/**
	 * 获取指定渠道
	 * @param $channel_name
	 * @return mixed
	 */
	public static function getInfoByName($channel_name)
	{	
		$filter = [ Filter::makeDBFilter('channel.name', $channel_name, Filter::TYPE_EQUAL) ];
		return self::getList($filter, [ 0, 1])[0];
	}
}
