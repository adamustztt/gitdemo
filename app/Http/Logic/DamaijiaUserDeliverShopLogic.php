<?php


namespace App\Http\Logic;


use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Http\Controllers\BaseController;
use App\Http\Utils\BaseUtil;
use App\Http\Utils\LoggerFactoryUtil;
use App\Models\CustomWarehouseExpressModel;
use App\Models\CustomWarehouseModel;
use App\Models\ExpressProductModel;
use App\Models\OrderConsignee;
use App\Models\Product;
use App\Models\User;
use App\Models\UserOrder;
use App\Models\UserShopAuthorizationLogModel;
use App\Models\UserShopModel;
use http\Header;
use MongoDB\Driver\Exception\CommandException;
use phpDocumentor\Reflection\Location;
use Tool\ShanTaoTool\HttpCurl;
use function foo\func;

class DamaijiaUserDeliverShopLogic extends BaseLogic
{
	public static function listUserShop()
	{
		$user_id = BaseController::getUserId();
		$data = UserShopModel::query()
			->where("user_id", $user_id)
			->where("is_delete", 0)
			->orderBy("id", "desc")->get();
		$date = date("Y-m-d H:i:s");
		foreach ($data as &$v) {
			$v["is_expiration"] = false;
			if ($date > $v["expiration_time"]) {
				$v["is_expiration"] = true;
			}
		}
		return $data;
	}

	public static function getUserShop()
	{
		$params = app("request")->all();
		$user_id = BaseController::getUserId();
		$where["user_id"] = $user_id;
		if (!empty($params["id"])) {
			$where["id"] = $params["id"];
		}
		$data = UserShopModel::query()->where($where)->first();
		$date = date("Y-m-d H:i:s");
		$data["is_expiration"] = false;
		if ($date > $data["expiration_time"]) {
			$data["is_expiration"] = true;
		}
		return $data;
	}

	public static function refreshGerUserShop()
	{
		$params = app("request")->all();
		switch ($params["shop_type"]) {
			case "pdd":
				return self::refreshGetPddUserShop();
			case "ks":
				return self::refreshGetUserShopByType("ks");
			case "tb":
				return self::refreshGetUserShopByType("tb");
			case "dy":
				return self::refreshGetUserShopByType("dy");
			default:
				return [];
		}
	}

	/*
	 * 刷新拼多多授权信息
	 */
	public static function refreshGetPddUserShop()
	{
		$user_id = BaseController::getUserId();
		$site_id = BaseController::getSiteId();
		$create_time = date("Y-m-d");
		$url = env("PDD_GET_USER_SHOP_BY_CHILD_ID");
		$uid = env("AT_VTOOL_PROJECT_USER_ID");
		$project_id = env("PROJECT_ID");
		$params = [
			"project_id" => $project_id,
			"uid" => md5($uid),
			"create_time" => $create_time,
			"child_id" => $user_id
		];
		$req = HttpCurl::postCurl($url, $params);
		$log = new LoggerFactoryUtil(DamaijiaUserDeliverShopLogic::class);
		$log->info("拼多多erp返回结果：".json_encode($req));
		$result = null;
		if (isset($req["data"]) && !empty($req["data"])) {
			$data = $req["data"];
			$map["user_id"] = $data["child_id"];
			$map["shop_id"] = $data["shop_id"];
			$map["shop_name"] = $data["shop"]["shop_name"];
			$map["shop_type"] = "pdd";
			$map["authorization_time"] = date("Y-m-d H:i:s");
			$map["expiration_time"] = date("Y-m-d H:i:s", $data["shop"]["expires_at"]);
			$map["authorization_from"] = 2;
			$map["site_id"] = $site_id;
			$map["access_token"] = $data["shop"]["access_token"];
			$map["callback_params"] = json_encode($data);
			$find = UserShopModel::query()->where([
				"shop_id" => $data["shop_id"],
				"shop_type" => "pdd",
				"is_delete" => 0
			])->first();
			if (!$find) {
				$result = UserShopModel::create($map);
				$log_map["user_id"] = $data["child_id"];
				$log_map["shop_id"] = $data["shop_id"];
				$log_map["shop_name"] = $data["shop"]["shop_name"];
				$log_map["shop_type"] = "pdd";
				$log_map["shop_version"] = $data["order_desc"];
				$log_map["site_id"] = $site_id;
				$log_map["expiration_time"] = date("Y-m-d H:i:s", $data["shop"]["expires_at"]);
				UserShopAuthorizationLogModel::create($log_map);
			} else {
				CommonUtil::throwException([422, "该店铺已经被绑定"]);
			}
		}
		return $result;

	}

	public static function refreshGetUserShopByType($type)
	{
		$user_id = BaseController::getUserId();
		$result = UserShopModel::query()
			->where("user_id", $user_id)
			->where("shop_type", $type)
			->orderBy("id", "desc")
			->first();
		return $result;
	}

	public static function setUserShop()
	{
		$params = app("request")->all();
		$where["id"] = $params["id"];
		if(isset($params["shop_status"])) {
			$data["shop_status"] = $params["shop_status"];
		}
		if($params["is_tag"]) {
			$data["is_tag"] = $params["is_tag"];
		}
		if(isset($params["tag_color"])) {
			$data["tag_color"] = $params["tag_color"];
		}
		if(isset($params["tag_remark"])) {
			$data["tag_remark"] = $params["tag_remark"];
		}
		if(isset($params["match_type"])) {
			$data["match_type"] = $params["match_type"];
		}
		$shop = UserShopModel::query()->where($where)->first();
		if($shop->shop_type == "pdd") {
//			if(empty($params["tag_remark"])) {
//				CommonUtil::throwException([422, "拼多多类型店铺备注必填"]);
//			}
		}
		return UserShopModel::query()->where($where)->update($data);
	}

	public static function authorizationShop()
	{
		$params = app("request")->all();
		$id = $params["id"];
		$user_shop = UserShopModel::query()->where(["id" => $id])->first();
		switch ($user_shop["shop_type"]) {
			case "pdd":
				return self::pddAuthorizationShop($user_shop);
			case "tb":
				return self::tbAuthorizationShop($user_shop);
			case "ks":
				return self::ksAuthorizationShop($user_shop);
			case "dy":
				return self::dyAuthorizationShop($user_shop);
			default:
				return [];
		}
	}

	// 快手更新最新店铺过期时间
	public static function ksAuthorizationShop($user_shop)
	{
		// 授权回掉会自动刷新过期时间
		return true;
	}

	// 淘宝更新最新店铺过期时间
	public static function tbAuthorizationShop($user_shop)
	{
		// 授权回掉会自动刷新过期时间
		return true;
	}
	// 抖音更新最新店铺过期时间
	public static function dyAuthorizationShop($user_shop)
	{
		// 授权回掉会自动刷新过期时间
		return true;
	}

	// 拼多多更新最新店铺过期时间
	public static function pddAuthorizationShop($user_shop)
	{
		$user_id = BaseController::getUserId();
		$site_id = BaseController::getSiteId();
		$url = env("PDD_GET_USER_SHOP_BY_CHILD_ID");
		$uid = env("AT_VTOOL_PROJECT_USER_ID");
		$project_id = env("PROJECT_ID");
		$params = [
			"project_id" => $project_id,
			"uid" => md5($uid),
			"child_id" => $user_id,
			"shop_id" => $user_shop["shop_id"]
		];
		$req = HttpCurl::postCurl($url, $params);
		if (isset($req["data"]) && !empty($req["data"])) {
			$data = $req["data"];
			$new_expiration_time = date("Y-m-d H:i:s", $data["shop"]["expires_at"]);
			if ($user_shop["expiration_time"] != $new_expiration_time) {
				$user_shop->authorization_time = date("Y-m-d H:i:s");
				$user_shop->expiration_time = $new_expiration_time;
				$user_shop->save();
				$log_map["user_id"] = $user_id;
				$log_map["shop_id"] = $user_shop["shop_id"];
				$log_map["shop_name"] = $data["shop"]["shop_name"];
				$log_map["shop_type"] = "pdd";
				$log_map["shop_version"] = $data["order_desc"];
				$log_map["site_id"] = $site_id;
				$log_map["expiration_time"] = $new_expiration_time;
				UserShopAuthorizationLogModel::create($log_map);
			}
		}
		return true;
	}

	public static function getUserShopByProductId()
	{
		$params = app("request")->all();
		$user_id = BaseController::getUserId();
		$product_id = $params["product_id"];
		$productInfo = Product::getById($product_id);
		$user_sorce = explode(",", $productInfo["user_source"]);
		$shop_type = [];
		foreach ($user_sorce as $k => $v) {
			if ("taobao" == $v) {
				$shop_type[] = "tb";
				continue;
			}
			if ("tmall" == $v) {
				$shop_type[] = "tb";
				continue;
			}
			if ("jd" == $v) {
				$shop_type[] = "jd";
				continue;
			}
			if ("pdd" == $v) {
				$shop_type[] = "pdd";
				continue;
			}
			if ("other" == $v) {
				$shop_type = ["tb", "pdd", "ks", "jd","dy"];
				continue;
			}
		}
		$shop = UserShopModel::query()
			->whereIn("shop_type", $shop_type)
			->where("user_id", $user_id)
			->where("is_delete", 0)
			->get();
		$date = date("Y-m-d H:i:s");
		foreach ($shop as &$v) {
			$v["is_expiration"] = false;
			if ($date > $v["expiration_time"]) {
				$v["is_expiration"] = true;
			}
		}
		return $shop;
	}

	/*
	 * 店铺删除
	 */
	public static function deleteShop()
	{
		$params = app("request")->all();
		$id = $params["id"];
		return UserShopModel::query()->where("id", $id)->update(["is_delete" => 1]);
	}

	/*
	 * 快手店铺回掉
	 */
	public static function ksShopCallback()
	{
		$params = app("request")->all();
		$shop_id = $params["shop_id"];
		$shop_name = $params["shop"]["shop_name"];
		$user_id = $params["uid"];
		$expiration_time = $params["shop"]["expires_at"];
		$shop_version = $params["shop"]["shop_desc"];
		$site_id = User::query()->where("id", $user_id)->value("site_id");
		$find = UserShopModel::query()->where([
			"shop_id" => $shop_id,
			"shop_type" => "ks",
			"authorization_from" => 2,
			"is_delete" => 0
		])->first();
		$map["user_id"] = $user_id;
		$map["shop_id"] = $shop_id;
		$map["shop_name"] = $shop_name;
		$map["shop_type"] = "ks";
		$map["authorization_time"] = date("Y-m-d H:i:s");
		$map["expiration_time"] = date("Y-m-d H:i:s", $expiration_time);
		$map["authorization_from"] = 2;
		$map["site_id"] = $site_id;
		$log_map["user_id"] = $user_id;
		$log_map["shop_id"] = $shop_id;
		$log_map["shop_name"] = $shop_name;
		$log_map["shop_type"] = "ks";
		$log_map["shop_version"] = $shop_version;
		$log_map["site_id"] = $site_id;
		$log_map["expiration_time"] = date("Y-m-d H:i:s", $expiration_time);
		UserShopAuthorizationLogModel::create($log_map);
		if (!$find) {
			UserShopModel::create($map);
		} else {
			$find->expiration_time = date("Y-m-d H:i:s", $expiration_time);
			$find->save(); // 刷新过期时间
		}
		return "success";
	}

	/*
	 * 淘宝店铺授权回掉
	 */
	public static function webCallbackShopInfo()
	{
		$params = app("request")->all();
		$state = $params['state'];
		$stateArr = explode("-", $state);
		$shop_id = $params["sid"];
		$shop_name = $params["title"];
		$expiration_time = $params["deadline"];
		$user_id = $stateArr[count($stateArr) - 1];
		$user = User::getById($user_id);
		$site_id = $user['site_id'];
		$find = UserShopModel::query()->where([
			"shop_id" => $shop_id,
			"shop_type" => "tb",
			"user_id"=>$user_id,
			"is_delete" => 0,
			"version_type"=>2
		])->first();
		$map["user_id"] = $user_id;
		$map["version_type"] = 2;
		$map["shop_id"] = $shop_id;
		$map["shop_name"] = $shop_name;
		$map["shop_type"] = "tb";
		$map["authorization_time"] = date("Y-m-d H:i:s");
		$map["expiration_time"] = $expiration_time;
		$map["authorization_from"] = 2;
		$map["site_id"] = $site_id;
		$map["callback_params"] = json_encode($params);
		$log_map["user_id"] = $user_id;
		$log_map["shop_id"] = $shop_id;
		$log_map["shop_name"] = $shop_name;
		$log_map["shop_type"] = "tb";
		$log_map["site_id"] = $site_id;
		$log_map["expiration_time"] = $expiration_time;
		UserShopAuthorizationLogModel::create($log_map);
		if (!$find) {
			UserShopModel::create($map);
		} else {
			//刷新过期时间
			UserShopModel::query()->where([
				"shop_id" => $shop_id,
				"shop_type" => "tb",
				"user_id"=>$user_id,
				"is_delete" => 0,
				"version_type"=>2
			])->update($map);
		}
		return "success";
	}
	/*
	 * 淘宝店铺授权回掉 打单软件3
	 */
	public static function webCallbackShopInfo3()
	{
		$params = app("request")->all();
		$shop_id = $params["shop_id"];
		$shop_name = $params["shop_name"];
		$expiration_time = $params["expiration_time"];
		$user_id = $params["user_id"];
		$user = User::getById($user_id);
		$site_id = $user['site_id'];
		$find = UserShopModel::query()->where([
			"shop_id" => $shop_id,
			"shop_type" => "tb",
			"user_id"=>$user_id,
			"is_delete" => 0,
			"version_type"=>3
		])->first();
		$map["user_id"] = $user_id;
		$map["version_type"] = 3;
		$map["shop_id"] = $shop_id;
		$map["shop_name"] = $shop_name;
		$map["shop_type"] = "tb";
		$map["authorization_time"] = date("Y-m-d H:i:s");
		$map["expiration_time"] = $expiration_time;
		$map["authorization_from"] = 2;
		$map["site_id"] = $site_id;
		$map["callback_params"] = json_encode($params);
		$log_map["user_id"] = $user_id;
		$log_map["shop_id"] = $shop_id;
		$log_map["shop_name"] = $shop_name;
		$log_map["shop_type"] = "tb";
		$log_map["site_id"] = $site_id;
		$log_map["expiration_time"] = $expiration_time;
		UserShopAuthorizationLogModel::create($log_map);
		if (!$find) {
			UserShopModel::create($map);
		} else {
			//刷新过期时间
			UserShopModel::query()->where([
				"shop_id" => $shop_id,
				"shop_type" => "tb",
				"user_id"=>$user_id,
				"is_delete" => 0,
				"version_type"=>3
			])->update($map);
		}
		return "success";
	}
	/*
	 * 淘宝店铺授权回掉 打单软件3
	 */
	public static function webCallbackShopInfo4()
	{
		$params = app("request")->all();
		$shop_id = $params["shop_id"];
		$shop_name = $params["shop_name"];
		$expiration_time = $params["expiration_time"];
		$user_id = $params["user_id"];
		$user = User::getById($user_id);
		$site_id = $user['site_id'];
		$find = UserShopModel::query()->where([
			"shop_id" => $shop_id,
			"shop_type" => "tb",
			"user_id"=>$user_id,
			"is_delete" => 0,
			"version_type"=>4
		])->first();
		$map["user_id"] = $user_id;
		$map["version_type"] = 4;
		$map["shop_id"] = $shop_id;
		$map["shop_name"] = $shop_name;
		$map["shop_type"] = "tb";
		$map["authorization_time"] = date("Y-m-d H:i:s");
		$map["expiration_time"] = $expiration_time;
		$map["authorization_from"] = 2;
		$map["site_id"] = $site_id;
		$map["callback_params"] = json_encode($params);
		$log_map["user_id"] = $user_id;
		$log_map["shop_id"] = $shop_id;
		$log_map["shop_name"] = $shop_name;
		$log_map["shop_type"] = "tb";
		$log_map["site_id"] = $site_id;
		$log_map["expiration_time"] = $expiration_time;
		UserShopAuthorizationLogModel::create($log_map);
		if (!$find) {
			UserShopModel::create($map);
		} else {
			//刷新过期时间
			UserShopModel::query()->where([
				"shop_id" => $shop_id,
				"shop_type" => "tb",
				"user_id"=>$user_id,
				"is_delete" => 0,
				"version_type"=>4
			])->update($map);
		}
		return "success";
	}
	

	public static function listShopOrder()
	{
		$params = app("request")->all();
		$shop_id = $params["shop_id"];
		$date = date("Y-m-d H:i:s", time() - 3600 * 24 * 30 * 3);
		$query = OrderConsignee::query()->where("shop_id", $shop_id)
			->whereIn("status", ["p", "s"])->where("create_time", ">", $date);
		if (!empty($params["ext_platform_order_sn"])) {
			$query->where("ext_platform_order_sn", $params["ext_platform_order_sn"]);
		}
		if (!empty($params["mobile"])) {
			$query->where("mobile", $params["mobile"]);
		}
		if (!empty($params["express_no"])) {
			$query->where("express_no", $params["express_no"]);
		}
		if (!empty($params["custom_warehouse_id"])) {
			$expressIdMap = CustomWarehouseExpressModel::query()->where("custom_warehouse_id", $params["custom_warehouse_id"])->pluck("express_id")->toArray();
			$productIdMap = ExpressProductModel::query()->whereIn("damaijia_express_id", $expressIdMap)->pluck("product_id")->toArray();
			$orderIdMap = UserOrder::query()->whereIn("product_id", $productIdMap)->pluck("id")->toArray();
			$query->whereIn("order_id", $orderIdMap);
		}
		if (!empty($params["order_status"])) {
			switch ($params["order_status"]) { // 1代发货  2待发货（出单中）3，已发货 4发货失败 5已退款
				case 1:
					$query->where("express_no", "!=", "")->where("deliver_status", 0);
					break;
				case 2:
					$query->where("express_no", "=", "")->where("deliver_status", 0);
					break;
				case 3:
					$query->where("deliver_status", 1);
					break;
				case 4:
					$query->where("deliver_status", 2);
					break;
				case 5:
					$query->where("status", "c");
					break;
			}
		}
		$page = $params["page"] ?? 1;
		$pageSize = $params["pageSize"] ?? 10;
		$count = $query->count();
		$data = $query->offset(($page - 1) * $pageSize)->with("userOrder")->limit($pageSize)->get();
		$product_ids = [];
		foreach ($data as $k => $v) {
			$product_ids[] = $v->userOrder["product_id"];
		}
		$expressProduct = ExpressProductModel::query()->whereIn("product_id", $product_ids)
			->pluck("damaijia_express_id", "product_id")->toArray();
		$customMap = CustomWarehouseModel::query()->pluck("custom_warehouse_name", "id")->toArray();
		$customExpressMap = CustomWarehouseExpressModel::query()->pluck("custom_warehouse_id", "express_id")->toArray();
		$products = Product::query()->whereIn("id", $product_ids)->get();
		$productMap = [];
		foreach ($products as $k => $v) {
			$productMap[$v["id"]] = $v;
		}
		foreach ($data as $k => $v) {
			$order_status_name = "";
			if ($v["status"] == "c") {
				$order_status_name = "已退款";
			} else {
				if (empty($v["express_no"])) {
					$order_status_name = "待发货（出单中）";
				} else {
					$order_status_name = "待发货";
					if ($v["deliver_status"] == 1) {
						$order_status_name = "已发货";
					}
					if ($v["deliver_status"] == 2) {
						$order_status_name = "发货失败";
					}
				}
			}
			$data[$k]["product"] = $productMap[$v->userOrder["product_id"]];
			$data[$k]["order_status_name"] = $order_status_name;
			$data[$k]["custom_warehouse_name"] = $customMap[$customExpressMap[$expressProduct[$v->userOrder["product_id"]]]];
		}
		return ["total" => $count, "list" => $data];
	}
}
