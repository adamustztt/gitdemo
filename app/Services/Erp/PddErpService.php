<?php


namespace App\Services\Erp;


use App\Enums\KuaiShouEnum;
use App\Helper\CommonUtil;
use App\Http\Utils\LoggerFactoryUtil;
use Tool\ShanTaoTool\HttpCurl;
use Tool\ShanTaoTool\QiWeiTool;

class PddErpService
{
	public static function pddRequest($params,$code)
	{
		$requestParams["shop_id"] = $params["shop_id"];
		$requestParams["method"] = $code;
		$requestParams["data"] = $params["params"];
		$baseUrl = env("ERP_DOMAIN");
		$url="api/pdd/request";
		$data = HttpCurl::postCurl($baseUrl . $url, $requestParams);
		
//		dd($baseUrl . "api/dy/sendRequest",$data,$requestParams);
		$log = new LoggerFactoryUtil(DouYinErpService::class);
		$log->info("请求接口:" . $baseUrl . $url);
		$log->info("请求参数:" . json_encode($requestParams));
		$log->info("pdd返回数据:" . json_encode($data));
		if ($data["status"]) {
			$tmp = $data["data"];
			if($tmp["status"]){
				return $tmp["data"];
			}else{
				$policy_msg = [
					'功能' => "dyErp",
					'请求链接' =>$baseUrl . $url,
					'请求参数' => $requestParams,
					'响应结果' => $data,
					'信息时间' => date("Y-m-d H:i:s"),
					'提示消息' => "pddserp请求失败"
				];
				QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM") . "p-d-d" . json_encode($policy_msg, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), env("POLICE_CODE"));

				CommonUtil::throwException([$tmp["code"],$tmp["msg"]]);
			}
		} else {
			$policy_msg = [
				'功能' => "pddErp",
				'请求参数' => $requestParams,
				'响应结果' => $data,
				'信息时间' => date("Y-m-d H:i:s"),
				'提示消息' => "pdderp请求失败"
			];
			QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM") . "k-s" . json_encode($policy_msg, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), env("POLICE_CODE"));

			CommonUtil::throwException([$data["code"],$data["msg"]]);
		}
	}
}
