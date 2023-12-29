<?php


namespace App\Models;


class UserShopOrderModel extends BaseModel
{
	protected $table = 'user_shop_order';
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
