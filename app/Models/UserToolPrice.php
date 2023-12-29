<?php


namespace App\Models;


class UserToolPrice extends BaseModel
{
	protected $table = 'user_tool_price';
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [];
	protected $guarded = [
		'id'
	];
	public static function getByWhere($where) {
		return static::query()->where($where)->first();
	}
}
