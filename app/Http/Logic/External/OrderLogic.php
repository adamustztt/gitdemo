<?php


namespace App\Http\Logic\External;


use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Http\Logic\BaseLogic;
use App\Http\Logic\BlackListLogic;
use App\Http\Utils\BaseUtil;
use App\Http\Utils\LoggerFactoryUtil;
use App\Models\AddressCity;
use App\Models\AddressProvince;
use App\Models\BanCityModel;
use App\Models\DamaijiaUserExpressPrice;
use App\Models\DamaijiaWarehouseUserSource;
use App\Models\ExpressModel;
use App\Models\ExpressProductModel;
use App\Models\ExpressWarehouseModel;
use App\Models\Product;
use App\Models\SiteBalanceLog;
use App\Models\UserLevelPrice;
use App\Models\UserOrder;
use App\Models\UserShopModel;
use App\Services\Shop\PddShop;
use App\Services\Shop\TbShop;
use App\Services\SiteService;
use App\Services\UserService;
use App\Services\Vtool\ErpService;
use App\Services\Warehouses\WarehouseService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OrderConsignee;
use App\Models\OrderConsignee as OrderConsigneeModel;
use SiteProduct;
use Tool\ShanTaoTool\QiWeiTool;
use User;
use WareHouse;

class OrderLogic extends BaseLogic
{
	/**
	 * 创建订单逻辑
	 */
	public static function createV1Logic()
	{
		$request = app("request");
		$user_id = $request->user_id;
//        $user_id = 105;
		$site_id = $request->site_id;
//        $site_id = 1;
		$params = $request->all();
		$params["product_number"] = 1;
		if (empty($params["site_order_id"])) {
			$params["site_order_id"] = time() . rand(1000, 9999);
		}
		foreach ($params["consignees"] as &$consignee) {
			$consignee["site_order_consignee_id"] = $consignee['site_order_consignee_id'] ?? time() . rand(1000, 9999);
		}
		//验证包裹信息
		if (OrderConsignee::check($params['consignees']) === false) {
			CommonUtil::throwException(ErrorEnum::ERROR_EXT_INVALID_CONSIGNEE);
		}
		//判断site_order_id是否重复
		$count = UserOrder::query()->where("user_id", $user_id)->where("site_order_id", $params["site_order_id"])->count();
		if ($count) {
			CommonUtil::throwException(ErrorEnum::ERROR_PRODUCT_SITE_ID);
		}
		$user = \App\Models\User::getById($user_id);
		$userPriceInfo = \App\Models\SiteProduct::query()->where(["user_id" => $user_id, "product_id" => $params["product_id"]])->first();
		$basePriceInfo = \App\Models\SiteProduct::query()->where(["site_id" => 1, "product_id" => $params["product_id"]])->first();
		if (!$basePriceInfo) {
			CommonUtil::throwException(ErrorEnum::ERROR_PRODUCT);
		}

		// 获取一级api运费 后面二级开放后api时需要修改此处逻辑
		if ($userPriceInfo) {
			$site_price = $basePriceInfo->price + $userPriceInfo->api_profit;
		} else {
			$site_price = $basePriceInfo->price + $basePriceInfo->api_profit;
		}
//        $site_price = SiteProduct::getSitePriceForLock($site_id, $params["product_id"]);
		$product = Product::query()->find($params["product_id"]);

		//获取商品归属发货地
		$expressId = ExpressProductModel::query()->where("product_id", $params["product_id"])->value("damaijia_express_id");
		if (!$expressId) {
			CommonUtil::throwException(ErrorEnum::ERROR_PRODUCT);
		}

		$warehouse_info = \App\Models\Warehouse::query()->find($product["warehouse_id"]);
		//获取API用户对应的运费
		//获取平台设置的上游仓库价格
		$baseWarehousePriceMap = DamaijiaUserExpressPrice::query()->where("user_id", 0)->pluck("api_price", "express_id");
		$baseWarehousePriceMap = $baseWarehousePriceMap ? $baseWarehousePriceMap->toArray() : [];

		//获取用户设置的价格
		$userWarehousePriceMap = DamaijiaUserExpressPrice::query()->where("user_id", app("request")->user_id)->pluck("api_price", "express_id");
		$userWarehousePriceMap = $userWarehousePriceMap ? $userWarehousePriceMap->toArray() : [];

		$shippingPrice = isset($userWarehousePriceMap[$expressId]) ? $userWarehousePriceMap[$expressId] : $baseWarehousePriceMap[$expressId];

		if (!$shippingPrice) {
			//不存在快递费用则报错
			CommonUtil::throwException(ErrorEnum::ERROR_PRODUCT_EXPRESS);
		}
		$product_info = Product::query()->where("id",$params["product_id"])->first();
		// 防止亏钱  保险一点
		$warehouse_cost_price = \App\Models\Warehouse::query()->where("id",$product_info['warehouse_id'])->value("cost_price");
		if($shippingPrice<$warehouse_cost_price) {
			$policy_msg["沧源ID"]=$product_info['warehouse_id'];
			$policy_msg["沧源价"]=$warehouse_cost_price;
			$policy_msg["运费"]=$shippingPrice;
			QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM")."运费小于成本价下单失败".json_encode($policy_msg,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),env("CHANNEL_MONEY_POLICY"));
			CommonUtil::throwException(ErrorEnum::ERROR_PRODUCT);
		}
		//开启事务
		DB::beginTransaction();

		$page_number = count($params["consignees"]);

		$total_price = $page_number * (($params["product_number"] * $site_price) + $shippingPrice);
		//获取订单编号
		$orderNumber = self::generateSN($user_id);

		$orderData = [
			'user_id' => $user_id,
			'source' => $params["source"],
			'order_sn' => $orderNumber,
			'product_id' => $params["product_id"],
			'product_number' => $params["product_number"],
			'warehouse_id' => $product["warehouse_id"],
			'shipping_fee' => $shippingPrice,
			'price' => $site_price,
			'channel_id' => $warehouse_info['channel_id'],
			'remark' => isset($params["remark"]) ? $params["remark"] : "",
			'status' => USER_ORDER_STATUS_PAID,
			'page_number' => $page_number,
			'total_price' => $total_price,
			'site_order_id' => $params['site_order_id'],
			'site_id' => $site_id,
			'order_sent_type' => 3,
			'order_from' => 3,
			'create_time' => date('Y-m-d H:i:s'),
			'pay_time' => date('Y-m-d H:i:s'),
			"consigners_consigner"=>$params["consigners"]["consigner"] ?? "",
			"consigners_mobile"=>$params["consigners"]["mobile"] ?? "",
			"consigners_province"=>$params["consigners"]["province"] ?? "",
			"consigners_city"=>$params["consigners"]["city"] ?? "",
			"consigners_district"=>$params["consigners"]["district"] ?? "",
			"consigners_address"=>$params["consigners"]["address"] ?? "",
		];
		$order = UserOrder::query()->create($orderData);
		if ($order === false) {
			DB::rollBack();
			CommonUtil::throwException(ErrorEnum::ERROR_CREATE_ORDER_FAIL);
		}
		$order_id = $order->id;

		if ($order_id === false) {
			DB::rollBack();
			CommonUtil::throwException(ErrorEnum::ERROR_EXT_UNKNOWN);
		}
		$express_id = ExpressProductModel::query()->where("product_id", $params["product_id"])->value("damaijia_express_id");
		// 平台商品成本价 仓库统计成本
		$platform_express_price = ExpressModel::query()->where("id", $express_id)->value("statistics_cost_price");
		$platform_profit = $shippingPrice - $platform_express_price;
		// 平台总利润 = 平台利润 * 包裹数量
		$platform_total_profit = $page_number * $platform_profit;
		foreach ($params["consignees"] as &$consignee) {
			$consigneeMap = [];
			$consigneeMap["order_id"] = $order_id;
			$consigneeMap["consignee"] = $consignee['consignee'];
			$consigneeMap["mobile"] = $consignee['mobile'];
			$consigneeMap["province"] = $consignee['province'];
			$consigneeMap["city"] = $consignee['city'];
			$consigneeMap["district"] = $consignee['district'];
			$consigneeMap["address"] = BaseUtil::getAddress($consignee['address']);
			$consigneeMap["ext_platform_order_sn"] = $consignee['platform_order_sn'];
			$consigneeMap["sync_status"] = USER_ORDER_SYNC_STATUS_PENDING;
			$consigneeMap["status"] = PACKAGE_STATUS_PENDING;
			$consigneeMap["site_id"] = $site_id;
			$consigneeMap["site_order_consignee_id"] = $consignee['site_order_consignee_id'];
			$consigneeMap["platform_profit"] = $platform_profit;

			$c_province = mb_substr($consignee['province'], 0, 2, "utf-8");
			$provinceInfo = AddressProvince::query()->where("name", "like", '%' . $c_province . '%')->first();
			if ($provinceInfo) {
				$ban_address = BanCityModel::getBanAddressExpressV1($express_id, 1, $provinceInfo->name);
				if (($provinceInfo["status"] == 2) || $ban_address) {
					$consigneeMap["cancel_type"] = 2;
					$consigneeMap["additional"] = "地址已停发";
					$consigneeMap["cancel_reason"] = "地址已停发, 请换其他快递公司";
				} else {
					$c_city = mb_substr($consignee['city'], 0, 2, "utf-8");
					$cityInfo = AddressCity::query()->where(["provinceCode" => $provinceInfo["code"]])->where("name", "like", "%" . $c_city . "%")->first();
					if ($cityInfo) {
						$ban_address = BanCityModel::getBanAddressExpressV1($express_id, 2, $provinceInfo->name, $cityInfo->name);
						if ($ban_address) {
							$consigneeMap["cancel_type"] = 2;
							$consigneeMap["additional"] = "地址已停发";
							$consigneeMap["cancel_reason"] = "地址已停发, 请换其他快递公司";
						}
					} else {
						$consigneeMap["cancel_type"] = 6;
						$consigneeMap["additional"] = "城市错误";
						$consigneeMap["cancel_reason"] = "城市错误";
					}
				}
			} else {
				$consigneeMap["cancel_type"] = 6;
				$consigneeMap["additional"] = "省份错误";
				$consigneeMap["cancel_reason"] = "省份错误";
			}
			if(BlackListLogic::checkPhoneIsBlack($consignee['mobile'],$params["product_id"])) {
				$consigneeMap["cancel_type"] = 5;
				$consigneeMap["additional"] ="收货人已经被多个店铺标黑禁止一件代发";
				$consigneeMap["cancel_reason"] = "收货人已经被多个店铺标黑禁止一件代发";
			}
			if(BlackListLogic::checkPhoneIsBlackByUserId($user_id,$params["product_id"])) {
				$consigneeMap["cancel_type"] = 5;
				$consigneeMap["additional"] ="收货人已经被多个店铺标黑禁止一件代发";
				$consigneeMap["cancel_reason"] = "收货人已经被多个店铺标黑禁止一件代发";
			}
			// 记录收件人
			$ret = OrderConsigneeModel::create($consigneeMap);
			if ($ret === false) {
				DB::rollBack();
				CommonUtil::throwException(ErrorEnum::ERROR_EXT_UNKNOWN);
			}
			$consignee["consignee_id"] = $ret->id;//获取包裹ID
		}
//        foreach ($params["consignees"] as &$consignee) {
//            // 记录收件人
//            $ret = OrderConsignee::addInternal($consignee['site_order_consignee_id'], $site_id, $order_id,
//                $consignee['consignee'], $consignee['mobile'], $consignee['province'], $consignee['city'],
//                $consignee['district'], str_replace(' ', '', $consignee['address']),
//                $consignee['platform_order_sn'],USER_ORDER_SYNC_STATUS_PENDING,PACKAGE_STATUS_PENDING);
//            if ($ret === false) {
//                DB::rollBack();
//                CommonUtil::throwException(ErrorEnum::ERROR_EXT_UNKNOWN);
//            }
//            $consignee["consignee_id"] = $ret->id;//获取包裹ID
//        }
		// 付款逻辑 扣费
//        $ret = User::balanceBuyInternal($user_id, $total_price, $order_id,$platform_total_profit);
		$userService = new UserService();
		$userService->decrUserBalance($user_id, $total_price, $order_id, $msg = "使用礼品商城api扣款", "p", $platform_total_profit, 1);
		if (!$ret) {
			DB::rollBack();
			CommonUtil::throwException(ErrorEnum::ERROR_EXT_UNKNOWN);
		}
		DB::commit();
		$consigneesMsg = self::getConsigneesMsg($params["product_id"], $params["consignees"]);
		return [
			"order_id" => $order_id,
			"order_sn" => $orderNumber,
			"consignees" => $consigneesMsg
		];
	}

	public static function generateSN($prefix = '')
	{
		$prefix = sprintf("%03d", $prefix);
		return date('YmdHis') . $prefix . rand(10000, 99999);
	}

	public static function getConsigneesMsg($product_id, $consignees)
	{
		$express_id = ExpressProductModel::query()->where("product_id", $product_id)->value("damaijia_express_id");
		$consigneesMsg = [];
		foreach ($consignees as $k => $v) {
			$check_province = BanCityModel::getBanAddressExpress($express_id, 1, $v["province"]);
			if ($check_province) {
				$temp["site_order_consignee_id"] = $v["site_order_consignee_id"];
				$temp["forbidden_zone"] = $v["province"];
				$temp["msg_code"] = ErrorEnum::FORBIDDEN_ZONE[0];
				$temp["msg"] = ErrorEnum::FORBIDDEN_ZONE[1];
				$consigneesMsg[] = $temp;
				continue;
			}
			$check_city = BanCityModel::getBanAddressExpress($express_id, 2, $v["province"], $v["city"]);
			if ($check_city) {
				$temp["site_order_consignee_id"] = $v["site_order_consignee_id"];
				$temp["forbidden_zone"] = $v["province"] . "/" . $v["city"];
				$temp["msg_code"] = ErrorEnum::FORBIDDEN_ZONE[0];
				$temp["msg"] = ErrorEnum::FORBIDDEN_ZONE[1];
				$consigneesMsg[] = $temp;
				continue;
			}
			$check_district = BanCityModel::getBanAddressExpress($express_id, 3, $v["province"], $v["city"], $v["district"]);
			if ($check_district) {
				$temp["site_order_consignee_id"] = $v["site_order_consignee_id"];
				$temp["forbidden_zone"] = $v["province"] . "/" . $v["city"] . "/" . $v["district"];
				$temp["msg_code"] = ErrorEnum::FORBIDDEN_ZONE[0];
				$temp["msg"] = ErrorEnum::FORBIDDEN_ZONE[1];
				$consigneesMsg[] = $temp;
				continue;
			}
			$temp["site_order_consignee_id"] = $v["site_order_consignee_id"];
			$temp["forbidden_zone"] = "";
			$temp["msg_code"] = ErrorEnum::PACKAGE_TRUE[0];
			$temp["msg"] = ErrorEnum::PACKAGE_TRUE[1];
			$temp["consignee_id"] = isset($v["consignee_id"]) ? $v["consignee_id"] : "";
			$consigneesMsg[] = $temp;
		}
		return $consigneesMsg;
	}

	/**
	 * @return mixed
	 * @throws \App\Exceptions\ApiException
	 * @throws \Throwable
	 * @author ztt
	 */
	public static function cancelPackage()
	{
		$request = app("request");
		$params = $request->all();
		$log = new LoggerFactoryUtil(OrderLogic::class);
		$log->info(json_encode($params));
		$site_order_consignee_id = $params["package_id"];
		$user_id = $params["user_id"];
		// 包裹可以取消状态 (代发货  已发货) 
		$status_arr = [PACKAGE_STATUS_PENDING, PACKAGE_STATUS_SHIPPED];
		// 获取包裹信息
		$order_consignee = OrderConsigneeModel::query()
			->with("userOrder:id,channel_id,user_id,shipping_fee,price")
			->join("user_order", "user_order.id", "=", "order_consignee.order_id")
			->where(["user_order.user_id" => $user_id, "order_consignee.site_order_consignee_id" => $site_order_consignee_id])
			->select("order_consignee.*")->first();
		if (empty($order_consignee)) {
			CommonUtil::throwException(ErrorEnum::ERROR_EXT_INVALID_PACKAGE_ID);
		}
		$package_id = $order_consignee->id;
		$order_consignee = $order_consignee->toArray();
		if ($order_consignee["status"] == "c") {
			return true;
		}
		if (!in_array($order_consignee['status'], $status_arr)) {
			CommonUtil::throwException(ErrorEnum::ERROR_EXT_INVALID_PACKAGE_STATUS);
		}
		DB::beginTransaction();
		try {
			// 如果包裹状态是待发货 不用请求上游
			if ($order_consignee['status'] == PACKAGE_STATUS_PENDING) {
				//更改包裹状态
				OrderConsigneeModel::updateById($package_id, ['status' => PACKAGE_STATUS_CANCELED]);
			} else {
				$bool = WarehouseService::getClass($order_consignee['user_order']['channel_id'])->cancelOrder((object)$order_consignee);
				if (!$bool) {
					DB::rollBack();
					CommonUtil::throwException(ErrorEnum::ERROR_EXT_INVALID_PACKAGE_STATUS);
				}
			}
			$where['order_id'] = $order_consignee['order_id'];
			$where[] = [function ($query) use ($status_arr) {
				$query->whereIn('order_consignee.status', $status_arr);
			}];
			// 更改订单包裹退款状态
			if (OrderConsigneeModel::getCount($where) > 0) {
				//订单包裹部分退款
				if ($order_consignee['user_order']['consignee_status'] == ORDER_CONSIGNEE_STATUS_NOTHING) {
					$result = UserOrder::updateById($order_consignee['order_id'], ['consignee_status' => ORDER_CONSIGNEE_STATUS_PART]);
					if ($result === false) {
						CommonUtil::throwException(ErrorEnum::ERROR_EXT_INVALID_PACKAGE_STATUS);
					}
				}
			} else {
				//订单包裹全部退款
				$result = UserOrder::updateById($order_consignee['order_id'], ['consignee_status' => ORDER_CONSIGNEE_STATUS_FULL]);
				if ($result === false) {
					CommonUtil::throwException(ErrorEnum::ERROR_EXT_INVALID_PACKAGE_STATUS);
				}
			}
			$amount = $order_consignee['user_order']['shipping_fee'] + $order_consignee['user_order']['price'];
			$userService = new userService();
			// 用户金额变动  用户资金流水
			$platform_profit = $order_consignee["platform_profit"];
			$userService->incrUserBalance($user_id, $amount, $package_id, "包裹退款ID:" . $package_id, "r", $platform_profit, 1);
			// 如果不是主站 并且分站利润已经计算    则退回分站利润
			if ($order_consignee["site_id"] != 1 && $order_consignee["is_belonged_site_income"] == 1) {
				// 查询分站该包裹的利润记录
				$logData = SiteBalanceLog::query()->where(array("context_id" => $package_id, "site_id" => $order_consignee["site_id"], "type" => 1, "type_name" => 4))->first();
				if ($logData) {
					// 防止重复退款
					if (!SiteBalanceLog::query()->where(array("context_id" => $package_id, "site_id" => $order_consignee["site_id"], "type" => 2, "type_name" => 4))->first()) {
						SiteService::siteRefund($order_consignee["site_id"], $package_id, $logData->change_balance);
					}
				}
			}
			DB::commit();
			return true;
		} catch (\Exception $e) {
			DB::rollBack();
			$policy_msg["包裹ID"] = $package_id;
			$policy_msg["site_order_consignee_id"] = $site_order_consignee_id;
			$policy_msg["user_id"] = $user_id;
			$policy_msg["msg"] = $e->getMessage();
			$policy_msg["code"] = $e->getCode();
			QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM") . "api用户包裹取消订单失败" . json_encode($policy_msg, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
			CommonUtil::throwException(ErrorEnum::ERROR_EXT_INVALID_PACKAGE_STATUS);
		}
	}

//	public static function orderCreateEncryption()
//	{
//		$params = app("request")->all();
//		$platform_order_sn = $params["platform_order_sn"];
//		$source = $params["source"];
//		$shop_id = $params["shop_id"];
//		$is_deliver = $params["is_deliver"] ?? 1;
//
//		switch ($source) {
//			case "taobao":
//				$shopClass = new TbShop();
//				$res = $shopClass->queryOrder($shop_id, $platform_order_sn);
//				$consigneeMap["consignee"] = $res['receiver_name'];
//				$consigneeMap["mobile"] = $res['receiver_mobile'];
//				$consigneeMap["province"] = $res['receiver_state'];
//				$consigneeMap["city"] = $res['receiver_city'];
//				$consigneeMap["district"] = $res['receiver_district'];
//				$consigneeMap["address"] = $res['receiver_address'];
//				$consigneeMap["ext_platform_order_sn"] = $platform_order_sn;
//				$consigneeMap["sync_status"] = USER_ORDER_SYNC_STATUS_PENDING;
//				$consigneeMap["status"] = PACKAGE_STATUS_PENDING;
//				$consigneeMap["is_encryption"] = 1;
//				$consigneeMap["is_deliver"] = $is_deliver;
//				$consigneeMap["oaid"] = $res["oaid"];
//				break;
//			case "pdd":
//				$shopClass = new PddShop();
//				$res = $shopClass->queryOrder($shop_id, $platform_order_sn);
//				$consigneeMap["consignee"] = $res['receiver_name'];
//				$consigneeMap["mobile"] = $res['receiver_phone'];
//				$consigneeMap["province"] = $res['province'];
//				$consigneeMap["city"] = $res['city'];
//				$consigneeMap["district"] = $res['town'];
//				$consigneeMap["address"] = $res['address'];
//				$consigneeMap["ext_platform_order_sn"] = $platform_order_sn;
//				$consigneeMap["sync_status"] = USER_ORDER_SYNC_STATUS_PENDING;
//				$consigneeMap["status"] = PACKAGE_STATUS_PENDING;
//				$consigneeMap["is_encryption"] = 1;
//				$consigneeMap["is_deliver"] = $is_deliver;
//				$requestParams["owner_id"] = $shop_id;
//				$requestParams["orderSns"] = [$platform_order_sn];
//				$requestParams["type"] = 1;
//				$url = "/tool/erps/mpaging-orders";
//				$erpService = new ErpService();
//				$res_mask = $erpService->sentPostRequest($url, $requestParams);
//				$consigneeMap["consignee_mask"] = $res_mask["content"][0]["receiver_name"] ?? "";
//				$consigneeMap["mobile_mask"] = $res_mask["content"][0]["receiver_phone"] ?? "";
//				$consigneeMap["address_mask"] = $res_mask["content"][0]["address"] ?? "";
////				$consigneeMap["oaid"] = $res["oaid"];
//				break;
//			default:CommonUtil::throwException(ErrorEnum::ERR_PRODUCT_SOURCE);
//			break;
//		}
//		$consigneeMap["shop_id"] = $shop_id;
//		if(empty($consigneeMap["mobile"])) {
//			CommonUtil::throwException(ErrorEnum::ERP_PRODUCT_INFO); 
//		}
////		dd($consigneeMap);
//		$user_id = $params["user_id"];
////        $user_id = 105;
//		$site_id = $params["site_id"];
//		if (empty($params["site_order_id"])) {
//			$params["site_order_id"] = time() . rand(1000, 9999);
//		}
//		$user = \App\Models\User::getById($user_id);
//		$userPriceInfo = \App\Models\SiteProduct::query()->where(["user_id" => $user_id, "product_id" => $params["product_id"]])->first();
//		$basePriceInfo = \App\Models\SiteProduct::query()->where(["site_id" => 1, "product_id" => $params["product_id"]])->first();
//		if (!$basePriceInfo) {
//			CommonUtil::throwException(ErrorEnum::ERROR_PRODUCT);
//		}
//
//		// 获取一级api运费 后面二级开放后api时需要修改此处逻辑
//		if ($userPriceInfo) {
//			$site_price = $basePriceInfo->price + $userPriceInfo->api_profit;
//		} else {
//			$site_price = $basePriceInfo->price + $basePriceInfo->api_profit;
//		}
////        $site_price = SiteProduct::getSitePriceForLock($site_id, $params["product_id"]);
//		$product = Product::query()->find($params["product_id"]);
//
//		//获取商品归属发货地
//		$expressId = ExpressProductModel::query()->where("product_id", $params["product_id"])->value("damaijia_express_id");
//		if (!$expressId) {
//			CommonUtil::throwException(ErrorEnum::ERROR_PRODUCT);
//		}
//		$express = ExpressModel::getById($expressId);
//		if($express->is_support_encry != 1) {
//			CommonUtil::throwException(ErrorEnum::VALIDATE_PRODUCT_ERROR);
//		}
//		$warehouse_info = \App\Models\Warehouse::query()->find($product["warehouse_id"]);
//		//获取API用户对应的运费
//		//获取平台设置的上游仓库价格
//		$baseWarehousePriceMap = DamaijiaUserExpressPrice::query()->where("user_id", 0)->pluck("api_price", "express_id");
//		$baseWarehousePriceMap = $baseWarehousePriceMap ? $baseWarehousePriceMap->toArray() : [];
//
//		//获取用户设置的价格
//		$userWarehousePriceMap = DamaijiaUserExpressPrice::query()->where("user_id", app("request")->user_id)->pluck("api_price", "express_id");
//		$userWarehousePriceMap = $userWarehousePriceMap ? $userWarehousePriceMap->toArray() : [];
//
//		$shippingPrice = isset($userWarehousePriceMap[$expressId]) ? $userWarehousePriceMap[$expressId] : $baseWarehousePriceMap[$expressId];
//
//		if (!$shippingPrice) {
//			//不存在快递费用则报错
//			CommonUtil::throwException(ErrorEnum::ERROR_PRODUCT_EXPRESS);
//		}
//
//		//开启事务
//		DB::beginTransaction();
//
//		$page_number = 1;
//		$product_number = 1;
//
//		$total_price = $page_number * (($product_number * $site_price) + $shippingPrice);
//		//获取订单编号
//		$orderNumber = self::generateSN($user_id);
//
//		$orderData = [
//			'user_id' => $user_id,
//			'source' => $params["source"],
//			'order_sn' => $orderNumber,
//			'product_id' => $params["product_id"],
//			'product_number' => $product_number,
//			'warehouse_id' => $product["warehouse_id"],
//			'shipping_fee' => $shippingPrice,
//			'price' => $site_price,
//			'channel_id' => $warehouse_info['channel_id'],
//			'remark' => isset($params["remark"]) ? $params["remark"] : "",
//			'status' => USER_ORDER_STATUS_PAID,
//			'page_number' => $page_number,
//			'total_price' => $total_price,
//			'site_order_id' => time() . rand(1000, 9999),
//			'site_id' => $site_id,
//			'order_sent_type' => 3,
//			'order_from' => 3,
//			'create_time' => date('Y-m-d H:i:s'),
//			'pay_time' => date('Y-m-d H:i:s')
//		];
//		$order = UserOrder::query()->create($orderData);
//		if ($order === false) {
//			DB::rollBack();
//			CommonUtil::throwException(ErrorEnum::ERROR_CREATE_ORDER_FAIL);
//		}
//		$order_id = $order->id;
//
//		if ($order_id === false) {
//			DB::rollBack();
//			CommonUtil::throwException(ErrorEnum::ERROR_CREATE_ORDER_FAIL);
//		}
//		$express_id = ExpressProductModel::query()->where("product_id", $params["product_id"])->value("damaijia_express_id");
//		// 平台商品成本价 仓库统计成本
//		$platform_express_price = ExpressModel::query()->where("id", $express_id)->value("statistics_cost_price");
//		$platform_profit = $shippingPrice - $platform_express_price;
//		// 平台总利润 = 平台利润 * 包裹数量
//		$platform_total_profit = $page_number * $platform_profit;
//		$c_province = mb_substr($consigneeMap['province'], 0, 2, "utf-8");
//		$provinceInfo = AddressProvince::query()->where("name", "like", '%' . $c_province . '%')->first();
//		if ($provinceInfo) {
//			$ban_address = BanCityModel::getBanAddressExpressV1($express_id, 1, $provinceInfo->name);
//			if (($provinceInfo["status"] == 2) || $ban_address) {
//				$consigneeMap["cancel_type"] = 2;
//				$consigneeMap["additional"] = "地址已停发";
//				$consigneeMap["cancel_reason"] = "地址已停发, 请换其他快递公司";
//			} else {
//				$c_city = mb_substr($consigneeMap['city'], 0, 2, "utf-8");
//				$cityInfo = AddressCity::query()->where(["provinceCode" => $provinceInfo["code"]])->where("name", "like", "%" . $c_city . "%")->first();
//				if ($cityInfo) {
//					$ban_address = BanCityModel::getBanAddressExpressV1($express_id, 2, $provinceInfo->name, $cityInfo->name);
//					if ($ban_address) {
//						$consigneeMap["cancel_type"] = 2;
//						$consigneeMap["additional"] = "地址已停发";
//						$consigneeMap["cancel_reason"] = "地址已停发, 请换其他快递公司";
//					}
//				}
//			}
//		}
//		$consigneeMap["order_id"] = $order_id;
//		$consigneeMap["site_id"] = $site_id;
//		$consigneeMap["site_order_consignee_id"] =  time() . rand(1000, 9999);
//		$consigneeMap["platform_profit"] = $platform_profit;
//		// 记录收件人
//		$ret = OrderConsigneeModel::create($consigneeMap);
//		if ($ret === false) {
//			DB::rollBack();
//			CommonUtil::throwException(ErrorEnum::ERROR_CREATE_ORDER_FAIL);
//		}
//		$userService = new UserService();
//		$userService->decrUserBalance($user_id, $total_price, $order_id, $msg = "使用礼品商城api扣款", "p", $platform_total_profit, 1);
//		if (!$ret) {
//			DB::rollBack();
//			CommonUtil::throwException(ErrorEnum::ERROR_CREATE_ORDER_FAIL);
//		}
//		DB::commit();
//		$consigneesMsg = self::getConsigneesMsg($params["product_id"], [$consigneeMap]);
//		return [
//			"order_id" => $order_id,
//			"order_sn" => $orderNumber,
//			"consignees" => $consigneesMsg
//		];
//	}
	public static function orderCreateEncryption()
	{
		$params = app("request")->all();
		$platform_order_sn = $params["platform_order_sn"];
		$source = $params["source"];
		$shop_id = $params["shop_id"];
		$is_deliver = $params["is_deliver"] ?? 1;
		$userShop = UserShopModel::query()->where(["user_id"=>$params["user_id"],"shop_id"=>$shop_id])->first();
		if(empty($userShop) && $params["user_id"] != env("VTOOL_USER_ID")) {
			CommonUtil::throwException(ErrorEnum::ERP_USER_SHOP);

		}
		switch ($source) {
			case "taobao":
				$shopClass = new TbShop();
				$res = $shopClass->queryOrder($shop_id, $platform_order_sn,$params["third_user_id"] ?? env("AT_VTOOL_PROJECT_USER_ID"));
				$consigneeMap["consignee"] = $res['receiver_name'];
				$consigneeMap["mobile"] = $res['receiver_mobile'];
				$consigneeMap["province"] = $res['receiver_state'];
				$consigneeMap["city"] = $res['receiver_city'];
				$consigneeMap["district"] = $res['receiver_district'];
				$consigneeMap["address"] = $res['receiver_address'];
				$consigneeMap["ext_platform_order_sn"] = $platform_order_sn;
				$consigneeMap["sync_status"] = USER_ORDER_SYNC_STATUS_PENDING;
				$consigneeMap["status"] = PACKAGE_STATUS_PENDING;
				$consigneeMap["is_encryption"] = 1;
				$consigneeMap["is_deliver"] = $is_deliver;
				$consigneeMap["oaid"] = $res["oaid"];
				$consigneeMap["third_user_id"] = $params["third_user_id"] ?? env("AT_VTOOL_PROJECT_USER_ID");
				break;
			case "pdd":
				$shopClass = new PddShop();
				$res = $shopClass->queryOrder($shop_id, $platform_order_sn);
				$consigneeMap["consignee"] = $res['receiver_name'];
				$consigneeMap["mobile"] = $res['receiver_phone'];
				$consigneeMap["province"] = $res['province'];
				$consigneeMap["city"] = $res['city'];
				$consigneeMap["district"] = $res['town'];
				$consigneeMap["address"] = $res['address'];
				$consigneeMap["ext_platform_order_sn"] = $platform_order_sn;
				$consigneeMap["sync_status"] = USER_ORDER_SYNC_STATUS_PENDING;
				$consigneeMap["status"] = PACKAGE_STATUS_PENDING;
				$consigneeMap["is_encryption"] = 1;
				$consigneeMap["is_deliver"] = $is_deliver;
				$consigneeMap["consignee_mask"] = $res["receiver_name_mask"];
				$consigneeMap["mobile_mask"] = $res["receiver_phone_mask"];
				$consigneeMap["address_mask"] = $res["address_mask"];
//				$consigneeMap["oaid"] = $res["oaid"];
				break;
			default:CommonUtil::throwException(ErrorEnum::ERR_PRODUCT_SOURCE);
				break;
		}
		$consigneeMap["shop_id"] = $shop_id;
		if(empty($consigneeMap["mobile"])) {
			CommonUtil::throwException(ErrorEnum::ERP_PRODUCT_INFO);
		}
//		dd($consigneeMap);
		$user_id = $params["user_id"];
//        $user_id = 105;
		$site_id = $params["site_id"];
		if (empty($params["site_order_id"])) {
			$params["site_order_id"] = time() . rand(1000, 9999);
		}
		$user = \App\Models\User::getById($user_id);
		$userPriceInfo = \App\Models\SiteProduct::query()->where(["user_id" => $user_id, "product_id" => $params["product_id"]])->first();
		$basePriceInfo = \App\Models\SiteProduct::query()->where(["site_id" => 1, "product_id" => $params["product_id"]])->first();
		if (!$basePriceInfo) {
			CommonUtil::throwException(ErrorEnum::ERROR_PRODUCT);
		}

		// 获取一级api运费 后面二级开放后api时需要修改此处逻辑
		if ($userPriceInfo) {
			$site_price = $basePriceInfo->price + $userPriceInfo->api_profit;
		} else {
			$site_price = $basePriceInfo->price + $basePriceInfo->api_profit;
		}
//        $site_price = SiteProduct::getSitePriceForLock($site_id, $params["product_id"]);
		$product = Product::query()->find($params["product_id"]);

		//获取商品归属发货地
		$expressId = ExpressProductModel::query()->where("product_id", $params["product_id"])->value("damaijia_express_id");
		if (!$expressId) {
			CommonUtil::throwException(ErrorEnum::ERROR_PRODUCT);
		}
		$express = ExpressModel::getById($expressId);
		if($express->is_support_encry != 1) {
			CommonUtil::throwException(ErrorEnum::VALIDATE_PRODUCT_ERROR);
		}
		$warehouse_info = \App\Models\Warehouse::query()->find($product["warehouse_id"]);
		//获取API用户对应的运费
		//获取平台设置的上游仓库价格
		$baseWarehousePriceMap = DamaijiaUserExpressPrice::query()->where("user_id", 0)->pluck("api_price", "express_id");
		$baseWarehousePriceMap = $baseWarehousePriceMap ? $baseWarehousePriceMap->toArray() : [];

		//获取用户设置的价格
		$userWarehousePriceMap = DamaijiaUserExpressPrice::query()->where("user_id", app("request")->user_id)->pluck("api_price", "express_id");
		$userWarehousePriceMap = $userWarehousePriceMap ? $userWarehousePriceMap->toArray() : [];

		$shippingPrice = isset($userWarehousePriceMap[$expressId]) ? $userWarehousePriceMap[$expressId] : $baseWarehousePriceMap[$expressId];

		if (!$shippingPrice) {
			//不存在快递费用则报错
			CommonUtil::throwException(ErrorEnum::ERROR_PRODUCT_EXPRESS);
		}

		//开启事务
		DB::beginTransaction();

		$page_number = 1;
		$product_number = 1;

		$total_price = $page_number * (($product_number * $site_price) + $shippingPrice);
		//获取订单编号
		$orderNumber = self::generateSN($user_id);

		$orderData = [
			'user_id' => $user_id,
			'source' => $params["source"],
			'order_sn' => $orderNumber,
			'product_id' => $params["product_id"],
			'product_number' => $product_number,
			'warehouse_id' => $product["warehouse_id"],
			'shipping_fee' => $shippingPrice,
			'price' => $site_price,
			'channel_id' => $warehouse_info['channel_id'],
			'remark' => isset($params["remark"]) ? $params["remark"] : "",
			'status' => USER_ORDER_STATUS_PAID,
			'page_number' => $page_number,
			'total_price' => $total_price,
			'site_order_id' => time() . rand(1000, 9999),
			'site_id' => $site_id,
			'order_sent_type' => 3,
			'order_from' => 3,
			'create_time' => date('Y-m-d H:i:s'),
			'pay_time' => date('Y-m-d H:i:s'),
			"consigners_consigner"=>$params["consigners"]["consigner"] ?? "",
			"consigners_mobile"=>$params["consigners"]["mobile"] ?? "",
			"consigners_province"=>$params["consigners"]["province"] ?? "",
			"consigners_city"=>$params["consigners"]["city"] ?? "",
			"consigners_district"=>$params["consigners"]["district"] ?? "",
			"consigners_address"=>$params["consigners"]["address"] ?? "",
		];
		$order = UserOrder::query()->create($orderData);
		if ($order === false) {
			DB::rollBack();
			CommonUtil::throwException(ErrorEnum::ERROR_CREATE_ORDER_FAIL);
		}
		$order_id = $order->id;

		if ($order_id === false) {
			DB::rollBack();
			CommonUtil::throwException(ErrorEnum::ERROR_CREATE_ORDER_FAIL);
		}
		$express_id = ExpressProductModel::query()->where("product_id", $params["product_id"])->value("damaijia_express_id");
		// 平台商品成本价 仓库统计成本
		$platform_express_price = ExpressModel::query()->where("id", $express_id)->value("statistics_cost_price");
		$platform_profit = $shippingPrice - $platform_express_price;
		// 平台总利润 = 平台利润 * 包裹数量
		$platform_total_profit = $page_number * $platform_profit;
		$c_province = mb_substr($consigneeMap['province'], 0, 2, "utf-8");
		$provinceInfo = AddressProvince::query()->where("name", "like", '%' . $c_province . '%')->first();
		if ($provinceInfo) {
			$ban_address = BanCityModel::getBanAddressExpressV1($express_id, 1, $provinceInfo->name);
			if (($provinceInfo["status"] == 2) || $ban_address) {
				$consigneeMap["cancel_type"] = 2;
				$consigneeMap["additional"] = "地址已停发";
				$consigneeMap["cancel_reason"] = "地址已停发, 请换其他快递公司";
			} else {
				$c_city = mb_substr($consigneeMap['city'], 0, 2, "utf-8");
				$cityInfo = AddressCity::query()->where(["provinceCode" => $provinceInfo["code"]])->where("name", "like", "%" . $c_city . "%")->first();
				if ($cityInfo) {
					$ban_address = BanCityModel::getBanAddressExpressV1($express_id, 2, $provinceInfo->name, $cityInfo->name);
					if ($ban_address) {
						$consigneeMap["cancel_type"] = 2;
						$consigneeMap["additional"] = "地址已停发";
						$consigneeMap["cancel_reason"] = "地址已停发, 请换其他快递公司";
					}
				}
			}
		}
		$consigneeMap["order_id"] = $order_id;
		$consigneeMap["site_id"] = $site_id;
		$consigneeMap["platform_profit"] = $platform_profit;
		$consigneeMap["site_order_consignee_id"] = $params['site_order_consignee_id'] ?? time() . rand(1000, 9999);
		// 记录收件人
		$ret = OrderConsigneeModel::create($consigneeMap);
		if ($ret === false) {
			DB::rollBack();
			CommonUtil::throwException(ErrorEnum::ERROR_CREATE_ORDER_FAIL);
		}
		$userService = new UserService();
		$userService->decrUserBalance($user_id, $total_price, $order_id, $msg = "使用礼品商城api扣款", "p", $platform_total_profit, 1);
		if (!$ret) {
			DB::rollBack();
			CommonUtil::throwException(ErrorEnum::ERROR_CREATE_ORDER_FAIL);
		}
		DB::commit();
		$consigneesMsg["consignee_id"]=$ret["id"];
		$consigneesMsg = self::getConsigneesMsg($params["product_id"], [$consigneeMap]);
		return [
			"order_id" => $order_id,
			"site_order_consignee_id"=>$ret["site_order_consignee_id"],
			"consignee_id" => $ret["id"],
			"order_sn" => $orderNumber,
			"consignees" => $consigneesMsg
		];
	}
}
