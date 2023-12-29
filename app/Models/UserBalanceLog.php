<?php
/**
 * Created by PhpStorm.
 * User: wzz
 * Date: 2020/9/9
 * Time: 11:07
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserBalanceLog extends BaseModel
{

	protected $table = 'user_balance_log';
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [];
	
	protected $guarded = [
		'id'
	];
	/**
	 * @param array $insert
	 * @return bool
	 */
	public static function userBalanceLogCreate(array $insert)
	{
		return self::query()->create($insert);
	}
	
}
