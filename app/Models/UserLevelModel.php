<?php


namespace App\Models;


class UserLevelModel extends BaseModel
{
	protected $table = 'user_level';
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
