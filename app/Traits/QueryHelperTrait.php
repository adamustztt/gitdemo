<?php
/**
 * Created by PhpStorm.
 * User: baixuan
 * Date: 2018/9/15
 * Time: 下午4:55
 */

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use \Illuminate\Database\Query\Builder as QueryBuilder;

trait QueryHelperTrait
{
    /**
     * 过滤的Where查询
     * @param Builder|QueryBuilder $query
     * @param array $whereParams 查询条件 [$column, $operator = null, $value = null, $boolean = 'and']
     * @param bool $filterZero 是否过滤0的值
     */
    public function whereFilter(&$query, array $whereParams, $filterZero = true)
    {
        $value = $this->getValueByParams($whereParams);
        if ($this->valueNotNull($value, $filterZero)) {
            $query->where(...$whereParams);
        }
    }

    /**
     * where时间范围 过滤空字段
     * @param Builder|QueryBuilder $query
     * @param string $sqlField
     * @param string $dateStartValue
     * @param string $dateEndFiledValue
     */
    public function whereDateScope(Builder &$query, $sqlField, $dateStartValue, $dateEndFiledValue)
    {
        $this->whereFilter($query, [$sqlField, '>=', $dateStartValue]);
        $this->whereFilter($query, [$sqlField, '<=', $dateEndFiledValue]);
    }

    /**
     * 根据参数数量获取值
     * @param $params
     * @return mixed
     */
    private function getValueByParams($params)
    {
        if (count($params) == 2) {
            $value = $params[1] ?? null;
        } elseif (count($params) == 3) {
            $value = $params[2] ?? null;
        } else {
            $value = null;
        }
        return $value;
    }

    /**
     * 判断值是非空的
     * @param $value
     * @param bool $filterZero 是否过滤0的值
     * @return bool
     */
    private function valueNotNull($value, $filterZero = true)
    {
        $value = is_string($value) ? trim($value) : $value;
        return !in_array($value, [null, 'null', '', 'undefined'], true) && (!$filterZero || $value !== 0);
    }

    /**
     * 超级Where 可以处理 = IN BETWEEN 等等
     * @param Builder|QueryBuilder $query
     * @param array $where [['a','=',1],['b','=',2]...]
     * @author wzz
     */
    public function superWhere(&$query, array $where)
    {
        foreach ($where as $index => $item) {
            if (!is_array($item)) {
                if (is_callable($item)) {
                    $query->where($item);
                    continue;
                } else {
                    $query->where($where);
                    break;
                }
            }
			
            $boolean = 'and';
            if (count($item) == 2) {
                list($column, $value) = $item;
                $operator = '=';
            } else if (count($item) == 4) {
                list($column, $operator, $value, $boolean) = $item;
            } else {
                list($column, $operator, $value) = $item;
            }
           
            switch (strtoupper($operator)) {
                case 'IN':
                    $query->whereIn($column, $value, $boolean);
                    break;
                case 'NOT IN':
                    $query->whereNotIn($column, $value, $boolean);
                    break;
                case 'BETWEEN':
                    $query->whereBetween($column, $value, $boolean);
                    break;
                case 'OR':
                    $query->orWhere($column, $value);
                    break;
                default:
                    $query->where($column, $operator, $value, $boolean);
                    break;
            }
        }
    }
}
