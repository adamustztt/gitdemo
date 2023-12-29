<?php


namespace App\Services\Vtool;


use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Http\Utils\LoggerFactoryUtil;
use Tool\ShanTaoTool\HttpCurl;
use Tool\ShanTaoTool\QiWeiTool;

class ErpService
{
	public function __construct()
	{
		$this->baseUrl = config("tool.baseUrl");
		$this->account = config("tool.account");
		$this->accessKey = config("tool.accessKey");
		

	}
	/**
	 * @author ztt
	 * 登录
	 * @return array|mixed
	 * @throws OuterApiException
	 */
	public function login()
	{
		return env("TOOL_TOKEN");
		$params = [
			"grant_type"=>"client_credentials",
			"client_id" => $this->account,
			"client_secret" => $this->accessKey,
		];
		$result = HttpCurl::postCurl($this->baseUrl."/oauth/token",$params,[],false);
//		dd($params,$this->baseUrl."/oauth/token",$result,$this->account,$this->accessKey);
		return $result["access_token"];
	}
	public function sentPostRequest($url,$params=[])
	{
		return $this->sentRequest($url,$params,"post");
	}
	public function sentGetRequest($url,$params=[])
	{
		return $this->sentRequest($url,$params,"get");
	}
	public function sentRequest($url,$params,$method)
	{
		$baseUrl = env("TOOL_BASEURL");
//		$access_token =  "Bearer ".$this->login();
		$access_token =  "Bearer ".env("TOOL_TOKEN");
		$headers = [
			"Authorization" => $access_token
		];
		if($method == "post") {
			 $data = HttpCurl::postCurl($baseUrl.$url,$params,$headers);
		} else {
			$data = HttpCurl::getCurl($baseUrl.$url,$params,$headers);
		}
		$log = new LoggerFactoryUtil(ErpService::class);
		$log->info("请求接口:".$baseUrl.$url);
		$log->info("请求参数:".json_encode($params));
		$log->info("vt上游返回数据:".json_encode($data));
//		dd($baseUrl.$url,$params,$data);
//		dd($data,$this->login());
		if($data["code"] === 0) {
			return $data["data"];
		} else {
			$policy_msg = [
				'功能'=>"Vttt工具",
				'请求链接'=> $baseUrl.$url,
				'请求参数'=> $params,
				'响应结果'=> $data,
				'信息时间'=>date("Y-m-d H:i:s"),
				'提示消息'=>"vvv请求失败"
			];
			
		
			QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM")."v-t".json_encode($policy_msg,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),env("POLICE_CODE"));

			if($data["code"] == 100) {
				CommonUtil::throwException([422,$data["msg"]]);
			}
			if($data["code"] == 302) {
				CommonUtil::throwException(ErrorEnum::ERP_USER_SHOP);
			}
			if($data["code"] == 303) {
				CommonUtil::throwException(ErrorEnum::ERP_USER_SHOP_BUY);
			}
			if($data["code"] == 614) {
				CommonUtil::throwException(ErrorEnum::ERP_USER_SHOP_ORDER);
			}
			if (strpos($data["msg"], '参数oaid无效') !== false) {
				CommonUtil::throwException(ErrorEnum::ERROR_ORDER_INFO);
			}
			if($data["code"] == 501) {
				CommonUtil::throwException(ErrorEnum::ERP_USER_SHOP_ORDER);
			}
			
//			dd($data);
			CommonUtil::throwException(ErrorEnum::ERP_ERP_ERROR);
		}
		
	}
}
