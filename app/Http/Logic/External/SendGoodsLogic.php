<?php


namespace App\Http\Logic\External;


use App\Helper\CommonUtil;
use App\Http\Logic\BaseLogic;
use App\Services\Vtool\ErpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SendGoodsLogic extends BaseLogic
{
//(淘)店铺后台发货
	public static function taoStoreBackstageDelivery()
	{
		$params = app("request")->all();
		$requestParams["sellerNick"] = $params["seller_nick"];
		$requestParams["orderNo"] = $params["order_no"];
		$requestParams["wpCode"] = $params["wp_code"];
		$requestParams["waybillNo"] = $params["waybill_no"];
		$url = "/tool/accounts/dcfso";
		$data = "";
		Db::beginTransaction();
		try {
			$price = ErpLogic::_createErpOrder($params["user_id"], "TaoStoreBackstageDelivery");
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

	//(淘)备注标旗(批量)
	public static function taoNoteFlagBatch()
	{
		$params = app("request")->all();
		$requestParams["sellerNick"] = $params["seller_nick"];
		$requestParams["param"] = $params["params"];
		$url = "/tool/accounts/bcro";
		$data = "";
		Db::beginTransaction();
		try {
			$price = ErpLogic::_createErpOrder($params["user_id"], "TaoNoteFlagBatch");
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

	//(淘)获取订单信息
	public static function taoToObtainOrderInformation()
	{
		$params = app("request")->all();
		$requestParams["sellerNick"] = $params["seller_nick"];
		$requestParams["orderNo"] = $params["order_no"];
		$url = "/tool/accounts/gcgo";
		$data = "";
		Db::beginTransaction();
		try {
			$price = ErpLogic::_createErpOrder($params["user_id"], "TaoToObtainOrderInformation");
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

	//(淘)获取订单(待发货)
	public static function taoGetTheOrderForShipment()
	{
		$params = app("request")->all();
		$requestParams["sellerNick"] = $params["seller_nick"];
		$requestParams["ps"] = $params['page_size'] ?? 10;
		$requestParams["pi"] = $params['page'] ?? 1;
		$requestParams["startTime"] = $params['start_time'];
		$requestParams["endTime"] = $params['end_time'];
		$url = "/tool/accounts/gcso";
		$data = "";
		Db::beginTransaction();
		try {
			$price = ErpLogic::_createErpOrder($params["user_id"], "TaoGetTheOrderForShipment");
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

	//(淘)获取面单(智能)
	public static function taoForSurfaceSingleZn()
	{
		$params = app("request")->all();
		$requestParams["sellerNick"] = $params["seller_nick"];
		$requestParams["orderNo"] = $params['order_no'];
		$requestParams["senderProvince"] = $params['sender_province'];
		$requestParams["senderCity"] = $params['sender_city'];
		$requestParams["senderDistrict"] = $params['sender_district'];
		$requestParams["senderAddress"] = $params['sender_address'];
		$requestParams["senderName"] = $params['sender_name'];
		$requestParams["senderPhone"] = $params['sender_phone'];
		$requestParams["templateUrl"] = $params['template_url'];
		$requestParams["shopCode"] = $params['shop_code'];
		$requestParams["wpCode"] = $params['wp_code'];
		$requestParams["goodsInfoList"] = $params['goods_info_map'];
		$url = "/tool/accounts/gcswb";
		$data = "";
		Db::beginTransaction();
		try {
			$price = ErpLogic::_createErpOrder($params["user_id"], "TaoForSurfaceSingleZn");
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

	//(淘)获取面单(非智能)
	public static function taoForSurfaceSingleFzn()
	{
		$params = app("request")->all();
		$requestParams["sellerNick"] = $params["seller_nick"];
		$requestParams["orderNo"] = $params['order_no'];
		$requestParams["receiverProvince"] = $params['receiver_province'];
		$requestParams["receiverCity"] = $params['receiver_city'];
		$requestParams["receiverDistrict"] = $params['receiver_district'];
		$requestParams["receiverAddress"] = $params['receiver_address'];
		$requestParams["receiverName"] = $params['receiver_name'];
		$requestParams["receiverPhone"] = $params['receiver_phone'];
		$requestParams["senderProvince"] = $params['sender_province'];
		$requestParams["senderCity"] = $params['sender_city'];
		$requestParams["senderDistrict"] = $params['sender_district'];
		$requestParams["senderAddress"] = $params['sender_address'];
		$requestParams["senderName"] = $params['sender_name'];
		$requestParams["senderPhone"] = $params['sender_phone'];
		$requestParams["templateUrl"] = $params['template_url'];
		$requestParams["shopCode"] = $params['shop_code'];
		$requestParams["wpCode"] = $params['wp_code'];
		$requestParams["goodsInfoList"] = $params['goods_info_map'];
		$url = "/tool/accounts/gcwb";
		$data = "";
		Db::beginTransaction();
		try {
			$price = ErpLogic::_createErpOrder($params["user_id"], "TaoForSurfaceSingleFzn");
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

	//(拼)待发订单
	public static function pinToOrder()
	{
		$params = app("request")->all();
		$requestParams["sellerId"] = $params["seller_id"];
		$requestParams["pageSize"] = $params['page_size'] ?? 10;
		$requestParams["pageIndex"] = $params['page'] ?? 1;
		$requestParams["orderNoStr"] = $requestParams["order_no_str"];
		$requestParams["endTime"] = $params['end_time'];
		$requestParams["startTime"] = $params['start_time'];
		$requestParams["remark"] = $params['remark'];
		foreach ($requestParams as $k => $v) {
			if (empty($v)) {
				unset($requestParams[$k]);
			}
		}
		$url = "/tool/accounts/gpso";
		$data = "";
		Db::beginTransaction();
		try {
			$price = ErpLogic::_createErpOrder($params["user_id"], "PinToOrder");
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

	//(拼)获取面单(非智能)
	public static function pinForSurfaceSingleFzn()
	{
		$params = app("request")->all();
		$requestParams["orderNo"] = $params['order_no'];
		$requestParams["receiverProvince"] = $params['receiver_province'];
		$requestParams["receiverCity"] = $params['receiver_city'];
		$requestParams["receiverDistrict"] = $params['receiver_district'];
		$requestParams["receiverAddress"] = $params['receiver_address'];
		$requestParams["receiverName"] = $params['receiver_name'];
		$requestParams["receiverPhone"] = $params['receiver_phone'];
		$requestParams["senderProvince"] = $params['sender_province'];
		$requestParams["senderCity"] = $params['sender_city'];
		$requestParams["senderDistrict"] = $params['sender_district'];
		$requestParams["senderAddress"] = $params['sender_address'];
		$requestParams["senderName"] = $params['sender_name'];
		$requestParams["senderPhone"] = $params['sender_phone'];
		$requestParams["templateUrl"] = $params['template_url'];
		$requestParams["shopCode"] = $params['shop_code'];
		$requestParams["wpCode"] = $params['wp_code'];
		$requestParams["waybillSellerNick"] = $params['waybill_seller_nick'];

		$requestParams["goodsInfoList"] = $params['goods_info_map'];
		$url = "/tool/accounts/gpwb";
		$data = "";
		Db::beginTransaction();
		try {
			$price = ErpLogic::_createErpOrder($params["user_id"], "PinForSurfaceSingleFzn");
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

	//(拼)获取面单(智能)
	public static function pinForSurfaceSingleZn()
	{
		$params = app("request")->all();
		$requestParams["orderNo"] = $params['order_no'];
		$requestParams["senderProvince"] = $params['sender_province'];
		$requestParams["senderCity"] = $params['sender_city'];
		$requestParams["senderDistrict"] = $params['sender_district'];
		$requestParams["senderAddress"] = $params['sender_address'];
		$requestParams["senderName"] = $params['sender_name'];
		$requestParams["senderPhone"] = $params['sender_phone'];
		$requestParams["templateUrl"] = $params['template_url'];
		$requestParams["shopCode"] = $params['shop_code'];
		$requestParams["wpCode"] = $params['wp_code'];
		$requestParams["sellerId"] = $params['seller_id'];
		$requestParams["waybillSellerId"] = $params['waybill_seller_id'];
		$requestParams["goodsInfoList"] = $params['goods_info_list'];
		$url = "/tool/accounts/gcswb";
		$data = "";
		Db::beginTransaction();
		try {
			$price = ErpLogic::_createErpOrder($params["user_id"], "PinForSurfaceSingleZn");
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

	//(拼)根据订单号同步
	public static function pinSynchronization()
	{
		$params = app("request")->all();
		$requestParams["orderSns"] = $params["order_sns"];
		$requestParams["ownerId"] = $params["owner_id"];
		$url = "/tool/accounts/syno";
		$data = "";
		Db::beginTransaction();
		try {
			$price = ErpLogic::_createErpOrder($params["user_id"], "PinSynchronization");
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

	//(拼)自动发货
	public static function pinAutomaticDelivery()
	{
		$params = app("request")->all();
		$requestParams["orderNo"] = $params["order_no"];
		$requestParams["sellerId"] = $params["seller_id"];
		$requestParams["wpCode"] = $params["wp_code"];
		$requestParams["waybillNo"] = $params["waybill_no"];
		$url = "/tool/accounts/dpfso";
		$data = "";
		Db::beginTransaction();
		try {
			$price = ErpLogic::_createErpOrder($params["user_id"], "PinAutomaticDelivery");
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

	//(淘)子订单发货
	public static function suborderDelivery()
	{
		$params = app("request")->all();
		$requestParams["sellerNick"] = $params["seller_nick"];
		$requestParams["orderNo"] = $params["order_no"];
		$requestParams["wpCode"] = $params["wp_code"];
		$requestParams["waybillNo"] = $params["waybill_no"];
		if (!empty($params["sub_tids"])) {
			$requestParams["subTids"] = $params["sub_tids"];
		}
		$url = "/tool/accounts/subsend";
		$data = "";
		Db::beginTransaction();
		try {
			$price = ErpLogic::_createErpOrder($params["user_id"], "SuborderDelivery");
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
//(淘)获取商家订购时长
	public static function taoAccessToTheMerchantOrderTime()
	{
		$params = app("request")->all();
		$requestParams["sellerNick"] = $params["seller_nick"];
		$url = "/tool/accounts/vcsi";
		$data = "";
		Db::beginTransaction();
		try {
			$price = ErpLogic::_createErpOrder($params["user_id"], "TaoAccessToTheMerchantOrderTime");
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
	//(拼)获取订购时长
	public static function pinAccessToTheMerchantOrderTime()
	{
		$params = app("request")->all();
		$requestParams["sellerNick"] = $params["seller_nick"];
		$url = "/tool/accounts/vpsi";
		$data = "";
		Db::beginTransaction();
		try {
			$price = ErpLogic::_createErpOrder($params["user_id"], "PinAccessToTheMerchantOrderTime");
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
