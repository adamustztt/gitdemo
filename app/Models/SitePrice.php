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

class SitePrice extends BaseModel
{

	protected $table = 'site_price';
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
		$site = Site::select('id')->get();
		foreach ($site as $val) {
			static::query()->create(['status' => 'n', 'site_id' => $val['id'], 'warehouse_id' => $warehouse_id]);
		}
	}

	public static function getSitePrice($where)
	{
		return static::query()->where($where)->first();
	}
}
