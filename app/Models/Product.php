<?php
/**
 * Created by PhpStorm.
 * User: wzz
 * Date: 2020/9/9
 * Time: 11:07
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Product extends BaseModel
{

	protected $table = 'product';
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'name',
		'thumb',
		'othumb',
		'cost_price',
		'weight',
		'status',
		'isort',
		'channel_id',
		'ext_id',
		'warehouse_id',
		'stock',
		'sales',
		'up_cost_price',
		'up_status'
	];
	
	/**
	 * 
	 * @param $channel_id
	 * @param $ext_id
	 * @return Builder|Model|object|null
	 * @author wzz
	 */
	public static function firstByChannelAndExtId($channel_id, $ext_id)
	{
		return static::query()
			->where('ext_id', $ext_id)
			->where('channel_id', $channel_id)
			->first();
	}

	/**
	 * @author ztt
	 * @param $warehouse_id
	 * @param $ext_id
	 * @return Builder|Model|object|null
	 */
	public static function firstByWarehouseAndExtId($warehouse_id, $ext_id)
	{
		return static::query()
			->where('ext_id', $ext_id)
			->where('warehouse_id', $warehouse_id)
			->first();
	}

	/**
	 * @param $data
	 * @param array $where
	 * @param string[] $columns
	 * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
	 */
	public static function listProduct($data,$where=[],$columns=['*'])
    {
        return static::query()
//            ->leftJoin('warehouse','warehouse.id','product.warehouse_id')
            ->leftJoin('site_product','site_product.product_id','product.id')
            ->where($where)->paginate($data['range'][1],$columns,'page',$data['range'][0]);
    }

	/**
	 * @return Builder[]|\Illuminate\Database\Eloquent\Collection
	 */
    public static function listProductAll($data)
	{
		return static::query()->paginate(20,['*'],'page',$data);
	}

	/**
	 * @return int
	 */
	public static function getProductCount()
	{
		return static::query()->count();
	}
	/**
	 * @return Builder[]|\Illuminate\Database\Eloquent\Collection
	 */
	public static function listWarehouseAll($data)
	{
		return Warehouse::paginate(10,['*'],'page',$data);
	}

	/**
	 * @return int
	 */
	public static function getWarehouseCount()
	{
		return Warehouse::count();
	}


}
