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

class Site extends BaseModel
{

	protected $table = 'site';
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

	/**
	 * @author ztt
	 * @param $site_id
	 * @return Model|\Illuminate\Database\Query\Builder|object|null
	 */
	public static function LockSiteForUpdate($site_id)
	{
		return static::query()->where("id",$site_id)->lockForUpdate()->first();
	}
	
}
