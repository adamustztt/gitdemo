<?php


namespace App\Http\Logic\External;


use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Http\Logic\BaseLogic;
use App\Http\Utils\BaseUtil;
use App\Models\SettingApiUserModel;
use App\Models\ToolOrder;
use App\Models\User;
use App\Models\UserToolPrice;
use App\Services\Erp\TbErpService;
use App\Services\UserService;
use Illuminate\Support\Facades\DB;
use Tool\ShanTaoTool\HttpCurl;

class TbErpLogic extends BaseLogic
{
// 请求tb下单
	public static function requestTbErp($code)
	{
		$params = app("request")->all();
		Db::beginTransaction();
		try {
			$price = ErpLogic::_createErpOrder($params["user_id"], $code);
			$data = TbErpService::tbRequest($params, $code);
			$data["deduct_money"] = $price;
			Db::commit();
		} catch (\Exception $e) {
			Db::rollBack();
			CommonUtil::throwException([$e->getCode(),$e->getMessage()]);
		}
		$data = BaseUtil::parseArrayToLine($data);
		return $data;
	}
	
	public static function tboaidhigh()
	{
		$params = app("request")->all();
		$auth = SettingApiUserModel::query()
			->where("user_id",$params["user_id"])
			->where("code","tb.oaidhigh")
			->first();
		if(!$auth) {
			CommonUtil::throwException(ErrorEnum::ERP_USER_AUTH);
		}
		
		Db::beginTransaction();
		try {
			$price = ErpLogic::_createErpOrder($params["user_id"], "tb.oaidhigh",count($params["query_list"]));
			$domain = env("TOOL_BASEURL");
			$url = $domain."/tool/erps/tb-oaidhigh";
			$header = [
				"Authorization"=>"Bearer ".env("TOOL_TOKEN")
			];
			$res = HttpCurl::postCurl($url,$params,$header);
			if(in_array($res["code"],[100,101,200,202])) {
				CommonUtil::throwException([$res["code"],$res["msg"]]);
			}
			if($res["code"]!=0) {
				CommonUtil::throwException([500,"系统错误请联系客服"]);
			}
			
			$data["deduct_money"] = $price;
			$data["decrypt_id"] = $res["data"]["decrypt_id"];
			Db::commit();
		} catch (\Exception $e) {
			Db::rollBack();
			CommonUtil::throwException([$e->getCode(),$e->getMessage()]);
		}
//		$data = BaseUtil::parseArrayToLine($data);
		return $data;
	}
	
	public static function getdecryptbyid()
	{
		$params = app("request")->all();
		Db::beginTransaction();
		try {
			$domain = env("TOOL_BASEURL");
			$url = $domain."/tool/erps/get-decrypt-by-id";
			$header = [
				"Authorization"=>"Bearer ".env("TOOL_TOKEN")
			];
			$res = HttpCurl::postCurl($url,$params,$header);
			if($res["code"]!=0) {
				CommonUtil::throwException([$res["code"],$res["msg"]]);
			}
			$data = $res["data"];
			Db::commit();
		} catch (\Exception $e) {
			Db::rollBack();
			CommonUtil::throwException([$e->getCode(),$e->getMessage()]);
		}
//		$data = BaseUtil::parseArrayToLine($data);
		return $data;
	}
	
	
	public static function iteminfo1688()
	{
		$params = app("request")->all();
		$auth =  UserToolPrice::query()
			->where("user_id",$params["user_id"])
			->where("code","iteminfo1688")
			->first();
		if(!$auth) {
			CommonUtil::throwException(ErrorEnum::ERP_USER_AUTH);
		}
		$user = User::query()->where("id",$params["user_id"])->first();
		Db::beginTransaction();
		try {
			$userService = new userService();
			// 用户金额变动  用户资金流水
			$userService->decrUserBalance($params["user_id"],$auth["api_price"],0,"1688详情页扣款,点券:".$auth["api_price"],"p",0,2);
			// 生成订单
//			$order_data["id"] = $toolInfo['id'];
			$order_data["site_id"] = 0;
			$order_data["user_id"] = $params["user_id"];
			$order_data["user_name"] =$user["mobile"];
			$order_data["generate_link_url"] = "";
			$order_data["link_url"] = "";
			$order_data["tool_type"] = 2;
			$order_data["tool_id"] = 0;
			$order_data["price"] = $auth["api_price"];
			$order_data["code"] = "iteminfo1688";
			$result = ToolOrder::create($order_data);
			$domain = env("QIANTAI_VTOOL");
			$url = $domain."/api/v1/tool/itemInfo1688";
			$header = [
				"Authorization"=>"Bearer ".env("TOOL_TOKEN")
			];
			$res = HttpCurl::postCurl($url,$params,$header);
			if(!$res["status"]) {
				CommonUtil::throwException([500,$res["msg"]]);
			}
			Db::commit();
		} catch (\Exception $e) {
			Db::rollBack();
			CommonUtil::throwException([$e->getCode(),$e->getMessage()]);
		}
//		$data = BaseUtil::parseArrayToLine($data);
		if(empty($res["data"])) {
			return ["amount"=>$auth["api_price"],"data"=>$res["data"],"msg"=>"商品不存在"];
		}
		return ["amount"=>$auth["api_price"],"data"=>$res["data"]];
	}
	
	public static function iteminfolowprice()
	{
		$params = app("request")->all();
		$auth =  UserToolPrice::query()
			->where("user_id",$params["user_id"])
			->where("code","iteminfolowprice")
			->first();
		if(!$auth) {
			CommonUtil::throwException(ErrorEnum::ERP_USER_AUTH);
		}
		$user = User::query()->where("id",$params["user_id"])->first();
		Db::beginTransaction();
		try {
			$userService = new userService();
			// 用户金额变动  用户资金流水
			$userService->decrUserBalance($params["user_id"],$auth["api_price"],0,"淘宝详情页扣款,点券：".$auth["api_price"],"p",0,2);
			// 生成订单
//			$order_data["id"] = $toolInfo['id'];
			$order_data["site_id"] = 0;
			$order_data["user_id"] = $params["user_id"];
			$order_data["user_name"] =$user["mobile"];
			$order_data["generate_link_url"] = "";
			$order_data["link_url"] = "";
			$order_data["tool_type"] = 2;
			$order_data["tool_id"] = 0;
			$order_data["price"] = $auth["api_price"];
			$order_data["code"] = "iteminfolowprice";
			$result = ToolOrder::create($order_data);
			$domain = env("VTOOL_2");
//			$url = $domain."/tool/accounts/item-info-low-price";
			$url = $domain."/itemInfoLowPrice";
			$header = [
				"Authorization"=>"Bearer ".env("TOOL_TOKEN")
			];
			$res = HttpCurl::getCurl($url,$params,$header);
			if($res["code"] !== 0) {
				CommonUtil::throwException([500,$res["msg"]]);
			}
			Db::commit();
		} catch (\Exception $e) {
			Db::rollBack();
			CommonUtil::throwException([$e->getCode(),$e->getMessage()]);
		}
//		$data = BaseUtil::parseArrayToLine($data);
		if(empty($res["data"])) {
			return ["amount"=>$auth["api_price"],"data"=>$res["data"],"msg"=>"商品不存在"];
		}
		return ["amount"=>$auth["api_price"],"data"=>$res["data"]];
	}
}
