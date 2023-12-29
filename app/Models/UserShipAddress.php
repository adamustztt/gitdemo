<?php
/**
 * Created by PhpStorm.
 * User: wzz
 * Date: 2020/10/21
 * Time: 14:01
 */

namespace App\Models;


class UserShipAddress extends BaseModel
{

	protected $table = 'user_ship_address';
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
