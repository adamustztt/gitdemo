<?php

class Range
{
	/**
	 * @param array $range (from request) [ begin, end ]
	 * @return boolean
	 */
	public static function checkRangeInfo($range)
	{
		return (is_array($range) && count($range) === 2
				&& is_int($range[0]) && is_int($range[1])
				&& $range[0] >= 0 && $range[1] >= $range[0]);
	}

	/**
	 * @param array $range [ begin, end ]
	 * @return string sql
	 */
	public static function getRangeSQL($range)
	{
		return is_array($range) ? (' LIMIT ' . ($range[1] - $range[0]) . ' OFFSET ' . $range[0]) : '';
	}
}
