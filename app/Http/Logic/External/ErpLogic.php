<?php


namespace App\Http\Logic\External;


use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Http\Logic\BaseLogic;
use App\Models\SettingApiModel;
use App\Models\SettingApiOrderModel;
use App\Models\SettingApiUserModel;
use App\Services\UserService;
use App\Services\Vtool\ErpService;
use Illuminate\Support\Facades\DB;

class ErpLogic extends BaseLogic
{
	public static function _createErpOrder($user_id, $code, $count = 1)
	{
		if ($count < 1) {
			CommonUtil::throwException(ErrorEnum::ERROR_COUNT);
		}
		$erpInfo = SettingApiUserModel::query()->where(["user_id" => $user_id, "code" => $code])->select("cost_point", "api_profit")->first();
		$setting_api = SettingApiModel::query()->where("code", $code)->select("id", "service")->first();
		$statistics_cost_price = $setting_api["statistics_cost_price"];
		$price = ($erpInfo["cost_point"] + $erpInfo["api_profit"]) * $count;
		$platform_profit = $price-($statistics_cost_price*$count);
		$map["price"] = $price;
		$map["user_id"] = $user_id;
		$map["setting_api_id"] = $setting_api["id"];
		$map["site_profit"] = 0;
		$map["up_site_profit"] = 0;
		$map["status"] = 2;
		SettingApiOrderModel::create($map);
		$userService = new UserService();
		$userService->decrUserBalance($user_id, $price, $setting_api["id"], "ERP扣款:" . $setting_api["service"],"p",$platform_profit,2);
		return $price;
	}

	// 淘宝店铺授权
	public static function getShopAuthorize()
	{
		$params = app("request")->all();
		$requestParams["shop_id"] = $params["shopId"];
		$url = "/tool/erps/shop-authorize";
		$data = "";
		Db::beginTransaction();
		try {
			$price = self::_createErpOrder($params["user_id"], "ShopAuthorize");
			$erpService = new ErpService();
			$data = $erpService->sentPostRequest($url, $requestParams);
			$data["deduct_money"] = $price;
			Db::commit();
		} catch (\Exception $e) {
			Db::rollBack();
			throw $e;
		}
		return $data;
	}

	// 淘宝店铺授权
	public static function getShopInfo()
	{
		$params = app("request")->all();
		$requestParams["url"] = $params["goods_url"];
		if (!empty($params["type"])) {
			$requestParams["type"] = $params["type"];
		}
		$url = "/tool/erps/get-shop-info";
		$data = "";
		Db::beginTransaction();
		try {
			$price = self::_createErpOrder($params["user_id"], "getShopInfo");
			$erpService = new ErpService();
			$data = $erpService->sentGetRequest($url, $requestParams);
			$data["deduct_money"] = $price;
			Db::commit();
		} catch (\Exception $e) {
			Db::rollBack();
			throw $e;
		}
		return $data;
	}

	//订购时间查询
	public static function getSubscribe()
	{
		$params = app("request")->all();
		$requestParams["nick"] = $params["nick"];
		$url = "/tool/erps/subscribe";
		$data = "";
		Db::beginTransaction();
		try {
			$price = self::_createErpOrder($params["user_id"], "OrderTime");
			$erpService = new ErpService();
			$data = $erpService->sentPostRequest($url, $requestParams);
			$data["deduct_money"] = $price;
			Db::commit();
		} catch (\Exception $e) {
			Db::rollBack();
			CommonUtil::throwException([$e->getCode(), $e->getMessage()]);
		}
		return $data;
	}

	//查询卖家已卖出的交易数据
	public static function listSold()
	{
		$params = app("request")->all();
		$requestParams["fields"] = $params["fields"];
		$requestParams["shop_id"] = $params["shopId"];
		$requestParams["start_created"] = $params["startCreated"];
		$requestParams["end_created"] = $params["endCreated"];
		$requestParams["status"] = $params["status"];
		$requestParams["buyer_nick"] = $params["buyerNick"];
		$requestParams["type"] = $params["type"];
		$requestParams["ext_type"] = $params["extType"];
		$requestParams["rate_status"] = $params["rateStatus"];
		$requestParams["tag"] = $params["tag"];
		$requestParams["page_no"] = $params["pageNo"];
		$requestParams["page_size"] = $params["pageSize"];
		$requestParams["use_has_next"] = $params["useHasNext"];
		$requestParams["buyer_open_id"] = $params["buyerOpenId"];
		$requestParams["include_oaid"] = $params["includeOaid"];
		foreach ($requestParams as $k => $v) {
			if (empty($v)) {
				unset($requestParams[$k]);
			}
		}
		$url = "/tool/erps/sold";
		$data = "";
		Db::beginTransaction();
		try {
			$price = self::_createErpOrder($params["user_id"], "OrderList");
			$erpService = new ErpService();
			$data = $erpService->sentPostRequest($url, $requestParams);
			$data["deduct_money"] = $price;
			Db::commit();
		} catch (\Exception $e) {
			Db::rollBack();
			CommonUtil::throwException([$e->getCode(), $e->getMessage()]);
		}
		return $data;
	}

	//获取订单明细
	public static function getFullInfo()
	{
		$params = app("request")->all();
		$requestParams["fields"] = $params["fields"];
		$requestParams["shop_id"] = $params["shopId"];
		$requestParams["tid"] = $params["tid"];
		if ($params["includeOaid"]) {
			$requestParams["include_oaid"] = $params["includeOaid"];
		}
		$url = "/tool/erps/fullinfo";
		$data = "";
		Db::beginTransaction();
		try {
			$price = self::_createErpOrder($params["user_id"], "OrderDetails");
			$erpService = new ErpService();
			$data = $erpService->sentPostRequest($url, $requestParams);
			$data["deduct_money"] = $price;
			Db::commit();
		} catch (\Exception $e) {
			Db::rollBack();
			CommonUtil::throwException([$e->getCode(), $e->getMessage()]);
		}
		return $data;
	}

	//订单备注
	public static function setMemo()
	{
		$params = app("request")->all();
		$requestParams["shop_id"] = $params["shopId"];
		$requestParams["tid"] = $params["tid"];
		$requestParams["memo"] = $params["memo"];
		if ($params["flag"]) {
			$requestParams["flag"] = $params["flag"];
		}
		$url = "/tool/erps/memo";
		$data = "";
		Db::beginTransaction();
		try {
			$price = self::_createErpOrder($params["user_id"], "OrderNote");
			$erpService = new ErpService();
			$data = $erpService->sentPostRequest($url, $requestParams);
			$data["deduct_money"] = $price;
			Db::commit();
		} catch (\Exception $e) {
			Db::rollBack();
			CommonUtil::throwException([$e->getCode(), $e->getMessage()]);
		}
		return $data;
	}

	//增量订单查询
	public static function listIncrement()
	{
		$params = app("request")->all();
		$requestParams["fields"] = $params["fields"];
		$requestParams["shop_id"] = $params["shopId"];
		$requestParams["start_create"] = $params["startCreate"];
		$requestParams["end_create"] = $params["endCreate"];
		if ($params["status"]) {
			$requestParams["status"] = $params["status"];
		}
		if ($params["type"]) {
			$requestParams["type"] = $params["type"];
		}
		if ($params["extType"]) {
			$requestParams["ext_type"] = $params["extType"];
		}
		if ($params["tag"]) {
			$requestParams["tag"] = $params["tag"];
		}
		if ($params["pageNo"]) {
			$requestParams["page_no"] = $params["pageNo"];
		}
		if ($params["pageSize"]) {
			$requestParams["page_size"] = $params["pageSize"];
		}
		if ($params["useHasNext"]) {
			$requestParams["use_has_next"] = $params["useHasNext"];
		}
		if ($params["includeOaid"]) {
			$requestParams["include_oaid"] = $params["includeOaid"];
		}
		$url = "/tool/erps/increment";
		$data = "";
		Db::beginTransaction();
		try {
			$price = self::_createErpOrder($params["user_id"], "OrderMore");
			$erpService = new ErpService();
			$data = $erpService->sentPostRequest($url, $requestParams);
			$data["deduct_money"] = $price;
			Db::commit();
		} catch (\Exception $e) {
			Db::rollBack();
			CommonUtil::throwException([$e->getCode(), $e->getMessage()]);
		}
		return $data;
	}

	//评价列表查询
	public static function listTraderates()
	{
		$params = app("request")->all();
		$requestParams["shop_id"] = $params["shopId"];
		$requestParams["fields"] = $params["fields"];
		$requestParams["rate_type"] = $params["rateType"];
		$requestParams["role"] = $params["role"];
		if ($params["result"]) {
			$requestParams["result"] = $params["result"];
		}
		if ($params["pageNo"]) {
			$requestParams["page_no"] = $params["pageNo"];
		}
		if ($params["pageSize"]) {
			$requestParams["page_size"] = $params["pageSize"];
		}
		if ($params["startDate"]) {
			$requestParams["start_date"] = $params["startDate"];
		}
		if ($params["endDate"]) {
			$requestParams["end_date"] = $params["endDate"];
		}
		if ($params["tid"]) {
			$requestParams["tid"] = $params["tid"];
		}
		if ($params["useHasNext"]) {
			$requestParams["use_has_next"] = $params["useHasNext"];
		}

		if ($params["numIid"]) {
			$requestParams["num_iid"] = $params["numIid"];
		}
		$url = "/tool/erps/traderates";
		$data = "";
		Db::beginTransaction();
		try {
			$price = self::_createErpOrder($params["user_id"], "EvaluationList");
			$erpService = new ErpService();
			$data = $erpService->sentPostRequest($url, $requestParams);
			$data["deduct_money"] = $price;
			Db::commit();
		} catch (\Exception $e) {
			Db::rollBack();
			CommonUtil::throwException([$e->getCode(), $e->getMessage()]);
		}
		return $data;
	}

	//物流信息
	public static function getCompanies()
	{
		$params = app("request")->all();
		$requestParams["fields"] = $params["fields"];
		if ($params["isRecommended"]) {
			$requestParams["is_recommended"] = $params["isRecommended"];
		}
		if ($params["orderMode"]) {
			$requestParams["order_mode"] = $params["orderMode"];
		}

		$url = "/tool/erps/companies";
		$data = "";
		Db::beginTransaction();
		try {
			$price = self::_createErpOrder($params["user_id"], "ExpressCompany");
			$erpService = new ErpService();
			$data = $erpService->sentPostRequest($url, $requestParams);
			$data["deduct_money"] = $price;
			Db::commit();
		} catch (\Exception $e) {
			Db::rollBack();
			CommonUtil::throwException([$e->getCode(), $e->getMessage()]);
		}
		return $data;
	}

	//获取物流详情
	public static function getCompaniesDetail()
	{
		$params = app("request")->all();
		$requestParams["shop_id"] = $params["shopId"];
		$requestParams["fields"] = $params["fields"];
		if ($params["tid"]) {
			$requestParams["tid"] = $params["tid"];
		}
		if ($params["buyerNick"]) {
			$requestParams["buyer_nick"] = $params["buyerNick"];
		}
		if ($params["status"]) {
			$requestParams["status"] = $params["status"];
		}
		if ($params["sellerConfirm"]) {
			$requestParams["seller_confirm"] = $params["sellerConfirm"];
		}
		if ($params["receiverName"]) {
			$requestParams["receiver_name"] = $params["receiverName"];
		}
		if ($params["startCreated"]) {
			$requestParams["start_created"] = $params["startCreated"];
		}
		if ($params["endCreated"]) {
			$requestParams["end_created"] = $params["endCreated"];
		}
		if ($params["freightPayer"]) {
			$requestParams["freight_payer"] = $params["freightPayer"];
		}
		if ($params["type"]) {
			$requestParams["type"] = $params["type"];
		}
		if ($params["pageNo"]) {
			$requestParams["page_no"] = $params["pageNo"];
		}
		if ($params["pageSize"]) {
			$requestParams["page_size"] = $params["pageSize"];
		}

		$url = "/tool/erps/detail";
		$data = "";
		Db::beginTransaction();
		try {
			$price = self::_createErpOrder($params["user_id"], "LogisticsDetails");
			$erpService = new ErpService();
			$data = $erpService->sentPostRequest($url, $requestParams);
			$data["deduct_money"] = $price;
			Db::commit();
		} catch (\Exception $e) {
			Db::rollBack();
			CommonUtil::throwException([$e->getCode(), $e->getMessage()]);
		}
		return $data;
	}

	//线下物流发货
	public static function send()
	{
		$params = app("request")->all();
		$requestParams["shop_id"] = $params["shopId"];
		$requestParams["tid"] = $params["tid"];
		if ($params["subTid"]) {
			$requestParams["sub_tid"] = $params["subTid"];
		}
		if ($params["isSplit"]) {
			$requestParams["is_split"] = $params["isSplit"];
		}
		$requestParams["out_sid"] = $params["outSid"];
		$requestParams["company_code"] = $params["companyCode"];
		if ($params["senderId"]) {
			$requestParams["sender_id"] = $params["senderId"];
		}
		if ($params["cancelId"]) {
			$requestParams["cancel_id"] = $params["cancelId"];
		}
		if ($params["feature"]) {
			$requestParams["feature"] = $params["feature"];
		}
		if ($params["sellerIp"]) {
			$requestParams["seller_ip"] = $params["sellerIp"];
		}

		$url = "/tool/erps/send";
		$data = "";
		Db::beginTransaction();
		try {
			$price = self::_createErpOrder($params["user_id"], "LogisticsDelivery");
			$erpService = new ErpService();
			$data = $erpService->sentPostRequest($url, $requestParams);
			$data["deduct_money"] = $price;
			Db::commit();
		} catch (\Exception $e) {
			Db::rollBack();
			CommonUtil::throwException([$e->getCode(), $e->getMessage()]);
		}
		return $data;
	}

	//修改物流公司和运单
	public static function setResend()
	{
		$params = app("request")->all();
		$requestParams["shop_id"] = $params["shopId"];
		$requestParams["tid"] = $params["tid"];
		$requestParams["out_sid"] = $params["outSid"];
		$requestParams["company_code"] = $params["companyCode"];
		if ($params["subTid"]) {
			$requestParams["sub_tid"] = $params["subTid"];
		}
		if ($params["isSplit"]) {
			$requestParams["is_split"] = $params["isSplit"];
		}
		if ($params["senderId"]) {
			$requestParams["sender_id"] = $params["senderId"];
		}
		if ($params["cancelId"]) {
			$requestParams["cancel_id"] = $params["cancelId"];
		}
		if ($params["feature"]) {
			$requestParams["feature"] = $params["feature"];
		}
		if ($params["sellerIp"]) {
			$requestParams["seller_ip"] = $params["sellerIp"];
		}

		$url = "/tool/erps/resend";
		$data = "";
		Db::beginTransaction();
		try {
			$price = self::_createErpOrder($params["user_id"], "ModifyNumber");
			$erpService = new ErpService();
			$data = $erpService->sentPostRequest($url, $requestParams);
			$data["deduct_money"] = $price;
			Db::commit();
		} catch (\Exception $e) {
			Db::rollBack();
			CommonUtil::throwException([$e->getCode(), $e->getMessage()]);
		}
		return $data;
	}

	//对加密字段进行解密
	public static function decode()
	{
		$params = app("request")->all();
		$requestParams["shop_id"] = $params["shopId"];
		$requestParams["param"] = $params["param"];
		$count = count($params["param"]);
		$url = "/tool/erps/decode";
		$data = "";
		Db::beginTransaction();
		try {
			$price = self::_createErpOrder($params["user_id"], "decode", $count);
			$erpService = new ErpService();
			$data = $erpService->sentPostRequest($url, $requestParams);
			$data["deduct_money"] = $price;
			Db::commit();
		} catch (\Exception $e) {
			Db::rollBack();
			CommonUtil::throwException([$e->getCode(), $e->getMessage()]);
		}
		return $data;
	}


	/*********************************pdd相关*************************/
	//拼多多订购时间查询（拼）
	public static function pgetShopInfo()
	{
		$params = app("request")->all();
		$requestParams["owner_name"] = $params["owner_name"];
		if (!empty($params["edition"])) {
			$requestParams["type"] = $params["edition"];
		}
		$url = "/tool/erps/pget-shop-info";
		$data = "";
		Db::beginTransaction();
		try {
			$price = self::_createErpOrder($params["user_id"], "pgetShopInfo");
			$erpService = new ErpService();
			$data = $erpService->sentPostRequest($url, $requestParams);
			$data["deduct_money"] = $price;
			Db::commit();
		} catch (\Exception $e) {
			Db::rollBack();
			CommonUtil::throwException([$e->getCode(), $e->getMessage()]);
		}
		return $data;
	}

	///订单列表查询（拼)
	public static function pagingOrders()
	{
		$params = app("request")->all();
		$requestParams["owner_id"] = $params["owner_id"] ?? "l";
		$requestParams["type"] = $params["edition"] ?? "l";
		$requestParams["page"] = $params["page"] ?? "l";
		$requestParams["pageSize"] = $params["page_size"] ?? "l";
		$requestParams["start_time"] = $params["start_time"] ?? "l";
		$requestParams["end_time"] = $params["end_time"] ?? "l";
		$requestParams["remark"] = $params["remark"] ?? "l";
		$requestParams["remarkTag"] = $params["remark_tag"] ?? "l";
		$requestParams["remarkTagName"] = $params["remark_tag_name"] ?? "l";
		foreach ($requestParams as $k => $v) {
			if ($v == "l") {
				unset($requestParams[$k]);
			}
		}
		if (!empty($params["edition"])) {
			$requestParams["type"] = $params["edition"];
		}
		if (!empty($params["page"])) {
			$requestParams["page"] = $params["edipagepagetion"];
		}
		$url = "/tool/erps/paging-orders";
		$data = "";
		Db::beginTransaction();
		try {
			$price = self::_createErpOrder($params["user_id"], "pagingOrders");
			$erpService = new ErpService();
			$data = $erpService->sentPostRequest($url, $requestParams);
			$data["deduct_money"] = $price;
			Db::commit();
		} catch (\Exception $e) {
			Db::rollBack();
			CommonUtil::throwException([$e->getCode(), $e->getMessage()]);
		}
		return $data;
	}

	//通过订单号获取订单（拼）
	public static function mpagingOrders()
	{
		$params = app("request")->all();
		$requestParams["owner_id"] = $params["owner_id"];
		if (!empty($params["edition"])) {
			$requestParams["type"] = $params["edition"];
		}
		if (!empty($params["order_sns"])) {
			$requestParams["orderSns"] = $params["order_sns"];
		}
		$url = "/tool/erps/mpaging-orders";
		$data = "";
		Db::beginTransaction();
		try {
			$price = self::_createErpOrder($params["user_id"], "mpagingOrders");
			$erpService = new ErpService();
			$data = $erpService->sentPostRequest($url, $requestParams);
			$data["deduct_money"] = $price;
			Db::commit();
		} catch (\Exception $e) {
			Db::rollBack();
			CommonUtil::throwException([$e->getCode(), $e->getMessage()]);
		}
		return $data;
	}

	//非官方订单出单
	public static function getwaybillfreewn()
	{
		$params = app("request")->all();
		$requestParams["owner_id"] = $params["owner_id"];
		$requestParams["order_sn"] = $params["order_sn"];
		$requestParams["sender_name"] = $params["sender_name"];
		$requestParams["sender_mobile"] = $params["sender_mobile"];
		$requestParams["sender_province"] = $params["sender_province"];
		$requestParams["sender_city"] = $params["sender_city"];
		$requestParams["sender_town"] = $params["sender_town"];
		$requestParams["sender_detail"] = $params["sender_detail"];
		$requestParams["wp_code"] = $params["wp_code"] ?? "ing";
		$requestParams["waybill_type"] = $params["waybill_type"];
		$requestParams["shopCode"] = $params["shop_code"];
		$requestParams["receiver_province"] = $params["receiver_province"];
		$requestParams["receiver_city"] = $params["receiver_city"];
		$requestParams["receiver_town"] = $params["receiver_town"];
		$requestParams["receiver_address"] = $params["receiver_address"];
		$requestParams["receiver_phone"] = $params["receiver_phone"];
		$requestParams["receiver_name"] = $params["receiver_name"];
		if (!empty($params["edition"])) {
			$requestParams["type"] = $params["edition"];
		}
		$url = "/tool/erps/getwaybillfreewn";
		$data = "";
		Db::beginTransaction();
		try {
			$price = self::_createErpOrder($params["user_id"], "getwaybillfreewn");
			$erpService = new ErpService();
			$data = $erpService->sentPostRequest($url, $requestParams);
			$data["deduct_money"] = $price;
			Db::commit();
		} catch (\Exception $e) {
			Db::rollBack();
			CommonUtil::throwException([$e->getCode(), $e->getMessage()]);
		}
		return $data;
	}
	////平台出单（拼）
	public static function platformTheSingle()
	{
		$params = app("request")->all();
		$requestParams["owner_id"] = $params["owner_id"];
		$requestParams["sender_name"] = $params["sender_name"];
		$requestParams["sender_mobile"] = $params["sender_mobile"];
		$requestParams["province"] = $params["province"];
		$requestParams["city"] = $params["city"];
		$requestParams["district"] = $params["district"];
		$requestParams["detail"] = $params["detail"];
		$requestParams["wp_code"] = $params["wp_code"];
		$requestParams["waybill_type"] = $params["waybill_type"];
		$requestParams["shop_code"] = $params["shop_code"];
		$requestParams["order_sn_list"] = $params["order_sn_list"];
		if (!empty($params["edition"])) {
			$requestParams["type"] = $params["edition"];
		}
		$url = "/tool/erps/platform-the-single";
		$data = "";
		Db::beginTransaction();
		try {
			$price = self::_createErpOrder($params["user_id"], "platformTheSingle");
			$erpService = new ErpService();
			$data = $erpService->sentPostRequest($url, $requestParams);
			$data["deduct_money"] = $price;
			Db::commit();
		} catch (\Exception $e) {
			Db::rollBack();
			CommonUtil::throwException([$e->getCode(), $e->getMessage()]);
		}
		return $data;
	}
	//批量发货（拼）
	public static function pddShipFree()
	{
		$params = app("request")->all();
		$requestParams["params"] = $params["params"];
		$url = "/tool/erps/pdd-ship-free";
		$data = "";
		Db::beginTransaction();
		try {
			$price = self::_createErpOrder($params["user_id"], "pddShipFree");
			$erpService = new ErpService();
			$data = $erpService->sentPostRequest($url, $requestParams);
			$data["deduct_money"] = $price;
			Db::commit();
		} catch (\Exception $e) {
			Db::rollBack();
			CommonUtil::throwException([$e->getCode(), $e->getMessage()]);
		}
		return $data;
	}
	//获取店铺信息(拼)
	public static function pddShopInfo()
	{
		$params = app("request")->all();
		$requestParams["itemId"] = $params["item_id"];
		$url = "/tool/erps/pdd-shop-info";
		$data = "";
		Db::beginTransaction();
		try {
			$price = self::_createErpOrder($params["user_id"], "pddShopInfo");
			$erpService = new ErpService();
			$data = $erpService->sentGetRequest($url, $requestParams);
			$data["deduct_money"] = $price;
			Db::commit();
		} catch (\Exception $e) {
			Db::rollBack();
			CommonUtil::throwException([$e->getCode(), $e->getMessage()]);
		}
		return $data;
	}
	//拼获取退货地址(拼)
	public static function pddGetRefundAddress()
	{
		$params = app("request")->all();
		$requestParams["owner_id"] = $params["owner_id"];
		$url = "/tool/erps/get-refund-address";
		$data = "";
		Db::beginTransaction();
		try {
			$price = self::_createErpOrder($params["user_id"], "pddGetRefundAddress");
			$erpService = new ErpService();
			$data = $erpService->sentPostRequest($url, $requestParams);
			$data["deduct_money"] = $price;
			Db::commit();
		} catch (\Exception $e) {
			Db::rollBack();
			CommonUtil::throwException([$e->getCode(), $e->getMessage()]);
		}
		return $data;
	}
	//获取网点信息(拼)
	public static function pddGetNetworkInformation()
	{
		$params = app("request")->all();
		$requestParams["owner_id"] = $params["owner_id"];
		if(!empty($params["wp_code"])) {
			$requestParams["wp_code"] = $params["wp_code"];
		}
		$url = "/tool/erps/get-network-info";
		$data = "";
		Db::beginTransaction();
		try {
			$price = self::_createErpOrder($params["user_id"], "pddGetNetworkInformation");
			$erpService = new ErpService();
			$data = $erpService->sentPostRequest($url, $requestParams);
			$data["deduct_money"] = $price;
			Db::commit();
		} catch (\Exception $e) {
			Db::rollBack();
			CommonUtil::throwException([$e->getCode(), $e->getMessage()]);
		}
		return $data;
	}
	//单号回收(拼)（拼）
	public static function pddWaybillRecovery()
	{
		$params = app("request")->all();
		$requestParams["params"] = $params["params"];
		$url = "/tool/erps/cancel-waybill";
		$data = "";
		Db::beginTransaction();
		try {
			$price = self::_createErpOrder($params["user_id"], "pddWaybillRecovery");
			$erpService = new ErpService();
			$data = $erpService->sentPostRequest($url, $requestParams);
			$data["deduct_money"] = $price;
			Db::commit();
		} catch (\Exception $e) {
			Db::rollBack();
			CommonUtil::throwException([$e->getCode(), $e->getMessage()]);
		}
		return $data;
	}

	public static function pddSynchronizationOrder()
	{
		$params = app("request")->all();
		$requestParams["params"] = $params["params"];
		$url = "/tool/erps/sync-ordersn";
		$data = "";
		Db::beginTransaction();
		try {
			$price = self::_createErpOrder($params["user_id"], "pddSynchronizationOrder");
			$erpService = new ErpService();
			$data = $erpService->sentPostRequest($url, $requestParams);
			$data["deduct_money"] = $price;
			Db::commit();
		} catch (\Exception $e) {
			Db::rollBack();
			CommonUtil::throwException([$e->getCode(), $e->getMessage()]);
		}
		return $data;
	}
}
