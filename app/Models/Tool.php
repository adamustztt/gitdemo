<?php


namespace App\Models;


class Tool extends BaseModel
{

	protected $table = 'tool';
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
