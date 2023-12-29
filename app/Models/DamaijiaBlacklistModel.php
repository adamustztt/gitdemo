<?php


namespace App\Models;


class DamaijiaBlacklistModel extends BaseModel
{
	protected $table = 'damaijia_blacklist';
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [];

	protected $guarded = [
		'id'
	];
}
