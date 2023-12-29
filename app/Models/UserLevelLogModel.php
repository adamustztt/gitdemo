<?php


namespace App\Models;


class UserLevelLogModel extends BaseModel
{
	protected $table = 'user_level_log';
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
