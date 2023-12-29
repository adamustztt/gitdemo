<?php
/**
 * Created by PhpStorm.
 * User: wzz
 * Date: 2020/9/9
 * Time: 11:07
 */

namespace App\Models;


use App\Traits\QueryHelperTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 */
class BaseModel extends Model
{
//	use SoftDeletes;
	use QueryHelperTrait;

	const CREATED_AT = 'create_time';
	const UPDATED_AT = 'update_time';

	/**
	 * The number of models to return for pagination.
	 * 默认分页数量
	 * @var int
	 */
	protected $perPage = 15;

	/**
	 * 默认排序
	 *
	 * @var array
	 */
	public $order = ['id', 'desc'];

	/**
	 * 更新通过ID
	 * @param $id
	 * @param $data
	 * @return int
	 * @author wzz
	 */
	public static function updateById($id, $data)
	{
		return static::query()->where('id', $id)->update($data);
	}

	/**
	 * 删除通过ID
	 * @param $id
	 * @return int
	 * @author wzz
	 */
	public static function deleteById($id)
	{
		return static::query()->where('id', $id)->delete();
	}

	/**
	 * 通过ID获取一条数据
	 * @param $id
	 * @param string[] $columns
	 * @return Builder|Model|object|null
	 * @author wzz
	 */
	public static function getById($id, $columns = ['*'])
	{
		return static::query()->where('id', $id)->first($columns);
	}

	public static function getByIdLockForUpdate($id, $columns = ['*'])
	{
		return static::query()->where('id', $id)->lockForUpdate()->first($columns);
	}
	/**
	 * 创建一条数据
	 * @param array $attributes
	 * @return Builder|Model|object|null
	 * @author wzz
	 */
	public static function create(array $attributes = [])
	{
		return static::query()->create($attributes);
	}

	/**
	 * 获取带分页的列表
	 * @param array $columns
	 * @param array $where
	 * @param int $page
	 * @param null $limit null取默认值
	 * @param array $relations 模型关联
	 * @param array $order 空取默认值
	 * @param array $group
	 * @return LengthAwarePaginator
	 * @author wzz
	 */
	public static function listPage(array $columns, array $where, $page = 1, $limit = null, $relations = [],
									$order = [], $group = [])
	{
		$that = (new static);
		$query = static::query();
		$that->superWhere($query, $where);
		!empty($relations) && $query->with($relations);
		$query->orderBy($that->order[0], $that->order[1]);
		!empty($order) && $query->orderBy($order[0], $order[1]);
		!empty($group) && $query->groupBy(...$group);

		return $query->paginate($limit, $columns, '', $page);
	}

	/**
	 * 获取列表 兼容老的查询
	 * @param null $filter
	 * @param null $range
	 * @param null $sort
	 * @return array
	 * @author wzz
	 */
	public static function getListByOld($filter = null, $range = null, $sort = null)
	{
		$that = (new static);
		$query = static::query();
		$that->superWhere($query, $filter); 
		!empty($range) && $query->limit($range[1])->offset($range[0]);
//		if (!empty($sort) && is_array($sort)) {
//			foreach ($sort as $index => $item) {
//				$direction = 'asc';
//				$item['reverse'] && $direction = 'desc';
//				$query->orderBy($item['field'], $direction);
//			}
//		}
		if (!empty($sort) && is_array($sort)) {
			$query->orderBy($sort[0], $sort[1]);
		}
		return $query->get()->toArray();
	}

	/**
	 * @author ztt
	 * @param $where
	 * @return Builder|Model|object|null
	 */
	public static function getByWhere($where) {
		return static::query()->where($where)->first();
	}

	/**
	 * @author ztt
	 * @param $where
	 * @return Builder[]|\Illuminate\Database\Eloquent\Collection
	 */
	public static function listByWhere($where,$select) {
		return static::query()->where($where)->select($select)->get();
	}
}
