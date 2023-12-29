<?php
/**
 * Created by PhpStorm.
 * User: wzz
 * Date: 2019/12/11
 * Time: 15:08
 */

namespace App\Traits;


use App\Helper\CommonUtil;

trait WhereHelperTrait
{
	/**
	 * 构建过滤空字段的Where语句数组
	 * @param array $where
	 * @param array $data
	 * @param string $dataKey
	 * @param string $sqlField
	 * @param string $operator
	 * @param bool $filterZero
	 * @author wzz
	 * @example [$where, $data, 'user_id']
	 */
	public function buildWhereFilter(array &$where, array $data, string $dataKey, string $sqlField = '',
									 $operator = '=', $filterZero = false)
	{

		if (!isset($data[$dataKey])) {
			return;
		}

		$val = $data[$dataKey];
		if (!CommonUtil::valueNull($val, $filterZero)) {
			empty($sqlField) && $sqlField = $dataKey;
			$where [] = [$sqlField, $operator, $val];
		}
		return;
	}

	/**
	 * 构建过滤空字段的 Where date 语句数组
	 * @param array $where
	 * @param array $data
	 * @param string $startKey
	 * @param string $endKey
	 * @param string $sqlField
	 * @author wzz
	 * @example [$where, $data, 'create_at_start', 'create_at_end'] 自动设置 sqlField = create_at
	 * @example [$where, $data, 'create_start', 'create_end', 'create_at']
	 */
	public function buildWhereDateFilter(array &$where, array $data, string $startKey, string $endKey, string $sqlField = '')
	{
		if (empty($sqlField)) {
			$index = strrpos($startKey, '_');
			$sqlField = substr($startKey, 0, $index);
		}
		$startVal = $data[$startKey];
		$endVal = strtotime($data[$endKey]);
		$endVal += 86400 - 1;
		$endVal = date('Y-m-d H:i:s', $endVal);
		$where[] = [$sqlField, 'between', [$startVal, $endVal]];
//        if (!empty($data[$startKey]) && !empty($data[$endKey])) {
//            $val = strtotime($data[$startKey]);
//            $where[] = [$sqlField, '>=', $val];
//        }
//
//        if (!empty($data[$endKey])) {
//            $val = strtotime($data[$endKey]);
//            $val += 86400 - 1;
//            $where[] = [$sqlField, '<=', $val];
//        }
		return;
	}


}
