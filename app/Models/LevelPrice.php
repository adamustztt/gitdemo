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

class LevelPrice extends BaseModel
{

	protected $table = 'level_price';
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
		$type = [1,2];// 站点类型：1：主站 2：分站
		for ($a = 0;$a < 2;$a++){
			$level = [1,2,3,4];
			for ($i = 0;$i <4;$i++){
				$site = Site::select('id')->get();
				foreach ($site as $val) {
					static::query()->create(['type'=> $type[$a],'level' => $level[$i], 'site_id'=>$val['id'], 'warehouse_id' => $warehouse_id,'status'=>'n']);
				}
			}
		}
		
	}
	
	
	
}
