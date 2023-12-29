<?php


namespace App\Models;


class UserProductProfit extends BaseModel
{
	protected $table = 'user_product_profit';
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
