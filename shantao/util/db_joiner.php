<?php

class Joiner
{
	/**
	 * @param string|array $join
	 * @return string sql
	 */
	public static function getJoinSQL($join)
	{
		if (is_string($join)) {
			return $join;
		}
		return implode(' ', $join);
	}
}
