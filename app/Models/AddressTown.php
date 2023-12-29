<?php


namespace App\Models;


class AddressTown extends BaseModel
{
	protected $table = "address_town";
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [];
	protected $guarded = [
		"id"
	];
}
