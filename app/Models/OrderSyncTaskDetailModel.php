<?php


namespace App\Models;


class OrderSyncTaskDetailModel extends BaseModel
{
	protected $fillable = [];

	protected $guarded = [
		'id'
	];
	protected $table = 'order_sync_task_detail';
}
