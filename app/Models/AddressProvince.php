<?php


namespace App\Models;


class AddressProvince extends BaseModel
{

	protected $table = 'address_province';
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
