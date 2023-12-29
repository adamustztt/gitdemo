<?php


namespace App\Models;


class SiteWebSitting extends BaseModel
{
	protected $table = "site_web_sitting";
	protected $fillable = [];
	protected $guarded = [
		'id'
	];
	public static function getSiteWeb($where)
	{
		return static::query()->where($where)->first();
	}
}
