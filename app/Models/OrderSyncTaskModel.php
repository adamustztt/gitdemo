<?php


namespace App\Models;


class OrderSyncTaskModel extends BaseModel
{
	protected $fillable = [];

	protected $guarded = [
		'id'
	];
	protected $table = 'order_sync_task';
}
