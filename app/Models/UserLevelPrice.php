<?php


namespace App\Models;
use App\Models\Warehouse;

class UserLevelPrice extends BaseModel
{
	protected $table = 'user_level_price';
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
	 * @author ztt
	 * @param $where
	 * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
	 */
	public static function getUserLevelPrice($where){
		return static::query()->where($where)->first();
	}

	/**
	 * @author ztt
	 * @param $where
	 * @param $data
	 * @return int
	 */
	public static function updateUserLevelPrice($where,$data){
		return static::query()->where($where)->update($data);
	}
}
