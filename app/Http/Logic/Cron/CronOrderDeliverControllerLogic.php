<?php


namespace App\Http\Logic\Cron;


use App\Enums\CustomWarehouseEnum;
use App\Enums\TagEnum;
use App\Helper\CommonUtil;
use App\Http\Logic\BaseLogic;
use App\Http\Utils\LoggerFactoryUtil;
use App\Models\CustomWarehouseExpressModel;
use App\Models\CustomWarehouseModel;
use App\Models\ExpressProductModel;
use App\Models\OrderConsignee;
use App\Models\UserOrder;
use App\Models\UserShopModel;
use App\Models\UserShopOrderModel;
use App\Services\Erp\PddErpService;
use App\Services\Shop\JdShop;
use App\Services\Vtool\ErpService;
use Tool\ShanTaoTool\HttpCurl;
use Tool\ShanTaoTool\QiWeiTool;

class CronOrderDeliverControllerLogic extends BaseLogic
{
	public static function orderDeliver($package_id)
	{
		$package = OrderConsignee::query()->where("id", $package_id)->first();
		$order = UserOrder::query()->where("id", $package["order_id"])->first();
		if ($order->order_sent_type == 3) { // api下单自动发货
			$shop_id = $package["shop_id"];
			switch ($order->source) {
				case "taobao":
//					$req = self::orderDeliverTbV1($package_id);
					$req = self::orderDeliverTbV2($package_id);
					return $req;
				case "pdd":
//					$req = self::orderDeliverPddV1($package_id);
					$req = self::orderDeliverPddV2($package_id);
					return $req;
				default:
					return false;
			}
		}
		$shop_id = $package["shop_id"];
		$shop_info = UserShopModel::query()->where([
			"is_delete"=>0,
			"shop_id" => $shop_id,
			"user_id" => $order["user_id"]
		])->first();
		$shop_type = $shop_info["shop_type"];
		switch ($shop_type) {
			case "pdd":
				$req = self::orderDeliverPdd($package_id); // 通知发货
				$req1 = self::orderTagPdd($package_id); // 订单标记
				return $req;
			case "tb":
				$req = self::orderDeliverTb($package_id, $shop_info);
				$req1 = self::orderTagTb($package_id); // 订单标记
				return $req;
			case "ks":
				$req = self::orderDeliverKs($package_id, $shop_info);
				$req1 = self::orderTagKs($package_id); // 订单标记
				return $req;
			case "dy":
				$req = self::orderDeliverDy($package_id, $shop_info);
				$req1 = self::orderTagDy($package_id); // 订单标记
				return $req;
			case "jd":
				$req = self::orderDeliverJd($package_id, $shop_info);
				$req1 = self::orderTagJd($package_id); // 订单标记
				return $req;
			default:
				return false;
		}
	}

	/**
	 * @param $package_id
	 * @param $shop_info
	 * 京东发货
	 */
	public static function orderDeliverJd($package_id, $shop_info)
	{
		$package = OrderConsignee::query()->where("id", $package_id)->lockForUpdate()->first();
		$order = UserOrder::query()->where("id", $package["order_id"])->first();
		$product_id = $order["product_id"];
		$express_id = ExpressProductModel::query()->where("product_id", $product_id)->value("damaijia_express_id");
		$custom_warehouse_id = CustomWarehouseExpressModel::query()->where("express_id", $express_id)->value("custom_warehouse_id");
		$custom_warehouse_name = CustomWarehouseModel::query()->where("id", $custom_warehouse_id)->value("custom_warehouse_name");
		$express_code = CustomWarehouseEnum::JD_EXPRESS_MAP[$custom_warehouse_name];
		$order_sn = $package["ext_platform_order_sn"];
		$express_no = $package["express_no"];
		$url = "api/jos";
		$domain = env("JD_URL");
		$params = [
			"appKey"=>env("JD_APPKEY"),
			"access_token"=>$shop_info["access_token"],
			"method" => "jingdong.pop.order.shipment",
			"orderId" => $order_sn,
			"logiCoprId" => $express_code,
			'logiNo' => $express_no
		];
		$params["sign"] = JdShop::getSignature($params);
		$log = new LoggerFactoryUtil(CronOrderDeliverControllerLogic::class);
		$log->info("express_id" . $express_id);
		$log->info("custom_warehouse_id" . $custom_warehouse_id);
		$log->info("custom_warehouse_name" . $custom_warehouse_name);
		$log->info("express_code" . $express_code);
		$log->info("请求参数" . json_encode($params));
		$req = HttpCurl::postCurl($domain . $url, $params);
		$package->deliver_response = json_encode($req);
		$log->info("返回结果：" . json_encode($req));
		$log->info("请求接口：" . $domain . $url);
		$res = $req["jingdong_pop_order_shipment_responce"]["sopjosshipment_result"]["success"] ?? '';
		if ($res == true) {
			$package->deliver_status = 1;
		} else {
			$package->deliver_status = 2;
		}
		return $package->save();
	}
	/**
	 * @param $package_id
	 * @param $shop_info
	 * 京东打标记
	 */
	public static function orderTagJd($package_id, $shop_info)
	{
		$package = OrderConsignee::query()->where("id", $package_id)->first();
		$order_sn = $package["ext_platform_order_sn"];
		$shop_id = $package["shop_id"];
		$url = "api/jos";
		$domain = env("JD_URL");
		$tag_match_type = $package["tag_match_type"];
		// 抖音备注必填
		if ($tag_match_type !=3) {
			return false;
		}
		$tag_color =  TagEnum::JD_TAG[$package["tag_color"]];
		$params["appKey"] = env("JD_APPKEY");
		$params["access_token"] = $shop_info["access_token"];
		$params['method'] = $package["jingdong.pop.order.modifyVenderRemark"];
		$params['flag'] = $tag_color;
		$params['order_id'] = $order_sn;
		$params['remark'] = $package["tag_remark"];
		$params['sign'] = JdShop::getSignature($params);
		$log = new LoggerFactoryUtil(CronOrderDeliverControllerLogic::class);
		$log->info("请求地址" . $domain . $url);
		$log->info("参数：" . json_encode($params));
		$req = HttpCurl::postCurl($domain . $url, $params);
		$log->info("京东打标记返回参数：" . json_encode($req));
		$success = $req["jingdong_pop_order_modifyVenderRemark_response"]["modifyvenderremark_result"]["success"] ?? "";
		if($success == true) {
			$package->tag_remark_status = 1;
			$package->tag_color_status = 1;
		} else {
			$package->tag_remark_status = 2;
			$package->tag_color_status = 2;
		}
		$package->save();
		return $req;
	}
	// 抖音标记
	public static function orderTagDy($package_id)
	{
		$package = OrderConsignee::query()->where("id", $package_id)->first();
		$order_sn = $package["ext_platform_order_sn"];
		$shop_id = $package["shop_id"];
		$url = "/api/dy/tagOrder";
		$domain = env("DOUYIN_DOMAIN");
		$tag_match_type = $package["tag_match_type"];
		// 抖音备注必填
		if ($tag_match_type == 0 || $tag_match_type == 1) {
			return false;
		}
		$tag_color =  TagEnum::DY_TAG[$package["tag_color"]];
		$params["shop_id"] = $shop_id;
		$params["order_id"] = $order_sn;
		$params['remark'] = $package["tag_remark"];
		if($tag_match_type == 3){ // 匹配方式0不标记备注 1 标记旗帜 2添加备注 3旗帜加备注
			$params['star'] = $tag_color;
			$params['is_add_star'] = "true";
		}
		$log = new LoggerFactoryUtil(CronOrderDeliverControllerLogic::class);
		$log->info("请求地址" . $domain . $url);
		$log->info("参数：" . json_encode($params));
		$req = HttpCurl::postCurl($domain . $url, $params);
		$log->info("抖音打标记返回参数：" . json_encode($req));
		if($req["status"]) {
			$package->tag_remark_status = 1;
			if($tag_match_type == 3) {
				$package->tag_color_status = 1;
			}
		} else {
			$package->tag_remark_status = 2;
			if($tag_match_type == 3) {
				$package->tag_color_status = 2;
			}
		}
		$package->save();
//		dd($domain . $url, $params,$req);
		return $req;
	}
	
	public static function orderTagKs($package_id)
	{
		$package = OrderConsignee::query()->where("id", $package_id)->first();
		$order_sn = $package["ext_platform_order_sn"];
		$shop_id = $package["shop_id"];
		$url = "/api/v1/api/tagOrder";
		$domain = env("KUAISHOU_DOMAIN");
		$tag_match_type = $package["tag_match_type"];
		if ($tag_match_type == 0) {
			return false;
		}
		if ($tag_match_type == 1) {  //匹配方式1 旗帜 2备注 3旗帜加备注
			$params["tag"] = $package["tag_color"];
		}
		if ($tag_match_type == 2) {  //匹配方式1 旗帜 2备注 3旗帜加备注
			$params["remark"] = $package["tag_remark"];
		}
		if ($tag_match_type == 3) {  //匹配方式1 旗帜 2备注 3旗帜加备注
			$params["remark"] = $package["tag_remark"];
			$params["tag"] = $package["tag_color"];
		}
		$params["shop_id"] = $shop_id;
		$params["order_sn"] = $order_sn;
		$log = new LoggerFactoryUtil(CronOrderDeliverControllerLogic::class);
		$log->info("请求地址" . $domain . $url);
		$log->info("参数：" . json_encode($params));
		$req = HttpCurl::postCurl($domain . $url, $params);
		$log->info("打标记返回参数：" . json_encode($req));
		if(isset($req["code"]) && (200 == $req["code"])) {
			$package->tag_color_status =1;
			$package->tag_remark_status =1;
		} else {
			$package->tag_color_status =2;
			$package->tag_remark_status =2;
		}
		$package->save();
		return $req;
	}

	public static function orderDeliverKs($package_id, $shop_info)
	{
		$package = OrderConsignee::query()->where("id", $package_id)->lockForUpdate()->first();
		$order = UserOrder::query()->where("id", $package["order_id"])->first();
		$product_id = $order["product_id"];
		$express_id = ExpressProductModel::query()->where("product_id", $product_id)->value("damaijia_express_id");
		$custom_warehouse_id = CustomWarehouseExpressModel::query()->where("express_id", $express_id)->value("custom_warehouse_id");
		$custom_warehouse_name = CustomWarehouseModel::query()->where("id", $custom_warehouse_id)->value("custom_warehouse_name");
		$express_code = CustomWarehouseEnum::EXPRESS_MAP[$custom_warehouse_name];
		$order_sn = $package["ext_platform_order_sn"];
		$shop_id = $package["shop_id"];
		$callbackParams = $shop_info["callback_params"];
		$cp = json_decode($callbackParams, true);
		$express_no = $package["express_no"];
		$url = "/api/v1/api/send";
		$domain = env("KUAISHOU_DOMAIN");
//		$uid = env("AT_VTOOL_PROJECT_USER_ID");
//		$project_id = env("PROJECT_ID");
		$params = [
			"shop_id" => $shop_id,
			"order_id" => $order_sn,
			"express_no" => $express_no,
			'express_code' => $express_code,
		];
		$log = new LoggerFactoryUtil(CronOrderDeliverControllerLogic::class);
		$log->info("express_id" . $express_id);
		$log->info("custom_warehouse_id" . $custom_warehouse_id);
		$log->info("custom_warehouse_name" . $custom_warehouse_name);
		$log->info("express_code" . $express_code);
		$log->info("请求参数" . json_encode($params));
		$req = HttpCurl::postCurl($domain . $url, $params);
		$package->deliver_response = json_encode($req);
		$log->info("返回结果：" . json_encode($req));
		$log->info("请求接口：" . $domain . $url);
		if ($req["code"] == 200) {
			$package->deliver_status = 1;
		} else {
			$package->deliver_status = 2;
		}
		return $package->save();

	}
	public static function orderDeliverDy($package_id, $shop_info)
	{
		$package = OrderConsignee::query()->where("id", $package_id)->lockForUpdate()->first();
		$order = UserOrder::query()->where("id", $package["order_id"])->first();
		$product_id = $order["product_id"];
		$express_id = ExpressProductModel::query()->where("product_id", $product_id)->value("damaijia_express_id");
		$custom_warehouse_id = CustomWarehouseExpressModel::query()->where("express_id", $express_id)->value("custom_warehouse_id");
		$custom_warehouse_name = CustomWarehouseModel::query()->where("id", $custom_warehouse_id)->value("custom_warehouse_name");
		$express_code = CustomWarehouseEnum::DY_EXPRESS_MAP[$custom_warehouse_name];
		$order_sn = $package["ext_platform_order_sn"];
		$shop_id = $package["shop_id"];
		$express_no = $package["express_no"];
		$url = "/api/dy/orderDeliver";
		$domain = env("DOUYIN_DOMAIN");
		$params = [
			"shop_id" => $shop_id,
			"order_id" => $order_sn,
			"logistics_code" => $express_no,
			'company_code' => $express_code,
		];
		$log = new LoggerFactoryUtil(CronOrderDeliverControllerLogic::class);
		$log->info("express_id" . $express_id);
		$log->info("custom_warehouse_id" . $custom_warehouse_id);
		$log->info("custom_warehouse_name" . $custom_warehouse_name);
		$log->info("express_code" . $express_code);
		$log->info("抖音发货请求参数:" . json_encode($params));
		$req = HttpCurl::postCurl($domain . $url, $params);
		$package->deliver_response = json_encode($req);
		$log->info("抖音发货返回结果：" . json_encode($req));
		$log->info("请求接口：" . $domain . $url);
		if ($req["status"]) {
			$package->deliver_status = 1;
		} else {
			$package->deliver_status = 2;
		}
//		dd($req,$params,$domain . $url);
		return $package->save();

	}

	public static function orderTagTb($package_id)
	{
		$package = OrderConsignee::query()->where("id", $package_id)->first();
		$order = UserOrder::query()->where("id", $package["order_id"])->first();
		$order_sn = $package["ext_platform_order_sn"];
		$shop_id = $package["shop_id"];
		$url = "/api/damajia/erp/tool/tb/offlineMemo";
		$domain = env("QIANTAI_VTOOL");
		$shopInfo = UserShopModel::query()
			->where(["shop_id" => $shop_id, "shop_type" => "tb", "user_id" => $order["user_id"]])
			->first();
		$tag_match_type = $package["tag_match_type"];
		if (($tag_match_type == 0) || ($tag_match_type == 1)) {
			return false;
		}
		if ($tag_match_type == 2) {  //匹配方式1 旗帜 2备注 3旗帜加备注
			$params["memo"] = $package["tag_remark"];
		}
		if ($tag_match_type == 3) {  //匹配方式1 旗帜 2备注 3旗帜加备注
			$params["memo"] = $package["tag_remark"];
			$params["flag"] = TagEnum::TAG_ARRAY[$package["tag_color"]] ?? 0;
		}
		$params["shop_id"] = $shop_id;
		$params["tid"] = $order_sn;
		$params["vvtype"] = 4;
		$callbackParams = $shopInfo["callback_params"];
		$cp = json_decode($callbackParams, true);
		$params["sellernick"] = $cp["sellernick"] ?? "";
		$params["code"] = $cp["code"] ?? "";
		$params["tid"] = $order_sn;
		$log = new LoggerFactoryUtil(CronOrderDeliverControllerLogic::class);
		$log->info("请求地址" . $domain . $url);
		$log->info("参数：" . json_encode($params));
		$req = HttpCurl::postCurl($domain . $url, $params);
		$log->info("打标记返回参数：" . json_encode($req));
		if($req["status"]) {
			$package->tag_color_status = 1;
			$package->tag_remark_status = 1;
		} else {
			$package->tag_color_status = 2;
			$package->tag_remark_status = 2;
		}
		
		$package->save();
//		dd($domain . $url, $params,$req);
		return $req;
	}
	public static function orderDeliverTb($package_id, $shop_info)
	{
		$package = OrderConsignee::query()->where("id", $package_id)->lockForUpdate()->first();
		$order = UserOrder::query()->where("id", $package["order_id"])->first();
		$product_id = $order["product_id"];
		$express_id = ExpressProductModel::query()->where("product_id", $product_id)->value("damaijia_express_id");
		$custom_warehouse_id = CustomWarehouseExpressModel::query()->where("express_id", $express_id)->value("custom_warehouse_id");
		$custom_warehouse_name = CustomWarehouseModel::query()->where("id", $custom_warehouse_id)->value("custom_warehouse_name");
		$express_code = CustomWarehouseEnum::TB_EXPRESS_MAP[$custom_warehouse_name];
		$order_sn = $package["ext_platform_order_sn"];
		$shop_id = $package["shop_id"];
		$callbackParams = $shop_info["callback_params"];
		$cp = json_decode($callbackParams, true);
		$express_no = $package["express_no"];
		$url = "/api/damajia/erp/tool/tb/offlineSend";
		$domain = env("QIANTAI_VTOOL");
//		$uid = env("AT_VTOOL_PROJECT_USER_ID");
//		$project_id = env("PROJECT_ID");
		$params = [
			"shop_id" => $shop_id,
			"tid" => $order_sn,
			"out_sid" => $express_no,
			'company_code' => $express_code,
			'sellernick' => $cp["sellernick"],
			"code" => $cp["code"],
			"vvtype"=>4
		];
		$log = new LoggerFactoryUtil(CronOrderDeliverControllerLogic::class);
		$log->info("express_id" . $express_id);
		$log->info("custom_warehouse_id" . $custom_warehouse_id);
		$log->info("custom_warehouse_name" . $custom_warehouse_name);
		$log->info("express_code" . $express_code);
		$log->info("params" . json_encode($params));
		$req = HttpCurl::postCurl($domain . $url, $params);
		$log->info("返回结果" . json_encode($req));
		$package->deliver_response = json_encode($req);
		if ($req["code"] == 200) {
			$package->deliver_status = 1;
		} else {
			$package->deliver_status = 2;
		}
		return $package->save();

	}

	public static function orderDeliverTbV1($package_id)
	{
		$package = OrderConsignee::query()->where("id", $package_id)->lockForUpdate()->first();
		$order = UserOrder::query()->where("id", $package["order_id"])->first();
		$product_id = $order["product_id"];
		$express_id = ExpressProductModel::query()->where("product_id", $product_id)->value("damaijia_express_id");
		$custom_warehouse_id = CustomWarehouseExpressModel::query()->where("express_id", $express_id)->value("custom_warehouse_id");
		$custom_warehouse_name = CustomWarehouseModel::query()->where("id", $custom_warehouse_id)->value("custom_warehouse_name");
		$express_code = CustomWarehouseEnum::TB_EXPRESS_MAP[$custom_warehouse_name];
		$order_sn = $package["ext_platform_order_sn"];
		$shop_id = $package["shop_id"];
		$express_no = $package["express_no"];
		$url = "/tool/erps/send";;
		$params = [
			"shop_id" => $shop_id,
			"tid" => $order_sn,
			"out_sid" => $express_no,
			'company_code' => $express_code,
		];
		$erpService = new ErpService();
		try {
			$req = $erpService->sentPostRequest($url, $params);
			$package->deliver_status = 1;
		} catch (\Exception $e) {
			$package->deliver_status = 2;
		}

		$log = new LoggerFactoryUtil(CronOrderDeliverControllerLogic::class);
		$log->info("express_id" . $express_id);
		$log->info("custom_warehouse_id" . $custom_warehouse_id);
		$log->info("custom_warehouse_name" . $custom_warehouse_name);
		$log->info("express_code" . $express_code);
		$log->info("params" . json_encode($params));
		$log->info("返回结果" . json_encode($req));
		$package->deliver_response = json_encode($req);
		return $package->save();

	}

	public static function orderDeliverTbV2($package_id)
	{
		$package = OrderConsignee::query()->where("id", $package_id)->lockForUpdate()->first();
		$order = UserOrder::query()->where("id", $package["order_id"])->first();
		$product_id = $order["product_id"];
		$express_id = ExpressProductModel::query()->where("product_id", $product_id)->value("damaijia_express_id");
		$custom_warehouse_id = CustomWarehouseExpressModel::query()->where("express_id", $express_id)->value("custom_warehouse_id");
		$custom_warehouse_name = CustomWarehouseModel::query()->where("id", $custom_warehouse_id)->value("custom_warehouse_name");
		$express_code = CustomWarehouseEnum::TB_EXPRESS_MAP[$custom_warehouse_name];
		$order_sn = $package["ext_platform_order_sn"];
		$shop_id = $package["shop_id"];
		$express_no = $package["express_no"];
		$params = [
			"shop_id" => $shop_id,
			"tid" => $order_sn,
			"out_sid" => $express_no,
			'company_code' => $express_code,
			"uid" => $package["third_user_id"]
		];
		try {
			$baseUrl = env("QIANTAI_VTOOL");
			$url = "/api/v1/erp/tool/tb/confirmLogisticsOnlineVt";
			$data = HttpCurl::PostCurl($baseUrl . $url, $params);
			if (isset($data["status"]) && $data["status"]) {
				$package->deliver_status = 1;
			}
			$package->deliver_status = 2;
		} catch (\Exception $e) {
			$package->deliver_status = 2;
		}

		$log = new LoggerFactoryUtil(CronOrderDeliverControllerLogic::class);
		$log->info("express_id" . $express_id);
		$log->info("custom_warehouse_id" . $custom_warehouse_id);
		$log->info("custom_warehouse_name" . $custom_warehouse_name);
		$log->info("express_code" . $express_code);
		$log->info("params" . json_encode($params));
		$log->info("返回结果" . json_encode($data));
		$package->deliver_response = json_encode($data);
		return $package->save();

	}

	public static function orderDeliverPddV1($package_id)
	{
		$package = OrderConsignee::query()->where("id", $package_id)->lockForUpdate()->first();
		$order = UserOrder::query()->where("id", $package["order_id"])->first();
		$product_id = $order["product_id"];
		$express_id = ExpressProductModel::query()->where("product_id", $product_id)->value("damaijia_express_id");
		$custom_warehouse_id = CustomWarehouseExpressModel::query()->where("express_id", $express_id)->value("custom_warehouse_id");
		$custom_warehouse_name = CustomWarehouseModel::query()->where("id", $custom_warehouse_id)->value("custom_warehouse_name");
		$express_code = CustomWarehouseEnum::TB_EXPRESS_MAP[$custom_warehouse_name];
		$order_sn = $package["ext_platform_order_sn"];
		$shop_id = $package["shop_id"];
		$express_no = $package["express_no"];
		$url = "/tool/erps/pdd-ship-free";
		$params = [
			"owner_id" => $shop_id,
			"order_sn" => $order_sn,
			"waybill_code" => $express_no,
			'wp_code' => $express_code,
		];
		$erpService = new ErpService();
		try {
			$req = $erpService->sentPostRequest($url, ["params" => [$params]]);
			if (!empty($req["result"]) && $req["result"][0]["code"] == 1) {
				$package->deliver_status = 1;
			} else {
				$package->deliver_status = 2;
			}

		} catch (\Exception $e) {
			$package->deliver_status = 2;
		}

		$log = new LoggerFactoryUtil(CronOrderDeliverControllerLogic::class);
		$log->info("express_id" . $express_id);
		$log->info("custom_warehouse_id" . $custom_warehouse_id);
		$log->info("custom_warehouse_name" . $custom_warehouse_name);
		$log->info("express_code" . $express_code);
		$log->info("params" . json_encode($params));
		$log->info("返回结果" . json_encode($req));
		$package->deliver_response = json_encode($req);
		return $package->save();

	}

	// 拼多多erp发货
	public static function orderDeliverPddV2($package_id)
	{
		$package = OrderConsignee::query()->where("id", $package_id)->lockForUpdate()->first();
		$order = UserOrder::query()->where("id", $package["order_id"])->first();
		$product_id = $order["product_id"];
		$express_id = ExpressProductModel::query()->where("product_id", $product_id)->value("damaijia_express_id");
		$custom_warehouse_id = CustomWarehouseExpressModel::query()->where("express_id", $express_id)->value("custom_warehouse_id");
		$custom_warehouse_name = CustomWarehouseModel::query()->where("id", $custom_warehouse_id)->value("custom_warehouse_name");
		$logistics_id = CustomWarehouseEnum::PDD_EXPRESS_MAP[$custom_warehouse_name];
		$order_sn = $package["ext_platform_order_sn"];
		$shop_id = $package["shop_id"];
		$express_no = $package["express_no"];
		try {
			$params = [
				"shop_id"=>$shop_id,
				"params"=>[
					"logistics_id" => $logistics_id,
					"order_sn" => $order_sn,
					"tracking_number" => $express_no,
				]
			];
			$req = PddErpService::pddRequest($params,"pdd.logistics.online.send");
			if($req["status"]) {
				$package->deliver_status = 1;
			} else {
				$package->deliver_status = 2;
			}
			$package->deliver_status = 1;
		} catch (\Exception $e) {
			$package->deliver_status = 2;
		}
		$package->deliver_response = json_encode($req);
		return $package->save();

	}

	public static function orderDeliverPdd($package_id)
	{
		$package = OrderConsignee::query()->where("id", $package_id)->lockForUpdate()->first();
		$order = UserOrder::query()->where("id", $package["order_id"])->first();
		$product_id = $order["product_id"];
		$express_id = ExpressProductModel::query()->where("product_id", $product_id)->value("damaijia_express_id");
		$custom_warehouse_id = CustomWarehouseExpressModel::query()->where("express_id", $express_id)->value("custom_warehouse_id");
		$custom_warehouse_name = CustomWarehouseModel::query()->where("id", $custom_warehouse_id)->value("custom_warehouse_name");
		$express_code = CustomWarehouseEnum::EXPRESS_MAP[$custom_warehouse_name];
		$order_sn = $package["ext_platform_order_sn"];
		$shop_id = $package["shop_id"];
		$express_no = $package["express_no"];
		$url = "/api/v1/api/orderDeliver";
		$domain = env("PDD_ERP_DOMAIN");
		$uid = env("AT_VTOOL_PROJECT_USER_ID");
		$project_id = env("PROJECT_ID");
		$params = [
			"project_id" => $project_id,
			"uid" => md5($uid),
			"shop_id" => $shop_id,
			"order_sn" => $order_sn,
			"express_no" => $express_no,
			'express_code' => $express_code
		];
		$log = new LoggerFactoryUtil(CronOrderDeliverControllerLogic::class);
		$log->info("express_id" . $express_id);
		$log->info("custom_warehouse_id" . $custom_warehouse_id);
		$log->info("custom_warehouse_name" . $custom_warehouse_name);
		$log->info("express_code" . $express_code);
		$log->info("params" . json_encode($params));
		$req = HttpCurl::postCurl($domain . $url, $params);
		$package->deliver_response = json_encode($req);
		if ($req["code"] == 200) {
			$package->deliver_status = 1;
		} else {
			$package->deliver_status = 2;
		}
		return $package->save();

	}

	/**
	 * @param $package_id
	 * @return bool|mixed
	 * 订单标记
	 */
	public static function orderTagPdd($package_id)
	{
		$package = OrderConsignee::query()->where("id", $package_id)->first();
		$order_sn = $package["ext_platform_order_sn"];
		$shop_id = $package["shop_id"];
		$url = "/api/v1/api/tagOrder";
		$domain = env("PDD_ERP_DOMAIN");
		$tag_match_type = $package["tag_match_type"];
		if (($tag_match_type == 0) || ($tag_match_type == 1)) {
			return false;
		}
		// 拼多多只支持标记备注必填
		if ($tag_match_type == 2) {  //匹配方式1 旗帜 2备注 3旗帜加备注
			$params["remark"] = $package["tag_remark"];
		}
		if ($tag_match_type == 3) {  //匹配方式1 旗帜 2备注 3旗帜加备注
			$params["remark"] = $package["tag_remark"];
			$params["tag"] = $package["tag_color"];
		}
		$params["shop_id"] = $shop_id;
		$params["order_sn"] = $order_sn;
		$log = new LoggerFactoryUtil(CronOrderDeliverControllerLogic::class);
		$log->info("请求地址" . $domain . $url);
		$log->info("参数：" . json_encode($params));
		$req = HttpCurl::postCurl($domain . $url, $params);
		$log->info("打标记返回参数：" . json_encode($req));
		if($req["status"]) {
			$package->tag_color_status =1;
			$package->tag_remark_status =1;
		} else {
			$package->tag_color_status =2;
			$package->tag_remark_status =2;
		}
		$package->save();
		return $req;
	}
	public static function cronRequestPddShopOrder()
	{
		$params = app("request")->all();
		$created_at = date("Y-m-d");
		if (isset($params["created_at"])) {
			$created_at = $params["created_at"];
		}
		$shop_id_arr = UserShopModel::query()->pluck("shop_id")->toArray();
		$url = "/api/v1/api/listAppOrder";
		$domain = env("PDD_ERP_DOMAIN");
		$params = [
			"shop_id_arr" => $shop_id_arr,
			"created_at" => $created_at
		];
		$req = HttpCurl::postCurl($domain . $url, $params);
		if (!empty($req["data"])) {
			$data = $req["data"];

			foreach ($data as $k => $v) {
				$is_create = UserShopOrderModel::query()
					->where("order_shop_id", $v["order_shop_id"])
					->where("order_pay_at", $v["order_pay_at"])
					->where("shop_type", "pdd")
					->first();
				if ($is_create) {
					continue;
				}
				$map["order_shop_id"] = $v["order_shop_id"];
				$map["order_shop_name"] = $v["order_shop_name"];
				$map["order_sn"] = $v["order_sn"];
				$map["order_price"] = $v["order_price"];
				$map["order_pay_at"] = $v["order_pay_at"];
				$map["order_desc"] = $v["order_desc"];
				$map["order_refund_status"] = $v["order_refund_status"];
				$map["order_refund_time"] = $v["order_refund_time"];
				$map["order_time"] = $v["order_time"];
				$map["order_pay_status"] = $v["order_pay_status"];
				$map["shop_type"] = "pdd";
				$user_shop = UserShopModel::query()->where("shop_id", $v["order_shop_id"])->first();
				$map["site_id"] = $user_shop->site_id;
				$map["user_id"] = $user_shop->user_id;
				UserShopOrderModel::create($map);
			}
			return count($data);
		}
		return "success";
	}
}
