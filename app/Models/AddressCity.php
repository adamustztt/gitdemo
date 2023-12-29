<?php


namespace App\Models;


class AddressCity extends BaseModel
{

	protected $table = 'address_city';
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
