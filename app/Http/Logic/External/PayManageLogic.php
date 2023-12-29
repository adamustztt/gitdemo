<?php


namespace App\Http\Logic\External;


use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Http\Logic\BaseLogic;
use App\Http\Utils\BaseUtil;
use App\Models\UserPayInfoModel;
use Tool\ShanTaoTool\HttpCurl;

class PayManageLogic extends BaseLogic
{
	
	public static function getScanCode($params,$user_id)
	{
		$data = UserPayInfoModel::query()->where("user_id",$user_id)->first();
		$headers = [
			'appKey'=>$data["app_key"],
			'appSecret'=>$data["app_secret"],
		];
		$ret = HttpCurl::postCurl(env("OFFICIAL_PAY_URL")."v2/pay/scanCode",$params,$headers);
		if($ret["status"] == false) {
			CommonUtil::throwException([20001,$ret["msg"]]);
		}
		return BaseUtil::parseToArray($ret["data"],2);
	}
	public static function listMerchant($user_id)
	{
		$data = UserPayInfoModel::query()->where("user_id",$user_id)->first();
		$headers = [
			'appKey'=>$data["app_key"],
			'appSecret'=>$data["app_secret"],
		];
		
		$ret = HttpCurl::getCurl(env("OFFICIAL_PAY_URL")."v2/pay/merchant/list",[],$headers);
		if($ret["status"] == false) {
			CommonUtil::throwException([20001,$ret["msg"]]);
		}
		return BaseUtil::parseToArray($ret["data"],2);
	}
	public static function getOrderDetail($params,$user_id)
	{
		$data = UserPayInfoModel::query()->where("user_id",$user_id)->first();
		$headers = [
			'appKey'=>$data["app_key"],
			'appSecret'=>$data["app_secret"],
		];
		$ret = HttpCurl::getCurl(env("OFFICIAL_PAY_URL")."v2/pay/order/detail",$params,$headers);
		if($ret["status"] == false) {
			CommonUtil::throwException([20001,$ret["msg"]]);
		}
		return BaseUtil::parseToArray($ret["data"],2);
	}
}
