<?php

class DBHelper
{
	/**
	 * @param string $prefix
	 * @param array $filters
	 * @param array $bind
	 * @return string
	 */
	public static function getFilterSQLs($prefix, $filters, &$bind)
	{
		return empty($filters) ? '' : ' ' . $prefix . ' ' . Filter::getFilterSQLs($filters, $bind);
	}

	/**
	 * @param array $range
	 * @return string
	 */
	public static function getRangeSQL($range)
	{
		return $range === null ? '' : ' ' . Range::getRangeSQL($range);
	}

	/**
	 * @param array $sorts
	 * @param array $bind
	 * @return string
	 */
	public static function getSortSQLs($sorts, &$bind)
	{
		return empty($sorts) ? '' : ' ORDER BY ' . Sort::getSortSQLs($sorts, $bind);
	}

	/**
	 * 获取数据库行
	 *
	 * @param string $table
	 * @param array $cols
	 * @param array $filter
	 * @param array $range
	 * @param array $sort
	 * @return array
	 */
	public static function getRows($table, $cols, $filter = null, $range = null, $sort = null)
	{
		$bind = [];
		$sql = 'SELECT ' . Selector::getSelectorSQL($cols, $bind) . '
				FROM ' . $table . ' 
				' . DBHelper::getFilterSQLs(' WHERE ', $filter, $bind) . '
				' . DBHelper::getSortSQLs($sort, $bind) . '
				' . DBHelper::getRangeSQL($range);

		return DB::select($sql, $bind);
	}
}
