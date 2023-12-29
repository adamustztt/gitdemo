<?php


namespace App\Models;


class DamaijiaWarehouseUserSource extends BaseModel
{
	protected $table = 'damaijia_warehouse_user_source';
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [];

	protected $guarded = [
		'id'
	];
	public static function getPluckByWhere($where,$column) {
		return static::query()->where($where)->pluck($column);
	}
}
