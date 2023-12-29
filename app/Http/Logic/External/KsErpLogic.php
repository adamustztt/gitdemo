<?php


namespace App\Http\Logic\External;


use App\Http\Logic\BaseLogic;
use App\Http\Utils\BaseUtil;
use App\Services\Erp\KuaiShouErpService;
use App\Services\Vtool\ErpService;
use Illuminate\Support\Facades\DB;

class KsErpLogic extends BaseLogic
{
	public static function requestKsErp($code)
	{
		$params = app("request")->all();
		Db::beginTransaction();
		try {
			$price = ErpLogic::_createErpOrder($params["user_id"], $code);
			$data = KuaiShouErpService::ksRequest($params,$code);
			$data["deduct_money"] = $price;
			Db::commit();
		} catch (\Exception $e) {
			Db::rollBack();
			throw $e;
		}
		$data = BaseUtil::parseArrayToLine($data);
		return $data;
	}
//	public static function ksSeller()
//	{
//		$params = app("request")->all();
//		Db::beginTransaction();
//		try {
//			$price = ErpLogic::_createErpOrder($params["user_id"], "ksSeller");
//			$function = "open.service.market.buyer.service.info";
//			$data = KuaiShouErpService::ksRequest($params,$function,"get");
//			$data["deduct_money"] = $price;
//			Db::commit();
//		} catch (\Exception $e) {
//			Db::rollBack();
//			throw $e;
//		}
//		$data = BaseUtil::parseArrayToLine($data);
//		return $data;
//	}
//	//获取订单列表(游标方式)
//	public static function ksPcursorList()
//	{
//		$params = app("request")->all();
//		Db::beginTransaction();
//		try {
//			$price = ErpLogic::_createErpOrder($params["user_id"], "ksPcursorList");
//			$function = "open.order.cursor.list";
//			$data = KuaiShouErpService::ksRequest($params,$function,"get");
//			$data["deduct_money"] = $price;
//			Db::commit();
//		} catch (\Exception $e) {
//			Db::rollBack();
//			throw $e;
//		}
//		$data = BaseUtil::parseArrayToLine($data);
//		return $data;
//	}
//	public static function ksDetail()
//	{
//		$params = app("request")->all();
//		Db::beginTransaction();
//		try {
//			$price = ErpLogic::_createErpOrder($params["user_id"], "ksDetail");
//			$function = "open.order.detail";
//			$data = KuaiShouErpService::ksRequest($params,$function,"get");
//			$data["deduct_money"] = $price;
//			Db::commit();
//		} catch (\Exception $e) {
//			Db::rollBack();
//			throw $e;
//		}
//		$data = BaseUtil::parseArrayToLine($data);
//		return $data;
//	}
//	//获取订单列表(游标方式)
//	public static function ksCpsList()
//	{
//		$params = app("request")->all();
//		Db::beginTransaction();
//		try {
//			$price = ErpLogic::_createErpOrder($params["user_id"], "ksCpsList");
//			$function = "open.seller.order.cps.list";
//			$data = KuaiShouErpService::ksRequest($params,$function,"get");
//			$data["deduct_money"] = $price;
//			Db::commit();
//		} catch (\Exception $e) {
//			Db::rollBack();
//			throw $e;
//		}
//		$data = BaseUtil::parseArrayToLine($data);
//		return $data;
//	}
//	//获取分销订单详情
//	public static function ksCpsDetail()
//	{
//		$params = app("request")->all();
//		Db::beginTransaction();
//		try {
//			$price = ErpLogic::_createErpOrder($params["user_id"], "ksCpsDetail");
//			$function = "open.seller.order.cps.detail";
//			$data = KuaiShouErpService::ksRequest($params,$function,"get");
//			$data["deduct_money"] = $price;
//			Db::commit();
//		} catch (\Exception $e) {
//			Db::rollBack();
//			throw $e;
//		}
//		$data = BaseUtil::parseArrayToLine($data);
//		return $data;
//	}
//	//修改订单规格
//	public static function ksSkuUpdate()
//	{
//		$params = app("request")->all();
//		Db::beginTransaction();
//		try {
//			$price = ErpLogic::_createErpOrder($params["user_id"], "ksSkuUpdate");
//			$function = "open.seller.order.sku.update";
//			$data = KuaiShouErpService::ksRequest($params,$function,"post");
//			$data["deduct_money"] = $price;
//			Db::commit();
//		} catch (\Exception $e) {
//			Db::rollBack();
//			throw $e;
//		}
//		$data = BaseUtil::parseArrayToLine($data);
//		return $data;
//	}
//	//订单发货
//	public static function ksGoodsDeliver()
//	{
//		$params = app("request")->all();
//		Db::beginTransaction();
//		try {
//			$price = ErpLogic::_createErpOrder($params["user_id"], "ksGoodsDeliver");
//			$function = "open.seller.order.goods.deliver";
//			$data = KuaiShouErpService::ksRequest($params,$function,"post");
//			$data["deduct_money"] = $price;
//			Db::commit();
//		} catch (\Exception $e) {
//			Db::rollBack();
//			throw $e;
//		}
//		$data = BaseUtil::parseArrayToLine($data);
//		return $data;
//	}
//	//物流更新
//	public static function ksLogisticsUpdate()
//	{
//		$params = app("request")->all();
//		Db::beginTransaction();
//		try {
//			$price = ErpLogic::_createErpOrder($params["user_id"], "ksLogisticsUpdate");
//			$function = "open.seller.order.logistics.update";
//			$data = KuaiShouErpService::ksRequest($params,$function,"post");
//			$data["deduct_money"] = $price;
//			Db::commit();
//		} catch (\Exception $e) {
//			Db::rollBack();
//			throw $e;
//		}
//		$data = BaseUtil::parseArrayToLine($data);
//		return $data;
//	}
//	//添加备注
//	public static function ksNoteAdd()
//	{
//		$params = app("request")->all();
//		Db::beginTransaction();
//		try {
//			$price = ErpLogic::_createErpOrder($params["user_id"], "ksNoteAdd");
//			$function = "open.seller.order.note.add";
//			$data = KuaiShouErpService::ksRequest($params,$function,"post");
//			$data["deduct_money"] = $price;
//			Db::commit();
//		} catch (\Exception $e) {
//			Db::rollBack();
//			throw $e;
//		}
//		$data = BaseUtil::parseArrayToLine($data);
//		return $data;
//	}
//	//关闭订单
//	public static function ksOrderClose()
//	{
//		$params = app("request")->all();
//		Db::beginTransaction();
//		try {
//			$price = ErpLogic::_createErpOrder($params["user_id"], "ksOrderClose");
//			$function = "open.seller.order.close";
//			$data = KuaiShouErpService::ksRequest($params,$function,"post");
//			$data["deduct_money"] = $price;
//			Db::commit();
//		} catch (\Exception $e) {
//			Db::rollBack();
//			throw $e;
//		}
//		$data = BaseUtil::parseArrayToLine($data);
//		return $data;
//	}
//	//售后单列表(游标方式)
//	public static function ksRefundPcursor()
//	{
//		$params = app("request")->all();
//		Db::beginTransaction();
//		try {
//			$price = ErpLogic::_createErpOrder($params["user_id"], "ksRefundPcursor");
//			$function = "open.seller.order.refund.pcursor.list";
//			$data = KuaiShouErpService::ksRequest($params,$function,"get");
//			$data["deduct_money"] = $price;
//			Db::commit();
//		} catch (\Exception $e) {
//			Db::rollBack();
//			throw $e;
//		}
//		$data = BaseUtil::parseArrayToLine($data);
//		return $data;
//	}
//	//售后单详情
//	public static function ksRefundDetail()
//	{
//		$params = app("request")->all();
//		Db::beginTransaction();
//		try {
//			$price = ErpLogic::_createErpOrder($params["user_id"], "ksRefundDetail");
//			$function = "open.seller.order.refund.detail";
//			$data = KuaiShouErpService::ksRequest($params,$function,"get");
//			$data["deduct_money"] = $price;
//			Db::commit();
//		} catch (\Exception $e) {
//			Db::rollBack();
//			throw $e;
//		}
//		$data = BaseUtil::parseArrayToLine($data);
//		return $data;
//	}
//	//商家同意退货
//	public static function ksReturngoodsApprove()
//	{
//		$params = app("request")->all();
//		Db::beginTransaction();
//		try {
//			$price = ErpLogic::_createErpOrder($params["user_id"], "ksReturngoodsApprove");
//			$function = "open.seller.order.refund.returngoods.approve";
//			$data = KuaiShouErpService::ksRequest($params,$function,"post");
//			$data["deduct_money"] = $price;
//			Db::commit();
//		} catch (\Exception $e) {
//			Db::rollBack();
//			throw $e;
//		}
//		$data = BaseUtil::parseArrayToLine($data);
//		return $data;
//	}
//	// 商家同意退款
//	public static function ksRefundApprove()
//	{
//		$params = app("request")->all();
//		Db::beginTransaction();
//		try {
//			$price = ErpLogic::_createErpOrder($params["user_id"], "ksRefundApprove");
//			$function = "open.seller.order.refund.approve";
//			$data = KuaiShouErpService::ksRequest($params,$function,"post");
//			$data["deduct_money"] = $price;
//			Db::commit();
//		} catch (\Exception $e) {
//			Db::rollBack();
//			throw $e;
//		}
//		$data = BaseUtil::parseArrayToLine($data);
//		return $data;
//	}
//	public static function ksRefundReject()
//	{
//		$params = app("request")->all();
//		Db::beginTransaction();
//		try {
//			$price = ErpLogic::_createErpOrder($params["user_id"], "ksRefundReject");
//			$function = "open.refund.reject";
//			$data = KuaiShouErpService::ksRequest($params,$function,"post");
//			$data["deduct_money"] = $price;
//			Db::commit();
//		} catch (\Exception $e) {
//			Db::rollBack();
//			throw $e;
//		}
//		$data = BaseUtil::parseArrayToLine($data);
//		return $data;
//	}
//	public static function ksServiceInfo()
//	{
//		$params = app("request")->all();
//		Db::beginTransaction();
//		try {
//			$price = ErpLogic::_createErpOrder($params["user_id"], "ksServiceInfo");
//			$function = "open.service.market.buyer.service.info";
//			$data = KuaiShouErpService::ksRequest($params,$function,"get");
//			$data["deduct_money"] = $price;
//			Db::commit();
//		} catch (\Exception $e) {
//			Db::rollBack();
//			throw $e;
//		}
//		$data = BaseUtil::parseArrayToLine($data);
//		return $data;
//	}
//	public static function ksNetworkInfo()
//	{
//		$params = app("request")->all();
//		Db::beginTransaction();
//		try {
//			$price = ErpLogic::_createErpOrder($params["user_id"], "ksNetworkInfo");
//			$function = "open.service.market.buyer.service.info";
//			$data = KuaiShouErpService::ksRequest($params,$function,"get");
//			$data["deduct_money"] = $price;
//			Db::commit();
//		} catch (\Exception $e) {
//			Db::rollBack();
//			throw $e;
//		}
//		$data = BaseUtil::parseArrayToLine($data);
//		return $data;
//	}
//	public static function kswaybillfreewn()
//	{
//		$params = app("request")->all();
//		$requestParams["owner_id"] = $params["owner_id"];
//		$requestParams["order_sn"] = $params["order_sn"];
//		$requestParams["sender_name"] = $params["sender_name"];
//		$requestParams["sender_mobile"] = $params["sender_mobile"];
//		$requestParams["sender_province"] = $params["sender_province"];
//		$requestParams["sender_city"] = $params["sender_city"];
//		$requestParams["sender_town"] = $params["sender_town"];
//		$requestParams["sender_detail"] = $params["sender_detail"];
//		$requestParams["waybill_type"] = $params["waybill_type"];
//		$requestParams["shopCode"] = $params["shopCode"];
//		$requestParams["receiver_province"] = $params["receiver_province"];
//		$requestParams["receiver_city"] = $params["receiver_city"];
//		$requestParams["receiver_town"] = $params["receiver_town"];
//		$requestParams["receiver_address"] = $params["receiver_address"];
//		$requestParams["receiver_phone"] = $params["receiver_phone"];
//		$requestParams["receiver_name"] = $params["receiver_name"];
//		$requestParams["template_url"] = $params["template_url"];
//		$requestParams["platform"] = $params["platform"];
//		$requestParams["template_url"] = $params["template_url"];
//		$requestParams["wp_code"] = $params["wp_code"];
//		if (!empty($params["edition"])) {
//			$requestParams["vvtype"] = $params["edition"];
//		}
//		$url = "/tool/erps/kswaybillfreewn";
//		Db::beginTransaction();
//		try {
//			$price = ErpLogic::_createErpOrder($params["user_id"], "kswaybillfreewn");
//			$erpService = new ErpService();
//			$data = $erpService->sentPostRequest($url, $requestParams);
//			$data["deduct_money"] = $price;
//			Db::commit();
//		} catch (\Exception $e) {
//			Db::rollBack();
//			throw $e;
//		}
//		$data = BaseUtil::parseArrayToLine($data);
//		return $data;
//	}
//	// 平台出单
//	public static function ksPlatformTheSingle()
//	{
//		$params = app("request")->all();
//		$requestParams["owner_id"] = $params["owner_id"];
//		$requestParams["shop_id"] = $params["shop_id"];
//		$requestParams["sender_name"] = $params["sender_name"];
//		$requestParams["sender_mobile"] = $params["sender_mobile"];
//		$requestParams["province"] = $params["province"];
//		$requestParams["city"] = $params["city"];
//		$requestParams["district"] = $params["district"];
//		$requestParams["detail"] = $params["detail"];
//		$requestParams["wp_code"] = $params["wp_code"];
//		$requestParams["waybill_type"] = $params["waybill_type"];
//		$requestParams["shop_code"] = $params["shop_code"];
//		$requestParams["template_url"] = $params["template_url"];
//		$requestParams["order_sn_list"] = $params["order_sn_list"];
//		$requestParams["platform"] = $params["platform"];
//		$requestParams["template_url"] = $params["template_url"];
//		if (!empty($params["edition"])) {
//			$requestParams["vvtype"] = $params["edition"];
//		}
//		$url = "/tool/erps/ks-platform-the-single";
//		Db::beginTransaction();
//		try {
//			$price = ErpLogic::_createErpOrder($params["user_id"], "ksPlatformTheSingle");
//			$erpService = new ErpService();
//			$data = $erpService->sentPostRequest($url, $requestParams);
//			$data["deduct_money"] = $price;
//			Db::commit();
//		} catch (\Exception $e) {
//			Db::rollBack();
//			throw $e;
//		}
//		$data = BaseUtil::parseArrayToLine($data);
//		return $data;
//	}
//	// 单号回收
//	public static function ksCancelWaybill()
//	{
//		$params = app("request")->all();
//		$requestParams["params"] = $params["params"];
//		if (!empty($params["edition"])) {
//			$requestParams["vvtype"] = $params["edition"];
//		}
//		$url = "/tool/erps/ks-cancel-waybill";
//		Db::beginTransaction();
//		try {
//			$price = ErpLogic::_createErpOrder($params["user_id"], "ksCancelWaybill");
//			$erpService = new ErpService();
//			$data = $erpService->sentPostRequest($url, $requestParams);
//			$data["deduct_money"] = $price;
//			Db::commit();
//		} catch (\Exception $e) {
//			Db::rollBack();
//			throw $e;
//		}
//		$data = BaseUtil::parseArrayToLine($data);
//		return $data;
//	}
}
