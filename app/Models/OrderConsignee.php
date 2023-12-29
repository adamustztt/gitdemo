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
use Illuminate\Support\Facades\DB;
class OrderConsignee extends BaseModel
{

	protected $table = 'order_consignee';
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
	 * @param array $insert
	 * @return bool
	 */
	public static function orderConsigneeCreate(array $insert)
	{
		return self::query()->create($insert);
	}
	public static function listWhereOrderConsignee($where,$update)
	{
		return self::query()->where($where)->update($update);
	}
	public static function getInfo($where)
	{
		return self::query()->where($where)->first();
	}
	/**
	 * @author ztt
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function warehouse()
	{
		return $this->hasOne(Warehouse::Class,"id","warehouse_id");
	}
	public function userOrder()
	{
		return $this->hasOne(UserOrder::Class,"id","order_id");
	}
	public function expressSheet()
	{
		return $this->belongsTo(ExpressSheetModel::class,"id","order_consignee_id");
	}
	public static function getOrderConsigneeById($id){
		return static::query()->where('id',$id)->with("userOrder:id,channel_id,user_id,shipping_fee,price")->first();
	}
	/**
	 * 获取订单包裹列表
	 * ztt
	 * @param array $where
	 * @param null $range
	 * @param null $order
	 * @return Builder[]|\Illuminate\Database\Eloquent\Collection
	 */
	public static function listOrderConsignee($where =[],$range = null, $order = null){
		$that = (new static);
		$query = static::query();
		$that->superWhere($query, $where);
		!empty($range) && $query->offset($range[0])->limit($range[1]);
		!empty($order) && $query->orderBy($order[0], $order[1]);
		return $query->join("user_order","user_order.id","=","order_consignee.order_id")
			->join("product","product.id","=","user_order.product_id")
			->join("warehouse","warehouse.id","=","product.warehouse_id")
			->select("order_consignee.*","user_order.order_sn","user_order.warehouse_id", "user_order.price", "user_order.shipping_fee",
				"user_order.product_number", "user_order.create_time", "user_order.channel_id", "user_order.user_id","user_order.product_id","user_order.source",
				"product.weight AS product_weight","product.alias_name AS product_name","product.ext_id AS product_ext_id","warehouse.ext_id AS warehouse_ext_id")
			->with("warehouse:id,alias_name")
			->with("expressSheet")
			->get();
	}

	/**
	 * 获取订单包裹数量
	 * ztt
	 * @param array $where
	 * @return int
	 */
	public static function getCount($where=[]){
		$that = (new static);
		$query = static::query();
		$that->superWhere($query, $where);
		return $query->join("user_order","user_order.id","=","order_consignee.order_id")->count();
	}

	/**
	 * @author ztt
	 * @param $where
	 * @param string $select
	 * @return Builder[]|\Illuminate\Database\Eloquent\Collection
	 */
	public static function listOrderConsigneeByWhere($where,$select="*") {
		return static::query()->where($where)->select($select)->get();
	}
	public static function updateOrderConsignee($where,$data) {
		return static::query()->where($where)->update($data);
	}
}
