<?php


namespace App\Services;


use App\Models\OrderConsignee;
use App\Models\OrderConsigneePushDown;
use App\Models\User;
use App\Models\UserBalanceLog;
use App\Models\UserOrder;
use phpDocumentor\Reflection\Types\Object_;

class OrderConsigneePushDownService
{
	/**
	 * @author ztt
	 * @param $package_id
	 * @param $type 1禁发取消推送 2更新运单推送 3取消推送 4已发货
	 * @return false
	 */
	public static function addPush($package_id,$type) {
		$orderConsignee = OrderConsignee::getById($package_id); 
		$user_order = UserOrder::getById($orderConsignee->order_id);
		if($user_order->order_from != 3) { // 不是API用户
			return false;
		}
		$user_id = $user_order->user_id;
		$notify_url = User::query()->where("id",$user_id)->value("notify_url");
		if(empty($notify_url)) { // 未设置回调地址
			return false;
		}
		$user_balance_log = UserBalanceLog::query()
			->where(["user_id"=>$user_id,"context_id"=>$orderConsignee->id])
			->orderBy("id","desc")->limit(1)
			->first();
		$push_data["site_id"] = $orderConsignee->site_id;
		$push_data["site_order_consignee_id"] = $orderConsignee->site_order_consignee_id;
		$push_data["push_status"] = 1;
		$push_data["push_type"] = 1;
		$push_data["api"] = $notify_url;
		$params_data["site_order_consignee_id"]=$orderConsignee->site_order_consignee_id;
		$params_data["status"]=$orderConsignee->status; //取消发货
		$params_data["consignee"] = $orderConsignee->consignee;
		$params_data["mobile"]= $orderConsignee->mobile;
		$params_data["province"]= $orderConsignee->province;
		$params_data["city"]= $orderConsignee->city;
		$params_data["district"]= $orderConsignee->district;
		$params_data["address"]= $orderConsignee->address;
		$params_data["express_no"]= $orderConsignee->express_no;
		$params_data["payment_sn"]= env("BASE_TRADE_NUMBER")+$user_balance_log->id; //流水编号
		$params_data["user_id"]= $user_id;
		$params_data["cancel_reason"]= $orderConsignee->cancel_reason;
		$params_data["additional"]= $orderConsignee->additional;
		$push_data["params"] = json_encode($params_data);
		OrderConsigneePushDown::create($push_data);
		$is_add_push = OrderConsignee::updateById($package_id,["is_add_push"=>1]);
	} 
}
