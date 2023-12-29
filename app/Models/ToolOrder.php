<?php


namespace App\Models;


class ToolOrder extends BaseModel
{
	protected $table = 'tool_order';
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
