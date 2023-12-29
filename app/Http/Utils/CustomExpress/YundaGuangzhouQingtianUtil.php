<?php


namespace App\Http\Utils\CustomExpress;


use App\Enums\ErrorEnum;
use App\Exceptions\ApiException;
use App\Http\Bean\Utils\CustomExpress\YundaCreateBmOrderBean;
use App\Http\Utils\BaseUtil;
use App\Http\Utils\LoggerFactoryUtil;
use Tool\ShanTaoTool\QiWeiTool;

class YundaGuangzhouQingtianUtil extends BaseUtil
{
	/**
	 * 初始化header
	 * @param array $params 参数
	 */
	public static function initHeader($jsonInfo)
	{
		$config = config("customExpress.yunda_guangzhou_qingtian");
		$appSecret = $config["appSecret"];
		$appKey = $config["appKey"];

		//生成的SIGN签名串
		$sign = md5($jsonInfo.'_'.$appSecret);
		//header
		$header = [
			'app-key:'.$appKey,
			'sign:'.$sign,
			'req-time:'.time(),
			'Content-Type:application/json;charset=UTF-8',
		];
		return $header;
	}

	/**
	 * 电子面单下单
	 * @param YundaCreateBmOrderBean $yundaCreateBmOrderBean
	 */
	public static function createBmOrder(YundaCreateBmOrderBean $yundaCreateBmOrderBean)
	{
		$url = "accountOrder/createBmOrder";
		//获取配置
		$config = config("customExpress.yunda_guangzhou_qingtian");
		$params = [
			"appid"=>$config["appKey"],
			"partner_id"=>$config["partnerId"],
			"secret"=>$config["secret"],
			"orders"=>[
				[
					"order_serial_no"=>$yundaCreateBmOrderBean->getOrderNumber(),
					"khddh"=>$yundaCreateBmOrderBean->getOrderNumber(),
					"sender"=>[
						"name"=>$yundaCreateBmOrderBean->getSendName(),
						"province"=>$yundaCreateBmOrderBean->getSendProvince(),
						"city"=>$yundaCreateBmOrderBean->getSendCity(),
						"county"=>$yundaCreateBmOrderBean->getSendCountry(),
						"address"=>$yundaCreateBmOrderBean->getSendAddress(),
					],
					"receiver"=>[
						"name"=>$yundaCreateBmOrderBean->getReceiveName(),
						"province"=>$yundaCreateBmOrderBean->getReceiveProvince(),
						"city"=>$yundaCreateBmOrderBean->getReceiveCity(),
						"county"=>$yundaCreateBmOrderBean->getReceiveCountry(),
						"address"=>$yundaCreateBmOrderBean->getReceiveAddress(),
						"mobile"=>$yundaCreateBmOrderBean->getReceivePhone(),
					],
					"order_type"=>"common",
					"node_id"=>350
				]
			]
		];
		//请求参数body,指定JSON格式   转json
		$json_info = json_encode($params,JSON_UNESCAPED_UNICODE);
		$header = self::initHeader($json_info);
		$res = self::postJson($config["url"].$url, $json_info, $header);
		return $res;
	}

	/**
	 * 取消下单接口
	 * @param $orderNumber string
	 */
	public static function cancelBmOrder($orderNumber,$mailno)
	{
		$url = "accountOrder/cancelBmOrder";
		//获取配置
		$config = config("customExpress.yunda_guangzhou_qingtian");
		$params = [
			"appid"=>$config["appKey"],
			"partner_id"=>$config["partnerId"],
			"secret"=>$config["secret"],
			"orders"=>[
				[
					"order_serial_no"=>$orderNumber,
					"mailno"=>$mailno
				]
			]
		];
		//请求参数body,指定JSON格式   转json
		$json_info = json_encode($params,JSON_UNESCAPED_UNICODE);
		$header = self::initHeader($json_info);
		$res = self::postJson($config["url"].$url, $json_info, $header);
		return $res;
	}

	/**
	 * 查询指定面单类型的余量
	 * @param string $type 面单类型
	 */
	public static function searchBmCount($type = "common")
	{
		$url = "accountOrder/searchCount";
		//获取配置
		$config = config("customExpress.yunda_guangzhou_qingtian");
		$params = [
			"appid"=>$config["appKey"],
			"partner_id"=>$config["partnerId"],
			"secret"=>$config["secret"],
			"type"=>$type
		];
		//请求参数body,指定JSON格式   转json
		$json_info = json_encode($params,JSON_UNESCAPED_UNICODE);
		$header = self::initHeader($json_info);
		$res = self::postJson($config["url"].$url, $json_info, $header);
		return $res;
	}

	/**
	 * 返回报文code对应关系
	 * @param $code
	 * @return string
	 */
	private static function getResCodeInfo($code){
		switch ($code){
			case '0000':
				return "请求成功";
			case '7100':
				return "账号无权限";
			case '7200':
				return "接口无权限";
			case '7300':
				return "IP无权限";
			case '7400':
				return "签名失败";
			case '7500':
				return "超过单用户日最高访问量";
			case '7501':
				return "超过日访问量最高值";
			case '7502':
				return "超过单用户接口QPS最大限制";
			case '7503':
				return "超过该接口QPS最大限制";
			case '7600':
				return "头信息header参数中缺少app-key";
			case '7601':
				return "头信息header参数中缺少sign";
			case '7602':
				return "头信息header参数中缺少req-time";
			case '7603':
				return "content-type只支持application/json;utf-8格式";
			case '7604':
				return "httpmothod只支持post类型";
			case '7605':
				return "请求body参数不能为空";
			case '7777':
				return "内部服务错误";
			default:
				return '未知错误';
		}
	}

	/**
	 * http 请求
	 * @param $url
	 * @param $data
	 * @param array $header
	 * @param int $timeout
	 * @return mixed|string
	 * @throws Exception
	 */
	public static function postJson($url, $data, $header = array(
		"Content-Type: application/json;charset=UTF-8"
	),$timeout=5)
	{
		$url = trim($url);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		$res = curl_exec($ch);
		if ($res === false) {
			$res = curl_error($ch);
			throw new ApiException(ErrorEnum::ERROR_YUNDA_WAREHOUSE);
		}
		$base_url = $config = config("customExpress.yunda_guangzhou_qingtian");
		$function = "";
		switch ($url){
			case $base_url["url"] . "accountOrder/searchCount": $function="获取用户信息"; break;
			case $base_url["url"] . "accountOrder/cancelBmOrder": $function="取消订单"; break;
			case $base_url["url"] . "accountOrder/createBmOrder": $function="请求下单"; break;
		}
		$policy_msg["功能"] = $function;
		$policy_msg["请求链接"] = $url;
		$policy_msg["请求参数"] = $data;
		$policy_msg["响应结果"] = json_decode($res,true);
		$policy_msg["信息时间"] = date("Y-m-d H:i:s");
		$log = new LoggerFactoryUtil(YunDaExpressUtil::class);
		$log->info(json_encode($policy_msg));
//		$api_result["请求地址"]=$url;
//		$api_result["请求参数"]=$data;
//		$api_result["返回结果"]=json_decode($res,true);
//        dd(json_encode($api_result,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
		curl_close($ch);
		if (!empty($res)){
			$res_array = json_decode($res,true);
			if ($res_array['code'] === '0000'){
				return json_decode($res,true)["data"];
			}
			QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM")."韵达广州擎天".json_encode($policy_msg,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
			throw new ApiException([$res_array['code'],self::getResCodeInfo($res_array['code'])]);
		}else{
			QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM")."韵达广州擎天".json_encode($policy_msg,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
			throw new ApiException(ErrorEnum::ERROR_YUNDA_WAREHOUSE);
		}

	}
}
