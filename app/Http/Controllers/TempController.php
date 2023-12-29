<?php


namespace App\Http\Controllers;


use App\Models\ExpressModel;
use App\Models\ExpressProductModel;
use App\Models\OrderConsignee;
use App\Models\User;
use App\Models\UserBalanceLog;
use App\Models\UserOrder;

class TempController extends BaseController
{
	/**
	 * 修复api用户下订单平台收入
	 * @return int
	 */
	public function fixApiUserOrderPlatformIncome()
	{
		$userIdMap = User::query()->where("is_api",1)->pluck("id")->toArray();
		$logData = UserBalanceLog::query()
			->whereIn("user_id",$userIdMap)
			->where("additional","like","%下单扣款%")
			->where("create_time",">","2021-06-01")
			->where("platform_income",0)
			->orderBy("id","desc")
			->get();
		$count = 0;
		foreach ($logData as $k=>$v) {
			
			$user_order = UserOrder::query()->where("id",$v["context_id"])->first();
			if($user_order) {
				$express_id = ExpressProductModel::query()
					->where("product_id",$user_order["product_id"])
					->value("damaijia_express_id");
				if(!$express_id) {
					continue;
				}
				$statistics_cost_price = ExpressModel::query()
					->where("id",$express_id)
					->value("statistics_cost_price");
				if(!$statistics_cost_price) {
					continue;
				}
				$platform_income= ($user_order["shipping_fee"] - $statistics_cost_price)*$user_order["page_number"];
				$req = UserBalanceLog::query()
					->where("id",$v["id"])
					->update(["platform_income"=>$platform_income]);
				if($req) {
					$count = $count+1;
				}
			}
		}
		return $count;
	}

	/**
	 * @return int
	 * 修复下单api用户资金流水类型
	 */
	public function fixUserBalanceLogType()
	{
//		$userIdMap = User::query()->where("is_api",1)->pluck("id")->toArray();
		$log_update = UserBalanceLog::query()
//			->whereIn("user_id",$userIdMap)
			->where("additional","like","%下单扣款%")
			->where("log_change_type",0)
			->update(["log_change_type"=>2,"log_type"=>1]);
		return $log_update;
	}

	/**
	 * @return int
	 * 修复api用户包裹退款流水类型
	 */
	public function fixUserBalanceLogTypeRefund()
	{
//		$userIdMap = User::query()->where("is_api",1)->pluck("id")->toArray();
		$log_update = UserBalanceLog::query()
//			->whereIn("user_id",$userIdMap)
			->where("additional","like","%包裹退款%")
			->where("log_change_type",0)
			->update(["log_change_type"=>1,"log_type"=>1]);
		return $log_update;
	}

	/**
	 * @return int
	 * 修复api包裹退款平台收入
	 */
	public function fixApiPackageRefundPlatformIncome()
	{
		$userIdMap = User::query()->where("is_api",1)->pluck("id")->toArray();
		$logData = UserBalanceLog::query()
			->whereIn("user_id",$userIdMap)
			->where("additional","like","%包裹退款%")
			->where("create_time",">","2021-06-01")
			->where("platform_income",0)
			->orderBy("id","desc")
			->get();
		$count = 0;
		foreach ($logData as $k=>$v) {
			$package = OrderConsignee::query()->where("id",$v["context_id"])->first();
			if($package) {
				$user_order = UserOrder::query()->where("id",$package["order_id"])->first();
				$express_id = ExpressProductModel::query()
					->where("product_id",$user_order["product_id"])
					->value("damaijia_express_id");
				if(!$express_id) {
					continue;
				}
				$statistics_cost_price = ExpressModel::query()
					->where("id",$express_id)
					->value("statistics_cost_price");
				if(!$statistics_cost_price) {
					continue;
				}
				$platform_income= $user_order["shipping_fee"] - $statistics_cost_price;
				$req = UserBalanceLog::query()
					->where("id",$v["id"])
					->update(["platform_income"=>$platform_income]);
				if($req) {
					$count = $count+1;
				}
			}
		}
		return $count;
	}
}
