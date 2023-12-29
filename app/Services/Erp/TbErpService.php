<?php


namespace App\Services\Erp;


use App\Enums\ErrorEnum;
use App\Enums\TbEnum;
use App\Helper\CommonUtil;
use App\Http\Utils\LoggerFactoryUtil;
use App\Models\UserShopModel;
use Tool\ShanTaoTool\HttpCurl;
use Tool\ShanTaoTool\QiWeiTool;

class TbErpService
{
	public static function tbRequest($params,$code)
	{
		$code = TbEnum::TB_CODE[$code];

		if(empty($params["shop_id"])) {
			CommonUtil::throwException([422,"店铺id不能为空"]);
		}
		$shop_id = $params["shop_id"];
		$userShop = UserShopModel::query()
			->where("shop_id",$shop_id)
			->where("user_id",$params["user_id"])->first();
		$requestParams["method"] = $code;
		$requestParams["data"] = $params["params"];
		$baseUrl = env("ERP_DOMAIN");
		$url="api/tb/request";
		$app_type = $userShop["version_type"];
		if($app_type == 1) {
			$requestParams["secretKey"] = $userShop["access_token"];
		} else {
			$callback_params = json_decode($userShop["callback_params"],true);
			$requestParams["appkey"] = $callback_params["sellernick"];
			$requestParams["secretKey"] = $callback_params["code"];
			$requestParams["vvtype"] = $app_type;
//			{"state":"uutxu-eccbc87e4b5ce2fe28308fd9f2a7baf3-damajia-716","sellernick":"tb5930250350","code":"5112160531061885445","sid":173926031,"title":"\u5c0f\u4e00\u5c0f\u5e97","deadline":"2022-09-24 00:00:00","g_site_id":1,"g_domain":null}
		}
		$data = HttpCurl::postCurl($baseUrl . $url, $requestParams);
		$log = new LoggerFactoryUtil(TbErpService::class);
		$log->info("请求接口:" . $baseUrl . $url);
		$log->info("请求参数:" . json_encode($requestParams));
		$log->info("tb返回数据:" . json_encode($data));
		if(!$data) {
			CommonUtil::throwException(ErrorEnum::KS_ERP_ERROR);
		}
		if ($data["status"]) {
			$tmp = $data["data"];
			if($tmp["status"]){
				return $tmp["data"];
			}else{
				$policy_msg = [
					'功能' => "tbErp",
					'请求链接' =>$baseUrl . $url,
					'请求参数' => $requestParams,
					'响应结果' => $data,
					'信息时间' => date("Y-m-d H:i:s"),
					'提示消息' => "tbserp请求失败"
				];
				QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM") . json_encode($policy_msg, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), env("POLICE_CODE"));

				CommonUtil::throwException([$tmp["code"],$tmp["msg"]]);
			}
		} else {
			$policy_msg = [
				'功能' => "tbErp",
				'请求链接' =>$baseUrl . "api/tb/sendRequest",
				'请求参数' => $requestParams,
				'响应结果' => $data,
				'信息时间' => date("Y-m-d H:i:s"),
				'提示消息' => "tbserp请求失败"
			];
			QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM") . json_encode($policy_msg, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), env("POLICE_CODE"));

			CommonUtil::throwException([$data["code"],$data["msg"]]);
		}
	}
}
