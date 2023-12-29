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

class Channel extends BaseModel
{

	protected $table = 'channel';
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [];
	protected $guarded = [
		'id'
	];
	
	public static function getWhereData($where=[],$columns=['*'])
	{
		return static::query()->where($where)->select($columns)->first();
	}
	
	
	
}
