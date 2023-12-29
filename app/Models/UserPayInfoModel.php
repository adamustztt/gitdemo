<?php


namespace App\Models;


class UserPayInfoModel extends BaseModel
{
	protected $table = 'user_pay_info';
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
