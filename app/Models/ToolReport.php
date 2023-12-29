<?php


namespace App\Models;


class ToolReport extends BaseModel
{
	protected $table = 'tool_report';
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
