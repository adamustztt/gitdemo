<?php

class Selector
{
	/**
	 * @param array $fields array of column name / value AS key
	 * @param array $bind
	 * @return string sql
	 */
	public static function getSelectorSQL($fields, &$bind = null)
	{
		$field_sql_array = [];
		foreach ($fields as $f => $v) {
			if (is_array($v)) {		// with bind info
				for ($i = 1; $i < count($v); $i++)
					$bind[] = $v[$i];
				$v = $v[0];
			}
			if (is_string($f))	// $v AS $f
				$field_sql_array[] = $v . ' AS ' . $f;
			else		// number => $v
				$field_sql_array[] = $v;
		}
		return implode(', ', $field_sql_array);
	}

	/**
	 * @param array $fields array of column name / value AS key
	 * @return string sql
	 */
	public static function getSelectorColumns($fields)
	{
		$field_sql_array = [];
		foreach ($fields as $f => $v) {
			if (is_string($f))	// $v AS $f
				$field_sql_array[] = $f;
			else		// number => $v
				$field_sql_array[] = $v;
		}
		return implode(', ', $field_sql_array);
	}
}
