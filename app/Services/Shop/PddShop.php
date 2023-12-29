<?php


namespace App\Services\Shop;


use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Http\Utils\LoggerFactoryUtil;
use App\Services\Vtool\ErpService;
use Tool\ShanTaoTool\HttpCurl;
use Tool\ShanTaoTool\QiWeiTool;

class PddShop extends AbstractShop
{ 
// 请求pdd打单
	protected function requestQueryOrder($shopId, $tid,$third_user_id="")
	{
		$requestParams["shop_id"] = $shopId;
		$requestParams["order_id"] = $tid;
		$url = "/api/v1/api/queryOrderInfo";
		$baseUrl = env("PDD_ERP_DOMAIN");
//		dd($url,$baseUrl,$requestParams);
		$data = HttpCurl::getCurl($baseUrl.$url,$requestParams);
		$log = new LoggerFactoryUtil(PddShop::class);
		$log->info($baseUrl.$url);
		$log->info(json_encode($data));
		$log->info(json_encode($requestParams));
		if(isset($data["status"]) && $data["status"]) {
			return  $data["data"];
		}
		$policy_msg = [
			'功能'=>"密文下单获取pdd订单详情",
			'请求链接'=> $baseUrl.$url,
			'请求参数'=> $requestParams,
			'响应结果'=> $requestParams,
			'信息时间'=>date("Y-m-d H:i:s"),
		];
		QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM").json_encode($policy_msg,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),env("POLICE_CODE"));

		CommonUtil::throwException([2004,"该订单不存在"]);
	}
	// 请求vtool
//	protected function requestQueryOrder($shopId, $tid)
//	{
//		$requestParams["owner_id"] = $shopId;
//		$requestParams["order_sn"] = $tid;
//		$requestParams["vvtype"] = 1;
//		$url = "/tool/erps/pdd-order-get";
//		$erpService = new ErpService();
//		$res = $erpService->sentPostRequest($url, $requestParams);
//		return $res;
//	}
//	protected function requestQueryOrder($shopId, $tid)
//	{
//		$requestParams["owner_id"] = $shopId;
//		$requestParams["order_sn"] = $tid;
//		$requestParams["vvtype"] = 1;
//		$url = "/tool/erps/mpaging-orders";
//		$erpService = new ErpService();
//		$res = $erpService->sentPostRequest($url, $requestParams);
//		return $res;
//	}
}
