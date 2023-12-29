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

class ChannelPriceMain extends BaseModel
{

	protected $table = 'channel_price_main';
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
		static::query()->create(['status'=>'n','warehouse_id'=>$warehouse_id]);
	}
}
