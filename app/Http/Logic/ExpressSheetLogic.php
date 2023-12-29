<?php


namespace App\Http\Logic;


use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Models\ExpressSheetModel;
use Tool\ShanTaoTool\QiWeiTool;

class ExpressSheetLogic extends BaseLogic
{
	public static function submitExpressSheet($user)
	{
		$params = app("request")->all();
		$map["user_id"] = $user["id"];
		$map["order_consignee_id"] = $params["package_id"];
		$data = ExpressSheetModel::query()->where($map)->first();
		$map["site_id"] = $user["site_id"];
		$map["email"] = $params["email"];
		$map["reason"] = $params["reason"];
		$map["status"] = 1;
		if($data) {
			if($data->status == 3) {
				return ExpressSheetModel::query()->where(["user_id"=>$user["id"],"order_consignee_id"=>$params["package_id"]])->update($map);
			} else {
				CommonUtil::throwException(ErrorEnum::VALIDATE_ERROR);
			}
		} else {
			$map["no"] = "Yd".date("YmdHis");
			$req =  ExpressSheetModel::create($map);
			if($req) {
				$policyMsg["功能"] = "有新的快递底单申请";
				$policyMsg["底单数据信息"] = $req;
				QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM").json_encode($policyMsg,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),"123");
			}
			return $req;
		}
		
	}
}
