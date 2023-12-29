<?php


namespace App\Services\Shop;


use App\Helper\CommonUtil;
use App\Http\Utils\LoggerFactoryUtil;
use App\Services\Vtool\ErpService;
use Tool\ShanTaoTool\HttpCurl;
use Tool\ShanTaoTool\QiWeiTool;

class TbShop extends AbstractShop
{

//	public function requestQueryOrder($shopId, $tid)
//	{
//		$requestParams["fields"] = "seller_nick, buyer_nick, title, type, created, tid, seller_rate,buyer_flag, buyer_rate,
//		 status, payment, adjust_fee, post_fee, total_fee, pay_time, end_time, modified, consign_time, buyer_obtain_point_fee,
//		  point_fee, real_point_fee, received_payment, commission_fee, buyer_memo, seller_memo, alipay_no,alipay_id,
//		  buyer_message, pic_path, num_iid, num, price, buyer_alipay_no, receiver_name, receiver_state, receiver_city,
//		   receiver_district, receiver_address, receiver_zip, receiver_mobile, receiver_phone,seller_flag, seller_alipay_no, 
//		   seller_mobile, seller_phone, seller_name, seller_email, available_confirm_fee, has_post_fee, timeout_action_time,
//		    snapshot_url, cod_fee, cod_status, shipping_type, trade_memo, is_3D,buyer_email,buyer_area, trade_from,is_lgtype,
//		    is_force_wlb,is_brand_sale,buyer_cod_fee,discount_fee,seller_cod_fee,express_agency_fee,invoice_name,
//		    service_orders,credit_cardfee,orders,trade_ext";
//		$requestParams["shop_id"] = $shopId;
//		$requestParams["tid"] = $tid;
//		$requestParams["vvtype"] = 2;
//		$requestParams["include_oaid"] = true;
//		$url = "/tool/erps/fullinfo";
//		$erpService = new ErpService();
//		$res = $erpService->sentPostRequest($url, $requestParams);
//		return $res;
//	}
	public function requestQueryOrder($shopId, $tid, $third_user_id)
	{
		$requestParams["fields"] = "seller_nick, buyer_nick, title, type, created, tid, seller_rate,buyer_flag, buyer_rate,
		 status, payment, adjust_fee, post_fee, total_fee, pay_time, end_time, modified, consign_time, buyer_obtain_point_fee,
		  point_fee, real_point_fee, received_payment, commission_fee, buyer_memo, seller_memo, alipay_no,alipay_id,
		  buyer_message, pic_path, num_iid, num, price, buyer_alipay_no, receiver_name, receiver_state, receiver_city,
		   receiver_district, receiver_address, receiver_zip, receiver_mobile, receiver_phone,seller_flag, seller_alipay_no, 
		   seller_mobile, seller_phone, seller_name, seller_email, available_confirm_fee, has_post_fee, timeout_action_time,
		    snapshot_url, cod_fee, cod_status, shipping_type, trade_memo, is_3D,buyer_email,buyer_area, trade_from,is_lgtype,
		    is_force_wlb,is_brand_sale,buyer_cod_fee,discount_fee,seller_cod_fee,express_agency_fee,invoice_name,
		    service_orders,credit_cardfee,orders,trade_ext";
		$requestParams["shop_id"] = $shopId;
		$requestParams["tid"] = $tid;
		$requestParams["uid"] = $third_user_id;
		$requestParams["vvtype"] = 2;
		$requestParams["include_oaid"] = true;
		$baseUrl = env("QIANTAI_VTOOL");
		$url = "/api/v1/erp/tool/tb/getOrderInfoVt";
//		dd($requestParams,$baseUrl . $url);
		$data = HttpCurl::getCurlOrigin($baseUrl . $url, $requestParams);
		$log = new LoggerFactoryUtil(TbShop::class);
		$log->info("url:".$baseUrl.$url);
		$log->info("params:".json_encode($requestParams));
		$log->info("返回结果:".json_encode($data));
		$data = json_decode($data,true);
		if (isset($data["status"]) && $data["status"]) {
			return $data["data"];
		}
		$policy_msg = [
			'功能' => "密文下单获取tb订单详情",
			'请求链接' => $baseUrl . $url,
			'请求参数' => $requestParams,
			'响应结果' => $requestParams,
			'信息时间' => date("Y-m-d H:i:s"),
		];
		QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM") . json_encode($policy_msg, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), env("POLICE_CODE"));

		CommonUtil::throwException([2004, "该订单不存在"]);
	}
}
