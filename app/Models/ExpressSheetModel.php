<?php


namespace App\Models;


class ExpressSheetModel extends BaseModel
{
	protected $table="express_sheet_log";
	protected $fillable = [];
	protected $guarded = [
		'id'
	];
	public function user()
	{
		return $this->hasOne(User::class,"id", "user_id");
	}
	public function orderConsignee()
	{
		return $this->hasOne(OrderConsignee::class,"id", "order_consignee_id");
	}
}
