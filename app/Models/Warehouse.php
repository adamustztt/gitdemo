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

class Warehouse extends BaseModel
{
	protected $table = 'warehouse';
	
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
	 * @param $channel_id
	 * @param $ext_id
	 * @return Builder|Model|object|null
	 * @author wzz
	 */
	public static function firstByChannelAndExtId($channel_id, $ext_id,$ext_express_id=0)
	{
		return static::query()
			->where('ext_id', $ext_id)
			->where('channel_id', $channel_id)
			->where('ext_express_id',$ext_express_id)
			->first();
	}

	/**
	 *
	 * @param $channel_id
	 * @param $express
	 * @return Builder|Model|object|null
	 * @author wzz
	 */
	public static function firstByChannelAndTypename($channel_id, $express)
	{
		return static::query()
			->where('typename', $express)
			->where('channel_id', $channel_id)
			->first();
	}
}
