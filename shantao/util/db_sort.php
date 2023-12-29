<?php

class Sort
{
	public static function makeSort($field, $reverse, $additional_value = null)
	{
		return new Sort($field, $reverse, $additional_value);
	}

	/**
	 * @param array $sort_data 输入数据 [
	 *          [
	 *            'field' => '<db column name>',
	 *            'reverse' => true / false,  // [optional] (ASC / DESC)
	 *            'custom_order' => <array>,  // [optional] 表示自定义权重排序
	 *          ], ...
	 *        ]
	 * @param array $rules DBSort检查规则 [
	 *          '<sort key 1>', '<sort key 2>', ...
	 *          '<sort key>' => '<replaced sort key>', ...
	 *          '<sort key>' => [ <custom order>, ... ], ...
	 *        ]
	 * @return boolean
	 */
	public static function checkSorts($sort_data, $rules)
	{
		if (!is_array($sort_data)) {
			return false;
		}

		// 将valid_keys做成map
		$valid_key_map = [];
		foreach ($rules as $k => $v) {
			$valid_key_map[is_int($k) ? $v : $k] = $v;
		}

		$dup_obj = [];
		foreach ($sort_data as $s) {
			if (!is_array($s)) {
				return false;
			}
			if (isset($s['reverse']) && !is_bool($s['reverse'])) {
				return false;
			}
			if (!is_string($s['field']) || isset($dup_obj[$s['field']])) {
				return false;
			}

			$sort_key = $valid_key_map[$s['field']];
			if (!isset($sort_key)) {
				return false;
			}
			// 只有对允许自定义排序的字段，才能够设置custom order
			if (isset($s['custom_order'])) {
				if (!is_array($sort_key)) {
					return false;
				}
				$sort_keys = $sort_key['custom_order'] ?? $sort_key;
				if (!Util::checkSet($s['custom_order'], $sort_keys)) {
					return false;
				}
			}
			$dup_obj[$s['field']] = 1;
		}
		return true;
	}

	/**
	 * @param Sort[] $sorts array of Sort
	 * @param array $bind
	 * @return string sql
	 */
	public static function getSortSQLs($sorts, &$bind)
	{
		$sql_array = [];
		foreach ($sorts as $s)
			$sql_array[] = $s->getSortSQL($bind);
		return implode(', ', $sql_array);
	}


	private function __construct($field, $reverse, $additional_value)
	{
		$this->field = $field;
		$this->reverse = $reverse;
		$this->additional_value = $additional_value;
	}

	/**
	 * @param array $bind
	 * @return string sql
	 */
	public function getSortSQL(&$bind)
	{
		if (is_array($this->additional_value) && count($this->additional_value) > 0) {
			$sql = ' FIELD(' . $this->field . str_repeat(',?', count($this->additional_value)) . ')';
			$bind = array_merge($bind, $this->additional_value);
		} else {
			$sql = $this->field;
			if (!is_null($this->additional_value))
				$bind[] = $this->additional_value;
		}
		$sql .= ' ' . ($this->reverse ? 'DESC' : 'ASC');
		return $sql;
	}


	public $field;
	public $reverse;
	public $additional_value;
}
