<?php


namespace App\Models;


class UserShopAuthorizationLogModel extends BaseModel
{
	protected $table = 'user_shop_authorization_log';
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
