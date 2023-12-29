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

class ChannelPrice extends BaseModel
{

	protected $table = 'channel_price';
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
	 * @param $warehouse_id
	 */
	public static function createTable($warehouse_id)
	{
		$site = Channel::select('id')->get();
		foreach ($site as $val){
			static::query()->create(['status'=>'n','channel_id'=>$val['id'],'warehouse_id'=>$warehouse_id]);
		}
	}
}
