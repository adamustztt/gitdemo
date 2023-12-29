<?php
/**
 * Created by PhpStorm.
 * User: wzz
 * Date: 2019/12/25
 * Time: 15:28
 */

namespace App\Helper;


use App\Traits\WhereHelperTrait;

class WhereUtil
{
	use WhereHelperTrait;

	/**
	 * @var array
	 */
	private $where = [];
	/**
	 * @var array
	 */
	private $data;

	/**
	 * WhereUtil constructor.
	 * @param array $data
	 * @param $where
	 */
	public function __construct(array $data, &$where)
	{
		$this->data = $data;
		$this->where = &$where;
	}

	/**
	 * 构建过滤空字段的 Where date 语句数组
	 * 按照字段$startKey和$endKey从 data 提取数据并过滤空的
	 * @param $dataKey
	 * @param string $sqlField
	 * @return $this
	 * @author wzz
	 */
	public function applyDateFilter($dataKey, $sqlField = '')
	{
		if(empty($this->data[$dataKey])) {
			return $this;
		}
//		if (empty($endKey)) {
//			$endKey = $startKey . '_end';
//			$startKey = $startKey . '_start';
//		}
		empty($sqlField) && $sqlField = $dataKey;
		$startTime = $this->data[$dataKey][0];
		$endTime = $this->data[$dataKey][1];
		$endTime = strtotime($endTime);
		$endTime += 86400 - 1;
		$endTime = date('Y-m-d H:i:s', $endTime);
		$this->where[] = [$sqlField, 'between',[$startTime, $endTime]];
		return $this;
	}
	/**
	 * 构建过滤空字段的 Where date 语句数组
	 * 按照字段$startKey和$endKey从 data 提取数据并过滤空的
	 * @param $startKey
	 * @param string $endKey
	 * @param string $sqlField
	 * @return $this
	 * @author wzz
	 */
	/*
	public function applyDateFilter($startKey, $endKey = '', $sqlField = '')
	{
		if (empty($endKey)) {
			$endKey = $startKey . '_end';
			$startKey = $startKey . '_start';
		}
		$this->buildWhereDateFilter($this->where, $this->data, $startKey, $endKey, $sqlField);
		return $this;
	}
	*/

	/**
	 * 构建过滤空字段的Where语句数组
	 * 按照字段$dataKey从 data 提取数据并过滤空的
	 * @param $dataKey
	 * @param string $sqlField
	 * @param string $operator
	 * @param bool $filterZero
	 * @return $this
	 * @author wzz
	 */
	public function applyFilter($dataKey, $sqlField = '', $operator = '=', $filterZero = false)
	{
		if (strtoupper($operator) == 'LIKE' && isset($this->data[$dataKey]) && !empty($this->data[$dataKey])) {
			$this->data[$dataKey] = '%' . $this->data[$dataKey] . '%';
		}

		$this->buildWhereFilter($this->where, $this->data, $dataKey, $sqlField, $operator, $filterZero);
		return $this;
	}

	/**
	 * 追加到Where数组
	 * @param $where
	 * @return array
	 * @author wzz
	 */
	public function addToWhere(&$where)
	{
		$where = array_merge($where, $this->where);
		return $where;
	}

	/**
	 * 追加到Where数组 key=value 方式
	 * @param $where
	 * @return array
	 * @author wzz
	 */
	public function addToWhereByMap(&$where)
	{
		$where = array_merge($where, $this->formatWhereToMap($this->where));
		return $where;
	}

	public function formatWhereToMap($where)
	{
		$arr = [];
		foreach ($where as $index => $item) {
			list($sqlField, $operator, $val) = $item;
			$key = $sqlField;
			$operator != '=' && $key = $key . ' ' . $operator;
			$arr[$key] = $val;
		}
		return $arr;
	}


}
