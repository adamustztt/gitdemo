<?php

class Filter
{
	/**
	 * @param string $key
	 * @param string/int/array $value
	 * @param int $type TYPE_*
	 * @param boolean $not
	 * @return Filter
	 */
	public static function makeDBFilter($key, $value, $type, $not = false)
	{
		return new Filter($key, $value, $type, $not);
	}

	/**
	 * @param Filter[] $filters
	 * @param array $bind
	 * @param array $aliases [
	 *          'column name' => 'replace column name in where clause'
	 *        ], ... ]
	 * @return string sql
	 */
	public static function getFilterSQLs($filters, &$bind, $aliases = null)
	{
		$sql_array = [];
		foreach ($filters as $f) {
			$sql_array[] = $f->getFilterSQL($bind, is_array($aliases) ? $aliases[$f->key] : null);
		}
		return implode(' && ', $sql_array);
	}


	private function __construct($key, $value, $type, $not)
	{
		$this->key = $key;
		$this->value = $value;
		$this->type = $type;
		$this->not = $not;
	}


	/**
	 * @param array $bind
	 * @param string $alias replace column name in where clause
	 * @return string sql
	 */
	public function getFilterSQL(&$bind, $alias = null)
	{
		$sql = is_string($alias) ? $alias : $this->key;

		switch ($this->type) {
			case self::TYPE_RANGE_INT:
			case self::TYPE_RANGE_SIGNED_INT:
			case self::TYPE_RANGE_FLOAT:
			case self::TYPE_RANGE_DATETIME:
				$sql .= ($this->not ? ' NOT ' : '') . ' BETWEEN ? AND ?';
				array_push($bind, $this->value[0], $this->value[1]);
				break;
			case self::TYPE_SET:
				if (is_array($this->value) && count($this->value) > 0) {
					$sql .= ($this->not ? ' NOT ' : '')
						. ' IN (' . implode(',', array_fill(0, count($this->value), '?')) . ')';
					$bind = array_merge($bind, $this->value);
				} else {
					$sql = ($this->not ? ' TRUE ' : ' FALSE ');
				}
				break;
			case self::TYPE_EQUAL:
				if ($this->value === null) {
					$sql .= ($this->not ? ' IS NOT NULL ' : ' IS NULL ');
				} else {
					$sql .= ($this->not ? ' != ? ' : ' = ? ');
					$bind[] = $this->value;
				}
				break;
			case self::TYPE_LARGER:
				$sql .= ($this->not ? ' <= ? ' : ' > ? ');
				$bind[] = $this->value;
				break;
			case self::TYPE_EQUAL_LARGER:
				$sql .= ($this->not ? ' < ? ' : ' >= ? ');
				$bind[] = $this->value;
				break;
			case self::TYPE_SQL:
				assert(is_array($this->value));
				assert($this->not === false);
				$bind = array_merge($bind, $this->value);
				break;
			default:
				die('INVALID USAGE: getFilterSQL');
		}
		return '(' . $sql . ')';
	}


	const TYPE_RANGE_INT		= 1;	// int range类型的db filter
	const TYPE_RANGE_SIGNED_INT	= 2;	// int range类型的db filter（允许为负数）
	const TYPE_RANGE_FLOAT		= 3;	// float range类型的db filter
	const TYPE_RANGE_DATETIME	= 4;	// 日期时间类型的db filter
	const TYPE_SET				= 5;	// 集合类型的db filter
	const TYPE_EQUAL			= 6;	// ==单一数值的db filter
	const TYPE_LARGER			= 7;	// >单一数值的db filter
	const TYPE_EQUAL_LARGER		= 8;	// >=单一数值的db filter
	const TYPE_SQL				= 10;	// key直接是sql，value为bind参数


	public $key;
	public $value;
	public $type;
	public $not = false;
}
