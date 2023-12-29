<?php


namespace App\Models;


class SiteProduct extends BaseModel
{
	protected $table = 'site_product';
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
