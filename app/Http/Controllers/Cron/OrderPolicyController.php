<?php


namespace App\Http\Controllers\Cron;


use App\Http\Controllers\BaseController;
use App\Models\OrderConsignee;
use App\Services\Warehouses\LisutongWarehouse;
use Tool\ShanTaoTool\QiWeiTool;

class OrderPolicyController extends BaseController
{
	// 超过一定时间（如超过30分钟）包裹仍未发货，
	public function checkPackageStatus()
	{
//		$sixAm =  strtotime(date("Y-m-d"))+6*3600;
//		$ninePm =  strtotime(date("Y-m-d"))+21*3600;
//		$nowTime = time();
//		if($nowTime<$sixAm) {
//			return $this->responseJson("现在时间:".date("Y-m-d H:i:s",$nowTime));
//		}
//		if($nowTime>$ninePm) {
//			return $this->responseJson("现在时间:".date("Y-m-d H:i:s",$nowTime));
//		}
		// 超过一定时间（如超过30分钟）包裹仍未发货
		$time = time()-30*60;
		$date = date("Y-m-d H:i:s",$time);
		$packageId= OrderConsignee::query()
			->where("status","=","p")
			->where("policy_count","=",0)
			->where("create_time","<",$date)
			->pluck("id")->toArray();
		$policyMsg["功能"] = "超过30分包裹仍未发货预警";
		$policyMsg["包裹ID"] = $packageId;
		if(!empty($packageId)) {
			QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM").json_encode($policyMsg,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),env("ROUTINE_POLICY"));
			OrderConsignee::query()->whereIn("id",$packageId)->update(["policy_count"=>1]);
			return $this->responseJson($packageId);
		} else {
			return $this->responseJson();
		}
	}
	// 已发货但未获取订单号（如超过30分钟未获取到单号）
	public function checkPackageExpressNo()
	{
		$time = time()-30*60;
		$date = date("Y-m-d H:i:s",$time);
		$packageId= OrderConsignee::query()
			->where("status","=","s")
			->where("express_no","=","")
			->where("update_time","<",$date)
			->pluck("id")->toArray();
		$policyMsg["功能"] = "已发货超过30分钟未获取到单号";
		$policyMsg["包裹ID"] = $packageId;
		if(!empty($packageId)) {
			QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM").json_encode($policyMsg,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
			return $this->responseJson($packageId);
		} else {
			return $this->responseJson();
		}
	}

	/**
	 * 礼速通包裹预警
	 */
	public function packagePolicyLst() {
		$params = app("request")->all();
		$min_count = $params["min_count"] ?? 3;
		$req = LisutongWarehouse::policyLisutongOrder($min_count);
		return $this->responseJson($req);
	}
}
