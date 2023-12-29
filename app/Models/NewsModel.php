<?php


namespace App\Models;


class NewsModel extends BaseModel
{
	protected $table = 'news';
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
