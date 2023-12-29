<?php


namespace App\Http\Logic\External;


use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Http\Logic\BaseLogic;
use App\Http\Logic\BlackListLogic;
use App\Models\AddressCity;
use App\Models\AddressProvince;
use App\Models\BanCityModel;
use App\Models\DamaijiaUserExpressPrice;
use App\Models\ExpressModel;
use App\Models\ExpressProductModel;
use App\Models\OrderConsignee as OrderConsigneeModel;
use App\Models\Product;
use App\Models\UserOrder;
use App\Services\UserService;
use Illuminate\Support\Facades\DB;
use Tool\ShanTaoTool\QiWeiTool;

class PlatformOrderLogic extends BaseLogic
{
	/**
	 * 创建秘文订单
	 */
	public static function createEntryOrder() 
	{
		$params = app("request")->all();
		$consignees = $params["consignees"];
		$user_id = $params["user_id"];
		$site_id = $params["site_id"];
		$is_deliver = $params["is_deliver"] ?? 1;
		$shop_id = $params["shop_id"];
		//判断site_order_id是否重复
		$count = UserOrder::query()->where("user_id", $user_id)->where("site_order_id", $params["site_order_id"])->count();
		if ($count) {
			CommonUtil::throwException(ErrorEnum::ERROR_PRODUCT_SITE_ID);
		}
		$product = Product::query()->find($params["product_id"]);
		$sourceMap = explode(",",$product["user_source"]);
		if(!in_array($params["source"],$sourceMap)) {
			CommonUtil::throwException(ErrorEnum::ERROR_WAREHOUSE_SOURCE);
		}
		$userPriceInfo = \App\Models\SiteProduct::query()->where(["user_id" => $user_id, "product_id" => $params["product_id"]])->first();
		$basePriceInfo = \App\Models\SiteProduct::query()->where(["site_id" => 1, "product_id" => $params["product_id"]])->first();
		if (!$basePriceInfo) {
			CommonUtil::throwException(ErrorEnum::ERROR_PRODUCT);
		}
//获取商品归属发货地
		$expressId = ExpressProductModel::query()->where("product_id", $params["product_id"])->value("damaijia_express_id");
		if (!$expressId) {
			CommonUtil::throwException(ErrorEnum::ERROR_PRODUCT);
		}

		$warehouse_info = \App\Models\Warehouse::query()->find($product["warehouse_id"]);
		// 获取一级api运费 后面二级开放后api时需要修改此处逻辑
		if ($userPriceInfo) {
			$site_price = $basePriceInfo->price + $userPriceInfo->api_profit;
		} else {
			$site_price = $basePriceInfo->price + $basePriceInfo->api_profit;
		}
		//获取API用户对应的运费
		//获取平台设置的上游仓库价格
		$baseWarehousePriceMap = DamaijiaUserExpressPrice::query()->where("user_id", 0)->pluck("api_price", "express_id");
		$baseWarehousePriceMap = $baseWarehousePriceMap ? $baseWarehousePriceMap->toArray() : [];

		//获取用户设置的价格
		$userWarehousePriceMap = DamaijiaUserExpressPrice::query()->where("user_id", app("request")->user_id)->pluck("api_price", "express_id");
		$userWarehousePriceMap = $userWarehousePriceMap ? $userWarehousePriceMap->toArray() : [];

		$shippingPrice = isset($userWarehousePriceMap[$expressId]) ? $userWarehousePriceMap[$expressId] : $baseWarehousePriceMap[$expressId];
		$page_number = count($params["consignees"]);
		if (!$shippingPrice) {
			//不存在快递费用则报错
			CommonUtil::throwException(ErrorEnum::ERROR_PRODUCT_EXPRESS);
		}
		// 防止亏钱  保险一点
		$warehouse_cost_price = \App\Models\Warehouse::query()->where("id",$product['warehouse_id'])->value("cost_price");
		if($shippingPrice<$warehouse_cost_price) {
			$policy_msg["沧源ID"]=$product['warehouse_id'];
			$policy_msg["沧源价"]=$warehouse_cost_price;
			$policy_msg["运费"]=$shippingPrice;
			QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM")."运费小于成本价下单失败".json_encode($policy_msg,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),env("CHANNEL_MONEY_POLICY"));
			CommonUtil::throwException(ErrorEnum::ERROR_PRODUCT);
		}
		$total_price = $page_number * ($site_price + $shippingPrice);

		$orderNumber = OrderLogic::generateSN($user_id);
		
		$orderData = [
			'user_id' => $user_id,
			'source' => $params["source"],
			'order_sn' => $orderNumber,
			'product_id' => $params["product_id"],
			'product_number' => 1,
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
			'pay_time' => date('Y-m-d H:i:s')
		];
		//开启事务
		DB::beginTransaction();
		$order = UserOrder::query()->create($orderData);
		if ($order === false) {
			DB::rollBack();
			CommonUtil::throwException(ErrorEnum::ERROR_CREATE_ORDER_FAIL);
		}
		$express_id = ExpressProductModel::query()->where("product_id", $params["product_id"])->value("damaijia_express_id");
		// 平台商品成本价 仓库统计成本
		$platform_express_price = ExpressModel::query()->where("id", $express_id)->value("statistics_cost_price");
		$platform_profit = $shippingPrice - $platform_express_price;
		// 平台总利润 = 平台利润 * 包裹数量
		$platform_total_profit = $page_number * $platform_profit;
		$order_id = $order->id;

		if ($order_id === false) {
			DB::rollBack();
			CommonUtil::throwException(ErrorEnum::ERROR_EXT_UNKNOWN);
		}
		foreach ($consignees as &$consignee) {
			$consigneeMap = [];
			$consigneeMap["order_id"] = $order_id;
			$consigneeMap["consignee"] = $consignee['consignee'];
			$consigneeMap["mobile"] = $consignee['mobile'];
			$consigneeMap["province"] = $consignee['province'];
			$consigneeMap["city"] = $consignee['city'];
			$consigneeMap["district"] = $consignee['district'];
			$consigneeMap["address"] = $consignee['address'];
			$consigneeMap["ext_platform_order_sn"] = $consignee['platform_order_sn'];
			$consigneeMap["sync_status"] = USER_ORDER_SYNC_STATUS_PENDING;
			$consigneeMap["status"] = PACKAGE_STATUS_PENDING;
			$consigneeMap["site_id"] = $site_id;
			$consigneeMap["site_order_consignee_id"] = $consignee['site_order_consignee_id'];
			$consigneeMap["platform_profit"] = $platform_profit;
			$consigneeMap["oaid"] = $consignee["oaid"];
			$consigneeMap["is_encryption"] = 1;
			$consigneeMap["is_deliver"] = $is_deliver;
			$consigneeMap["consignee_mask"] = $consignee["consignee_mask"];
			$consigneeMap["mobile_mask"] = $consignee["mobile_mask"];
			$consigneeMap["address_mask"] = $consignee["address_mask"];
			$consigneeMap["shop_id"] = $shop_id;
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
					}
				}
			}
			if(BlackListLogic::checkPhoneIsBlack($consignee['mobile'],$params["product_id"])) {
				$consigneeMap["cancel_type"] =2;
				$consigneeMap["additional"] ="收货信息错误，包裹已取消，请重新下单";
			}
			if(BlackListLogic::checkPhoneIsBlackByUserId($user_id,$params["product_id"])) {
				$consigneeMap["cancel_type"] =2;
				$consigneeMap["additional"] ="收货信息错误，包裹已取消，请重新下单";
				$consigneeMap["cancel_reason"] = "收货信息错误，包裹已取消，请重新下单";
			}
			// 记录收件人
			$ret = OrderConsigneeModel::create($consigneeMap);
			if ($ret === false) {
				DB::rollBack();
				CommonUtil::throwException(ErrorEnum::ERROR_EXT_UNKNOWN);
			}
			$consignee["consignee_id"] = $ret->id;//获取包裹ID
		}
		$userService = new UserService();
		$userService->decrUserBalance($user_id, $total_price, $order_id, $msg = "使用礼品商城api扣款", "p", $platform_total_profit, 1);
		if (!$ret) {
			DB::rollBack();
			CommonUtil::throwException(ErrorEnum::ERROR_EXT_UNKNOWN);
		}
		DB::commit();
		$consigneesMsg = OrderLogic::getConsigneesMsg($params["product_id"], $consignees);
		return [
			"order_id" => $order_id,
			"order_sn" => $orderNumber,
			"consignees" => $consigneesMsg
		];
	}
}
