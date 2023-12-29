<?php


namespace App\Models;


class UserShopModel extends BaseModel
{
	protected $table = 'user_shop';
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
