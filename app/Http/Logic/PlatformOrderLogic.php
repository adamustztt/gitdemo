<?php


namespace App\Http\Logic;


use App\Enums\ErrorEnum;
use App\Enums\OrderFromEnum;
use App\Enums\TagEnum;
use App\Helper\CommonUtil;
use App\Http\Controllers\BaseController;
use App\Http\Controllers\CartController;
use App\Http\Logic\Cron\CronOrderDeliverControllerLogic;
use App\Http\Utils\BaseUtil;
use App\Http\Utils\LoggerFactoryUtil;
use App\Http\Utils\PlatformOrderUtil;
use App\Models\BanCityModel;
use App\Models\ExpressModel;
use App\Models\ExpressProductModel;
use App\Models\OrderSyncTaskChildModel;
use App\Models\OrderSyncTaskDetailModel;
use App\Models\OrderSyncTaskModel;
use App\Models\Product;
use App\Models\Site;
use App\Models\SiteProduct;
use App\Models\UserLevelModel;
use App\Models\UserOrder;
use App\Models\UserProductProfit;
use App\Models\UserShopModel;
use App\Models\Warehouse;
use App\Services\Shop\JdShop;
use App\Services\Vtool\ErpService;
use Illuminate\Support\Facades\DB;
use Tool\ShanTaoTool\HttpCurl;
use Tool\ShanTaoTool\QiWeiTool;

class PlatformOrderLogic extends BaseLogic
{
	public static function getPlatformOrder()
	{
		$params = app("request")->all();
		$shop_type = $params["shop_type"];
		$shop_id = $params["shop_id"];
		$page = $params["page"] ?? 1;
		$product_id = $params["product_id"];
		$data = [];
		switch ($shop_type) {
			case "pdd":
				$data = self::getPddOrders($page, $shop_id);
				break;
			case "tb":
				$data = self::getTbOrders($page, $shop_id);
				break;
			case "ks":
				$data = self::getKsOrders($page, $shop_id);
				break;
			case "jd":
				$data = self::getJdOrders($page, $shop_id);
				break;
		}
		$list = $data["list"];
		$list = PlatformOrderUtil::checkPlatformOrder($list,$product_id);
		return[
			"total" => $data["total"],
			"list" => $list
		];
	}
	public static function getJdOrders($page, $shop_id)
	{
		$url = "api/jos";
		$domain = env("JD_URL");
		$params["appKey"] = env("JD_APPKEY");
		$user_id = BaseController::getUserId();
		$access_token = UserShopModel::query()->where(["shop_id"=>$shop_id,"user_id"=>$user_id])->value("access_token");
		$params["access_token"] = $access_token;
		$params["start_date"] = date("Y-m-d H:i:s",strtotime(time()-30*24*3600));
		$params["end_date"] = date("Y-m-d H:i:s");
		$params["order_state"] = "WAIT_SELLER_STOCK_OUT ";
		$params["optional_fields"] = "orderId,originalConsigneeInfo,orderRemark";
		$params["page"] = $page;
		$params["isAutoDecrypt"] = 1;
		$params["page_size"] = 50;
		$params["sign"] = JdShop::getSignature($params);
		$log = new LoggerFactoryUtil(PlatformOrderLogic::class);
		$log->info("请求地址" . $domain . $url);
		$log->info("参数：" . json_encode($params));
		$req = HttpCurl::postCurl($domain . $url, $params);
		$log->info("京东：" . json_encode($req));
		$list = [];
		$reqData = $req["jingdong_pop_order_search_responce"]["searchorderinfo_result"]["orderInfoList"];
		$count = $req["jingdong_pop_order_search_responce"]["searchorderinfo_result"];
		foreach ($reqData as $k => $v) {
			$map["ext_platform_order_sn"] = $v["orderId"];
			$map["consignee"] = $v["originalConsigneeInfo"]["fullname"];
			$map["consignee_mask"] = $v["originalConsigneeInfo"]["fullname"];
			$map["mobile"] = $v["originalConsigneeInfo"]["mobile"];
			$map["mobile_mask"] = $v["originalConsigneeInfo"]["mobile"];
			$map["address"] = $v["originalConsigneeInfo"]["fullAddress"];
			$map["address_mask"] = $v["originalConsigneeInfo"]["fullAddress"];
			$map["province"] =  $v["originalConsigneeInfo"]["fullAddress"];
			$map["city"] =  $v["originalConsigneeInfo"]["city"];
			$map["district"] =  $v["originalConsigneeInfo"]["town"];
			$map["tag"] = "";
			$map["seller_memo"] = $v["orderRemark"];
			$map["oaid"] = "";
			$list[] = $map;
		}
		return [
			"total" => $count,
			"list" => $list
		];
	}
	public static function getPddOrders($page, $shop_id)
	{
		$url = "/api/v1/api/unsend/orderlist";
		$domain = env("PDD_ERP_DOMAIN");
		$params["page"] = $page;
		$params["shop_id"] = $shop_id;
		$log = new LoggerFactoryUtil(PlatformOrderLogic::class);
		$log->info("请求地址" . $domain . $url);
		$log->info("参数：" . json_encode($params));
		$req = HttpCurl::postCurl($domain . $url, $params);
		$log->info("拼多多：" . json_encode($req));
		$list = [];
		$reqData = $req["data"]["list"];
		$count = $req["data"]["total"];
		foreach ($reqData as $k => $v) {
			$map["ext_platform_order_sn"] = $v["order_sn"];
			$map["consignee"] = $v["receiver_name"];
			$map["consignee_mask"] = $v["receiver_name_mask"];
			$map["mobile"] = $v["receiver_phone"];
			$map["mobile_mask"] = $v["receiver_phone_mask"];
			$map["address"] = $v["address"];
			$map["address_mask"] = $v["receiver_address_mask"];
			$map["province"] = $v["province"];
			$map["city"] = $v["city"];
			$map["district"] = $v["town"];
			$map["tag"] = TagEnum::PDD_TAG[$v["remark_tag"]] ?? "";
			$map["seller_memo"] = $v["remark"];
			$map["oaid"] = "";
			$list[] = $map;
		}
		return [
			"total" => $count,
			"list" => $list
		];
	}

	public static function getTbOrders($page, $shop_id,$day='')
	{
		$url = "/api/v1/erp/tool/tb/getOrderListVt";
		$domain = env("QIANTAI_VTOOL");
		$params["fields"] = "tid,seller_nick, buyer_nick, title, type, created, tid, seller_rate,buyer_flag,buyer_rate,status,seller_memo, num, price, buyer_alipay_no, receiver_name, receiver_state, receiver_city,receiver_district, receiver_address, receiver_zip, receiver_mobile, receiver_phone,seller_flag, seller_alipay_no,seller_mobile, seller_phone, seller_name,orders,oid,service_orders";
		$params["page"] = $page;
		$params["status"] = "WAIT_SELLER_SEND_GOODS";
		$params["page_size"] = BaseUtil::platformOrderNum();
		$params["shop_id"] = $shop_id;
		$vvt = UserShopModel::query()->where("shop_id",$shop_id)->value("version_type");
		$params["vvtype"]=$vvt;
		if(!empty($day)) {
			$params['start_created'] = date("Y-m-d H:i:s", strtotime("-" . $day . " day"));
		}
		$params["uid"] = env("AT_VTOOL_PROJECT_USER_ID");
		$log = new LoggerFactoryUtil(PlatformOrderLogic::class);
		$log->info("请求地址" . $domain . $url);
		$log->info("参数：" . json_encode($params));
		$req = HttpCurl::postCurl($domain . $url, $params);
		$log->info("vt淘宝：" . json_encode($req));
		$list = [];
		$reqData = $req["data"]["trades"]["trade"] ?? [];
		foreach ($reqData as $k => $v) {
			$map["ext_platform_order_sn"] = $v["tid"];
			$map["consignee"] = $v["receiver_name"] ?? "";
			$map["consignee_mask"] = "";
			$map["mobile"] = $v["receiver_mobile"] ?? "";
			$map["mobile_mask"] = "";
			$map["address"] = $v["receiver_address"] ?? "";
			$map["address_mask"] = "";
			$map["province"] = $v["receiver_state"] ?? "";
			$map["city"] = $v["receiver_city"] ?? "";
			$map["district"] = $v["receiver_district"] ?? "";
			$map["tag"] = TagEnum::TB_TAG[$v["seller_flag"]] ?? "";
			$map["seller_memo"] = $v["seller_memo"] ?? "";
			$map["oaid"] = $v["oaid"] ?? "";
			$list[] = $map;
		}
		return [
			"total" => $req["data"]["total_results"] ?? 0,
			"list" => $list
		];
	}

	public static function getKsOrders($page, $shop_id)
	{
		$url = "/api/v1/api/ks/unsend/order";
		$domain = env("KUAISHOU_DOMAIN");
		$params["page"] = $page;
		$params["shop_id"] = $shop_id;
		$log = new LoggerFactoryUtil(PlatformOrderLogic::class);
		$log->info("请求地址" . $domain . $url);
		$log->info("参数：" . json_encode($params));
		$req = HttpCurl::postCurl($domain . $url, $params);
		$log->info("快手：" . json_encode($req));
		$list = [];
		$reqData = $req["data"]["list"];
		$count = $req["data"]["total"];
		foreach ($reqData as $k => $v) {
			$map["ext_platform_order_sn"] = $v["oid"];
			$map["consignee"] = $v["address_consignee"];
			$map["consignee_mask"] = "";
			$map["mobile"] = "mobile";
			$map["mobile_mask"] = "";
			$map["address"] = $v["address"];
			$map["address_mask"] = "";
			$map["province"] = $v["province"];
			$map["city"] = $v["city"];
			$map["district"] = $v["district"];
			$map["tag"] = TagEnum::KS_TAG[$v["flag_tag_code"]] ?? "";
			$map["seller_memo"] = $v["remark"];
			$map["oaid"] = "";
			$list[] = $map;
		}
		return [
			"total" => $count,
			"list" => $list
		];
	}

	// 密文下单
	public static function entryCreateOrder()
	{
		$params = app("request")->all();
		$site_id = BaseController::getSiteId();
		$siteInfo = Site::query()->where("id", $site_id)->first();
		$site_user_id = $siteInfo->user_id;
		$user_id = BaseController::getUserId();
		$userInfo = \App\Models\User::getById($user_id);
		$consignees = $params["consignees"];
		$page_number = count($consignees);
		$product_id = $params["product_id"];
		$product_info = Product::getById($product_id);
		$warehouse_info = WareHouse::getById($product_info["warehouse_id"]);
		$shop_id = $params["shop_id"];
		// 订单标记
		$userShopInfo = UserShopModel::query()->where(["shop_id"=>$shop_id,"user_id"=>$user_id,"is_delete"=>0])->first();
		$tag_match_type = ($userShopInfo["is_tag"] == 0) ? 0 : $userShopInfo["match_type"];
		$tag_color = $userShopInfo["tag_color"];
		$tag_remark = $userShopInfo["tag_remark"];
		$is_deliver = $params['is_deliver'] ?? 1; // 是否自动发货
		// 运费
		$express_info = CartController::getWarehousePriceV2($product_id, $user_id, $product_info['warehouse_id']);
		$shipping_fee = $express_info->price;
		// 会员优惠金额
		$preferential_amount = UserLevelModel::query()->where(["id" => $userInfo->level_id, "status" => 1])->value("preferential_amount");
		if ($preferential_amount) {
			$shipping_fee = $shipping_fee - $preferential_amount;
			// 防止亏钱  保险一点
			$warehouse_cost_price = \App\Models\Warehouse::query()->where("id", $product_info['warehouse_id'])->value("cost_price");
			if ($shipping_fee < $warehouse_cost_price) {
				$policy_msg["沧源ID"] = $product_info['warehouse_id'];
				$policy_msg["沧源价"] = $warehouse_cost_price;
				$policy_msg["运费"] = $shipping_fee;
				QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM") . "运费小于成本价下单失败" . json_encode($policy_msg, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),env("CHANNEL_MONEY_POLICY"));
				CommonUtil::throwException(ErrorEnum::ERROR_PRODUCT);
			}
		}
		$siteProduct= SiteProduct::query()->where(["site_id"=>$site_id,"product_id"=>$product_id])->select("price","profit")->first();
		if ($site_id == 1) {
			$product_cost_price = $siteProduct["price"];
		} else {
			// 查找站长成本价

			$product_cost_price = ProductLogic::getSiteProductCostPrice($product_info["id"], $site_user_id);
		}

		// 判断是否给该用户单独设置过商品利润
		$user_profit = UserProductProfit::query()->where("user_id", $user_id)->value("user_profit");
		if (empty($user_profit)) {
			$user_profit = $siteProduct['profit'];
		}
		$site_price = $user_profit + $product_cost_price;// 商品价格 = 当前用户商品利润+当前站长成本价;
		$site_freight_profit = 0; //站长的运费利润
		$up_site_freight_profit = 0; // 上级站长的运费利润
		$site_product_profit = 0; //站长商品利润
		$up_site_product_profit = 0; //上级站长商品利润
		if ($site_id != 1) {
			$site_freight_profit = $shipping_fee - $express_info->site_price; //站长运费-站长成本价
			$site_product_profit = $user_profit;
			if ($siteInfo->parent_id != 1) {//当前站长为二级站长
				$user_profit = UserProductProfit::query()->where("user_id", $site_user_id)->value("user_profit");
				$up_site_user_id = Site::query()->where("id", $siteInfo->parent_id)->value("user_id");
				if ($user_profit) {
					$up_site_product_profit = $user_profit;
				} else {
					$up_site_product_profit = SiteProduct::query()->where("user_id", $up_site_user_id)->value("site_profit"); // 上级站长利润
				}
				$up_site_freight_profit = CartLogic::computeSiteProfit($site_id, $siteInfo->parent_id, $product_info["id"], $product_info['warehouse_id']);
			}
		}
		$product_number = 1;
		$page_number = count($consignees);
		$total_price = $page_number * (($product_number * $site_price) + $shipping_fee);
		// 创建订单
		$orderMap["site_id"] = $site_id;
		$orderMap["user_id"] = $user_id;
		$orderMap["source"] = ($params["source"] == "tb") ? "taobao" : $params["source"];
		$orderMap["order_sn"] = BaseController::generateSN();
		$orderMap["product_id"] = $product_id;
		$orderMap["product_number"] = $product_number;
		$orderMap["warehouse_id"] = $product_info['warehouse_id'];
		$orderMap["shipping_fee"] = $shipping_fee;
		$orderMap["price"] = $site_price;
		$orderMap["channel_id"] = $warehouse_info['channel_id'];
		$orderMap["page_number"] = $page_number;
		$orderMap["total_price"] = $total_price;
		$orderMap["order_sent_type"] = 2;
		$orderMap["order_from"] = 2;
		$orderMap["status"] = USER_ORDER_STATUS_PAYMENT;
		$orderMap["tag_color"]=$tag_color;
		$orderMap["tag_remark"]=$tag_remark;
		$orderMap["tag_match_type"]=$tag_match_type;
		$express_id = ExpressProductModel::query()->where("product_id",$product_info["product_id"])->value("damaijia_express_id");
		$is_support_encry= ExpressModel::query()->where("id",$express_id)->value("is_support_encry");
		if($is_support_encry == 1) { // is_support_encry  '是否支持密文 0否1支持'
			$is_decrypt = 0;
			$is_encryption = 1;
		} else {
			$is_decrypt = 1;
			$is_encryption = 0;
		}
		DB::beginTransaction();
		try {
			$orderInfo = UserOrder::query()->create($orderMap);
			$order_id = $orderInfo["id"];
			//创建包裹
			foreach ($consignees as $consignee) {
				$insert = [
					'order_id' => $order_id,
					'consignee' => $consignee['consignee'],
					'consignee_mask' => $consignee['consignee_mask'],
					'mobile' => $consignee['mobile'],
					'mobile_mask' => $consignee['mobile_mask'],
					'province' => $consignee['province'],
					'city' => $consignee['city'],
					'district' => $consignee['district'],
					'address' => BaseUtil::getAddress($consignee['address']),
					'address_mask' => $consignee['address_mask'],
					'ext_platform_order_sn' => $consignee['ext_platform_order_sn'],
					'sync_status' => USER_ORDER_SYNC_STATUS_PENDING,
					'status' => PACKAGE_STATUS_PAYMENT,
					'site_id' => $site_id,
					"site_freight_profit" => $site_freight_profit,
					"up_site_freight_profit" => $up_site_freight_profit,
					"site_product_profit" => $site_product_profit,
					"up_site_product_profit" => $up_site_product_profit,
					"shop_id" => $shop_id,
					"is_deliver" => $is_deliver,
					"is_encryption" => $is_encryption,
					"oaid"=>$consignee["oaid"] ?? "",
					"is_decrypt" => $is_decrypt,
					"tag_color"=>$tag_color ?? "",
					"tag_remark"=>$tag_remark ?? "",
					"tag_match_type"=>$tag_match_type ?? 0,
				];
				\App\Models\OrderConsignee::create($insert);
			}
		} catch (\Exception $e) {
			CommonUtil::throwException([$e->getCode(),$e->getMessage()]);
			DB::rollBack();
		}
		DB::commit();
		$item = [
			'product_price' => $site_price, // 产品单价
			'product_name' => $product_info['name'], // 产品名
			'product_weight' => $product_info['weight'], // 产品重量
			'product_number' => $product_number * $page_number, // 产品数量
			'total_price' => $total_price, // 小计
		];
		$list = [];
		foreach ($consignees as $consignee) {
			$list[] = $item;
		}
		return [
			'list' => $list,
			'total_product_price' => $site_price*$page_number, // 产品总价
			'total_model' => 1, // 款数
			'total_count' => $page_number, // 总数
			'total_freight_price' => $shipping_fee*$page_number, // 总快递费
			'total_freight_number' =>$page_number*1, // 总快递数量
			'total_price' => $total_price, // 总价
			"order_id"=>$order_id
		];
	}

	// 获取平台订单v1
	public static function getPlatformOrderV1()
	{
		$params = app("request")->all();
		$shop_type = $params["shop_type"];
		$shop_id = $params["shop_id"];
		$page = $params["page"] ?? 1;
		$product_id = $params["product_id"];
		$data = [];
		$is_sync = 1;
		switch ($shop_type) {
			case "pdd":
				$data = self::getPddOrders($page, $shop_id);
				break;
			case "tb":
				$data = self::getTbOrders($page, $shop_id,$params["day"]);
				if($data["total"] > BaseUtil::platformOrderNum()) {
					$is_sync = 0;
				}
				break;
			case "ks":
				$data = self::getKsOrders($page, $shop_id);
				break;
			case "jd":
				$data = self::getJdOrders($page, $shop_id);
				break;
		}
		if($data["total"] == 0) {
			CommonUtil::throwException([500,"当前暂没有待发货订单"]);
		}
		$list = $data["list"];
		if($is_sync == 1) {
			$list = PlatformOrderUtil::checkPlatformOrder($list,$product_id);
			$sync_id = 0;
		} else {
			$list = [];
			// 淘宝订单量大走队列模式走队列
			$map["uid"] = BaseController::getUserId();;
			$map["day"] = $params["day"];
			$map["shop_id"] = $params['shop_id'];
			$map["shop_type"] = $params['shop_type'];
			$map["total_count"] = $data["total"];
			$sync_id = OrderSyncTaskModel::query()->insertGetId($map);
			$dateils = [];
			for ($i = 1; $i<=ceil($data["total"]/BaseUtil::platformOrderNum());$i++) {
				$detail["sync_id"] = $sync_id;
				$detail["uid"] = BaseController::getUserId();
				$detail["shop_id"] = $params['shop_id'];
				$detail["shop_type"] = $params['shop_type'];
				$detail["product_id"] = $product_id;
				$detail["page"] = $i;
				$detail["day"] = $params["day"];
				$detail["status"] = 0;
				$detail["update_time"] = date("Y-m-d H:i:s");
				$detail["create_time"] = date("Y-m-d H:i:s");
				$dateils[] = $detail;
			}
			$insert = OrderSyncTaskChildModel::query()->insert($dateils);
			
		}
		return[
			"sync_id"=>$sync_id,
			"total" => $data["total"],
			"list" => $list
		];
	}
	// 定时同步订单
	public static function cronGetPlatformOrder($task)
	{
		$shop_type = $task["shop_type"];
		$shop_id = $task["shop_id"];
		$page = $task["page"] ?? 1;
		$data = [];
		switch ($shop_type) {
			case "pdd":
				$data = self::getPddOrders($page, $shop_id);
				break;
			case "tb":
				$data = self::getTbOrders($page, $shop_id,$task["day"]);
				break;
			case "ks":
				$data = self::getKsOrders($page, $shop_id);
				break;
			case "jd":
				$data = self::getJdOrders($page, $shop_id);
				break;
		}
		$list = $data["list"];
		foreach ($list as &$v) {
			$v["sync_id"] = $task["sync_id"];
			$v["sync_child_id"] = $task["id"];
		}
			
		$task->status = 1;
		$task->save();
		return OrderSyncTaskDetailModel::query()->insert($list);
	}

	public static function getPlatformOrderV3()
	{
		$params = request()->all();

		$not_complete = OrderSyncTaskChildModel::query()->where("sync_id",$params["sync_id"])->whereIn("status",[0,3])->first();
		$status = 0;
		$fail = OrderSyncTaskChildModel::query()->where("sync_id",$params["sync_id"])->where("status",2)->first();

		$data = OrderSyncTaskDetailModel::query()->where("sync_id",$params["sync_id"])->get();
		if(empty($not_complete)) { //没有未同步的
			$status = 1;//同步成功
			if(!empty($fail)) {
				$status = 2;//部分成功
			}
			if($data->count() == 0) {
				$status = 3;//同步失败
			}
		}
		$product_id = OrderSyncTaskChildModel::query()->where("sync_id",$params["sync_id"])->value("product_id");
		$data = PlatformOrderUtil::checkPlatformOrder($data,$product_id);

		return ["status"=>$status,"total"=>count($data),"list"=>$data];
	}
}
