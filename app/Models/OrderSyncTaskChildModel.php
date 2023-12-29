<?php


namespace App\Models;


class OrderSyncTaskChildModel extends BaseModel
{
	protected $fillable = [];

	protected $guarded = [
		'id'
	];
	protected $table = 'order_sync_task_child';
}
