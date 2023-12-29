<?php
/**
 * Created by PhpStorm.
 * User: wzz
 * Date: 2020/9/9
 * Time: 11:07
 */

namespace App\Models;


use App\Helper\WhereUtil;
use Composer\DependencyResolver\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\BaseController;

class UserOrder extends BaseModel
{

	protected $table = 'user_order';
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [];
	protected $guarded = [
		'id'
	];

	/**
	 *
	 * @param $where
	 * @param $range
	 * @param string[] $field
	 * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
	 */
	public static function listPackagePage($request, $where, $field = ['*'])
	{
		// 处理逻辑
		$range = $request['range'];
		$query = static::query();
		$that = (new static);
		$that->superWhere($query, $where);
		$list = $query->join('order_consignee', 'order_consignee.order_id', 'user_order.id')
			->join('product', 'user_order.product_id', 'product.id')
			->where('user_id', $request->user_id)->select($field)
			->paginate($range[1], '', 'page', $range[0]);
		return $list;
	}

	/**
	 * @param $request
	 * @param $where
	 * @param string[] $field
	 */
	public static function listGetUserOrder($request, $where, $columns = ['*'])
	{
		$range = $request['range'];
		$query = static::query();
		$that = (new static);
		$that->superWhere($query, $where);
		return $query->join('product', 'product.id', 'user_order.product_id')
			->paginate($range[1], $columns, 'page', $range[0]);
	}

	/**
	 * @param array $insert
	 * @return bool
	 */
	public static function userOrderCreate(array $insert)
	{
		return self::query()->create($insert);
	}
    

	/**
	 * @param array $where
	 * @param array $insert
	 * @return int
	 */
	public static function userOrderUpdate($where = [], array $insert)
	{
		return self::query()->where($where)->update($insert);
	}

	/**
	 * @param array $where
	 * @param string[] $columns
	 * @return Builder|Model|object|null
	 */
	public static function getUserOrder($where = [], $columns = ['*'])
	{
		return self::query()->where($where)->select($columns)->first();
	}
}
