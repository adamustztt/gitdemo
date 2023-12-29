<?php


namespace App\Models;


class SiteBalanceLog extends BaseModel
{
	protected $table = 'site_balance_log';
	protected $fillable = [];

	protected $guarded = [
		'id'
	];


	/**
	 * @author ztt
	 * @param $data
	 * @return bool
	 */
	public static function insertSiteBalanceLog($data) {
		return static::query()->create($data);
	}
}
