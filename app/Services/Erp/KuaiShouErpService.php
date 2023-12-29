<?php


namespace App\Services\Erp;


use App\Enums\ErrorEnum;
use App\Enums\KuaiShouEnum;
use App\Helper\CommonUtil;
use App\Http\Utils\BaseUtil;
use App\Http\Utils\LoggerFactoryUtil;
use Tool\ShanTaoTool\HttpCurl;
use Tool\ShanTaoTool\QiWeiTool;

class KuaiShouErpService
{
	public static function ksRequest($params,$function)
	{
		$request_mode = KuaiShouEnum::METHOD[KuaiShouEnum::CODE[$function]];
		$requestParams["shop_id"] = $params["shop_id"];
		$requestParams["method"] = KuaiShouEnum::CODE[$function];
		$requestParams["data"] = BaseUtil::parseToArray($params["params"] ?? []);
		$requestParams["request_mode"] = $request_mode;
		$baseUrl = env("ERP_DOMAIN");
		$data = HttpCurl::postCurl($baseUrl . "api/ks/request", $requestParams);
		$log = new LoggerFactoryUtil(DouYinErpService::class);
		$log->info("请求接口:" . $baseUrl . "api/ks/request");
		$log->info("请求参数:" . json_encode($requestParams));
		$log->info("快手返回数据:" . json_encode($data));
		if ($data["status"]) {
			$tmp = $data["data"];
			if($tmp["status"]){
				return $tmp["data"];
			}else{
				$policy_msg = [
					'功能' => "快手Erp",
					'请求链接' =>$baseUrl . "v1/api/ks/request",
					'请求参数' => $requestParams,
					'响应结果' => $data,
					'信息时间' => date("Y-m-d H:i:s"),
					'提示消息' => "kserp请求失败"
				];
				QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM") . "k-s" . json_encode($policy_msg, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), env("POLICE_CODE"));

				CommonUtil::throwException([$tmp["code"],$tmp["msg"]]);
			}
		} else {
			$policy_msg = [
				'功能' => "快手Erp",
				'请求链接' =>$baseUrl . "v1/api/ks/request",
				'请求参数' => $requestParams,
				'响应结果' => $data,
				'信息时间' => date("Y-m-d H:i:s"),
				'提示消息' => "kserp请求失败"
			];
			QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM") . "k-s" . json_encode($policy_msg, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), env("POLICE_CODE"));

			CommonUtil::throwException([$data["code"],$data["msg"]]);
		}
	}
}
