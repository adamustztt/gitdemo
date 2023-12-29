<?php


namespace App\Services\Warehouses;


use App\Enums\WarehouseChannelEnum;
use App\Exceptions\OuterApiException;
use App\Helper\CommonUtil;
use App\Http\Logic\ChannelSyncLogic;
use App\Http\Utils\LipinDaoUtil;
use App\Http\Utils\LoggerFactoryUtil;
use App\Models\NewsModel;
use App\Models\OrderConsignee;
use App\Models\Product;
use App\Models\SendPhoneModel;
use App\Models\Warehouse;
use App\Services\OrderConsigneePushDownService;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Tool\ShanTaoTool\HttpCurl;
use Tool\ShanTaoTool\QiWeiTool;

class LipindaoWarehouse extends AbstractWarehouse
{
	private $token = "81d8526f-236a-4b47-8401-d0d9351e3e8b";
//	protected $baseUrl = "https://www.lipindao.com";
	protected $baseUrl = "http://lpd.daifa51.com";
//	protected $baseUrl = "http://www.daifa51.com";
	protected $channel = WarehouseChannelEnum::LI_PIN_DAO;
	protected $package_id = "";
	protected $orderConsignee;
	protected $userOrder;

	protected function requestWarehouse()
	{
		$response = $this->sendRequest("post", "/api/goods/storelist", []);
		$warehouseList = $this->handleResponse($response); // 仓库列表
		$list = [];
		$storelist = $warehouseList["storelist"];
		foreach ($storelist as $warehouseKey => $warehouseValue) {
			$list[] = [
				"ext_id" => $warehouseValue["id"],
				"name" => $warehouseValue["store_name"],
				"cost_price" => (integer)floatval($warehouseValue["price"] * 1000) / 10,
				"price" => (integer)floatval($warehouseValue["price"] * 1000) / 10, // 初始平台价
				"address" => $warehouseValue["send_address"],
				"channel_id" => $this->channel,
				"status" => WARE_HOUSE_STATUS_NORMAL,
			];
		}
		$log = new LoggerFactoryUtil(LipindaoWarehouse::class);
		$log->info("返回数据", json_encode($list));
		return $list;
	}
	// 此上游商品下架 返回格式商品对多个仓库 
	public function updateProductStatus($productList)
	{
		foreach ($productList as $k=>$v) {
			$storeId = explode(",",$v["store_id"]);
			$warehouseIdMap = Warehouse::query()
				->where("channel_id",$this->channel)
				->whereIn("ext_id",$storeId)
				->pluck("id")->toArray();
			$product_up_data = Product::query()
				->where("ext_id",$v["id"])
				->where("channel_id",$this->channel)
				->where("status",PRODUCT_STATUS_ONLINE)
				->whereNotIn("warehouse_id",$warehouseIdMap)
				->select("id","name")->get();
			if($product_up_data->count()) {
				$updates = Product::query()
					->where("ext_id",$v["id"])
					->where("channel_id",$this->channel)
					->where("status",PRODUCT_STATUS_ONLINE)
					->whereNotIn("warehouse_id",$warehouseIdMap)
					->update(["status"=>PRODUCT_STATUS_OFFLINE,"up_status"=>2]);
				if(!$updates) {
					$policy = env("POLICE_FROM").date('Y-m-d H:i:s')."已下架失败".json_encode($product_up_data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
					QiWeiTool::sendMessageToBaoJing($policy);
				}
				$policy = env("POLICE_FROM").date('Y-m-d H:i:s')."已下架商品".json_encode($product_up_data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
				QiWeiTool::sendMessageToBaoJing($policy,env("POLICE_CODE"));
			}
		}
	}
	protected function getWarehousePlatform()
	{
		$response = $this->sendRequest("post", "/api/goods/storelist", []);
		$warehouseList = $this->handleResponse($response); // 仓库列表
		$list = [];
		$storelist = $warehouseList["storelist"];
		$stores = [];
		foreach ($storelist as $k => $v) {
			$stores[$v["id"]] = $v["plattype"];
		}
		return $stores;
	}

	protected function requestProduct($page = 1, $page_size = 100)
	{
		$stores = $this->getWarehousePlatform();
		$response = $this->sendRequest("post", "/api/goods/goodslist");

		$api_result = $this->handleResponse($response);
		$productList = $api_result["goodslist"];
		$warehouseList = Warehouse::query()->where(["channel_id" => $this->channel])->select("id", "ext_id")->get()->toArray();
		$warehouseMap = [];
		foreach ($warehouseList as $k => $v) {
			$warehouseMap[$v["ext_id"]] = $v;
		}
		$list = [];
		// 此上游商品下架 返回格式商品对多个仓库 
		$this->updateProductStatus($productList);
		foreach ($productList as $k => $v) {
			$store_ids = explode(",", $v["store_id"]);
			foreach ($store_ids as $store_id) {
				$warehouse = isset($warehouseMap[$store_id]) ? $warehouseMap[$store_id] : [];
				if (empty($warehouse)) {
					continue;
				}
				$list[] = [
					"name" => $v["name"] . $stores[$warehouse["ext_id"]],
					"thumb" => $v["goods_image"],
					"up_cost_price" => $v["apiprice"] * 100,
					"weight" => $v["weight"] * 1000,
					"ext_id" => $v["id"],
					"channel_id" => $this->channel,
					"status" => PRODUCT_STATUS_OFFLINE,
					"warehouse_id" => $warehouse["id"],
					"stock" => 9999,
					"sales" => rand(100, 9999),
				];
			}

		}
		return $list;
	}

	protected function getMoney()
	{
//	    $res = HttpCurl::postCurlOrigin($this->baseUrl."/api/goods/getmoney",[],["token"=>$this->token],false,true);
		$response = $this->sendRequest("post", "/api/goods/getmoney", []);
		$instance = new LoggerFactoryUtil(LipindaoWarehouse::class);
//		$instance->info("F安徽:".$res);
		$instance->info("获取礼品岛账户余额");
		$api_result = $this->handleResponse($response); // 仓库列表
		if (isset($api_result["usermoney"])) {
			if ($api_result["usermoney"] < 50) {
				$policy_msg["msg"] = "礼品岛剩余金额不足50元";
				$policy_msg["礼品岛剩余金额"] = $api_result["usermoney"];
				QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM").json_encode($policy_msg,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),env("CHANNEL_MONEY_POLICY"));
			}
		}
	}
// "1:菜鸟号段(淘宝、天猫、1688可用),2:拼多多电子面单(拼多多、京东可用)
	//sourcePlatform
	private $expressStatus = [
		USER_ORDER_SOURCE_TAOBAO => 1,
		USER_ORDER_SOURCE_TMALL => 1,
		USER_ORDER_SOURCE_PDD => 2,
		USER_ORDER_SOURCE_JD => 2,
	];

	protected function requestOrder($product, $userOrder, $orderConsignee)
	{
		
		$this->package_id = $orderConsignee->id;
		$this->orderConsignee = $orderConsignee;
		$this->userOrder = $userOrder;
		$this->getMoney();
//		try {
//			$this->getMoney();
//		} catch (\Exception $e) {
//			$policy_msg["包裹信息"] = $orderConsignee;
//			$policy_msg["url"] = "api/goods/getmoney";
//			$policy_msg["error"] = $e->getMessage();
//			QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM") . "礼品岛请求下单失败" . json_encode($policy_msg, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), env("POLICE_CODE"));
//
//		}
		
//		$send_order_no = date("Ymd").$orderConsignee->id.date("His");
		$send_order_no = $userOrder->order_sn . $orderConsignee->id;
		$warehouse = Warehouse::getById($product->warehouse_id);
		$params["send_order_no"] = ($orderConsignee->is_encryption == 1) ? $orderConsignee->ext_platform_order_sn : (string)$send_order_no;
		$params["goodsid"] = (integer)$product->ext_id;
		$params["storesid"] = (integer)$warehouse->ext_id;
		$params["plattype"] = $this->expressStatus[$userOrder->source];
		$params["receiver_name"] = $orderConsignee->consignee;
		$params["receiver_phone"] = $orderConsignee->mobile;
		$params["receiver_province"] = $orderConsignee->province;
		$params["receiver_city"] = $orderConsignee->city;
		$params["receiver_district"] = $orderConsignee->district;
		$params["receiver_address"] = $orderConsignee->address;
		/**
		 * @var \Redis $redis
		 */
		$redis = app("redis");
		$sendname = $redis->get("sendname");
		if(empty($sendname)) {
			$sendname = LipinDaoUtil::getSendName();
			$redis->set("sendname",$sendname);
		}
		$params["sendname"] = $sendname;

		$sendphone = $redis->get("sendphone");
		if(empty($sendphone)) {
			$sendphone = LipinDaoUtil::getMobile();
			$redis->set("sendphone",$sendphone);
		}
		$params["sendphone"] = $sendphone;
		if($orderConsignee->is_encryption == 1) {
			$params["iszn"] = 1;
			if($userOrder->source == "taobao" || $userOrder->source == "tb") {
				$params["oaid"] = $orderConsignee->oaid;
			}
			if($userOrder->source == "pdd") {
				$params["receiver_name"] = urlencode($orderConsignee->consignee);
				$params["receiver_phone"] = urlencode($orderConsignee->mobile);
				$params["receiver_address"] = urlencode($orderConsignee->address);
			}
		}
		//$response = $this->sendRequest("post", "/api/goods/order", $params);
		// 给了个立马出单号的接口
		$log = new LoggerFactoryUtil(LipindaoWarehouse::class);
		$log->info("下单请求参数：".json_encode($params));
		$response = $this->sendRequest("post", "/api/goods/submit", $params);
		$req = $this->handleResponse($response);
		return [
			"third_order_sn" => $req["taskid"],
			"express_no" => empty($req["express_no"]) ? "" : $req["express_no"],
			"status" => PACKAGE_STATUS_SHIPPED,
			"sync_status" => USER_ORDER_SYNC_STATUS_SUCCESS,
		];
	}

	protected function requestOrderQuery($orderConsignee)
	{
		$params["taskid"] = $orderConsignee->third_order_sn;
		$response = $this->sendRequest("post", "/api/goods/get_express", $params);
		$api_req = $this->handleResponse($response);
		return [
			"express_no" => $api_req["express_no"],
			"sync_query_status" => USER_ORDER_SYNC_QUERY_STATUS_SUCCESS,
		];
	}

	protected function requestCancelOrder($orderConsignee)
	{
		$params["ids"] = $orderConsignee->third_order_sn;
		$response = $this->sendRequest("post", "/api/goods/calloff", $params);
		$api_req = $this->handleResponse($response);
		if (!empty($api_req)) {
			return true;
		}
	}

	private function sendRequest(string $method, $uri, array $data = [])
	{
		$client = $this->getHttpClient();
		$baseUrl = $this->baseUrl;
		$params = $data;
		$this->requestParams = $params;
		$this->requestUrl = $baseUrl . $uri;
		return $client->request($method, $uri, [
			"form_params" => $params,
			"headers" => [
				"token" => $this->token
			]
		]);
	}

	private function handleResponse(ResponseInterface $response)
	{
		$contents = $response->getBody()->getContents();
		$instance = new LoggerFactoryUtil(ShenzhenYundaWarehouse::class);
		$instance->info("礼品岛" . json_encode([
				'url' => $this->requestUrl,
				"params" => $this->requestParams,
				"response" => $contents,
			]));
		$instance->info("礼品岛上游返回数据:" . json_encode($contents));
		if (is_string($contents)) {
			$contents = json_decode($contents, true);
		}
		if (!$contents) {
			throw new OuterApiException(sprintf("%s error:%s", CommonUtil::getClassName(get_class($this)), json_encode($contents)));
		}
		$heimingdanKey = "lipindao_heimingdan".date("Ymd");
		if ($contents["code"] != 1) {
			$function = "";
			$method = $this->requestParams["method"];
			switch ($this->requestUrl) {
				case "/api/goods/storelist":
					$function = "获取仓库";
					break;
				case "/api/goods/calloff":
					$function = "取消订单";
					break;
				case "address.logistics.list":
					$function = "获取快递";
					break;
				case "/api/goods/submit":
					$function = "请求下单";
					break;
				case  "/api/goods/get_express":
					$function = "查询订单";
					break;
			}
			$class_name = CommonUtil::getClassName(get_class($this));
			$policy_msg = [
				'功能' => $function,
				'请求链接' => $this->requestUrl,
				'请求参数' => $this->requestParams,
				'响应结果' => $contents,
				'信息时间' => date("Y-m-d H:i:s"),
				"damaijia_user_id" => $this->damaijia_user_id
			];
			if($function=="请求下单") {
				$policy_msg["商品id"] = $this->baseProductId;
				$policy_msg["仓库id"] = $this->baseExpressId;
				$policy_msg["仓源id"] = $this->baseWarehouseId;
			}
			$is_policy = true;
			if (strpos($contents["msg"], '不存在') !== false) {
				// 自动取消
				$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id);
				if ($cancel_req) {
					OrderConsignee::updateById($this->package_id, ["cancel_type" => 4, "cancel_reason" => "商品库存不足,请换其他商品"]);
				}
//				$add_push = OrderConsigneePushDownService::addPush($this->package_id, 1);// 如果是api用户 添加推送信息
				$new["user_id"] = $this->userOrder->user_id;
				$new["remark"] = "礼品库存不足";
				$new["type"] = 1;
				$new["order_id"] = $this->userOrder->id;
				$new["package_id"] = $this->package_id;
				NewsModel::create($new); //创建通知
				$policy_msg["msg"] = "退款已成功";
				WarehouseService::getClass($this->channel)->saveProduct();
			}
			if (strpos($contents["msg"], '停发') !== false) {
				// 自动取消
				$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id);
				if ($cancel_req) {
					OrderConsignee::updateById($this->package_id, ["cancel_type" => 2, "cancel_reason" => "该地区已停发"]);
				}
				$is_policy = false;
				$policy_msg["msg"] = "退款已成功";
			}
			if (strpos($contents["msg"], '黑名单用户') !== false) {
				/**
				 * @var \Redis $redis
				 */
				$redis = app("redis");
				$redis->incr($heimingdanKey);
				$heimingdanCount = $redis->get($heimingdanKey);
				// 发件人黑名单 换发件人手机号和姓名
				$sendname = LipinDaoUtil::getSendName();
				$sendphone = LipinDaoUtil::getMobile();
				$redis->set("sendname",$sendname);
				$redis->set("sendphone",$sendphone);
				if($heimingdanCount>1) {
					// 发件人黑名单
					QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM") . "礼品岛（收件人）黑名单已更换发货手机号为：".$sendphone."；姓名为：".$sendname, env("CHANNEL_MONEY_POLICY"));
					// 自动取消
					$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id);
					if ($cancel_req) {
						OrderConsignee::updateById($this->package_id, ["cancel_type" => 5, "cancel_reason" => "收货人已经被多个店铺标黑禁止一件代发"]);
					}
					$policy_msg["msg"] = "退款已成功";
					$redis->del($heimingdanKey);
				} else {
					QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM") . "礼品岛黑名单已更换发货手机号为：".$sendphone."；姓名为：".$sendname, env("CHANNEL_MONEY_POLICY"));
				}
				
				
			} else if($this->requestUrl == "/api/goods/submit"){
				//如果是请求下单 重置连续黑名单
				/**
				 * @var \Redis $redis
				 */
				$redis = app("redis");
				$redis->del($heimingdanKey);
			}

			if (strpos($contents["msg"], '账户余额不足!') !== false) {
				// 自动取消
				$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id);
				if ($cancel_req) {
					OrderConsignee::updateById($this->package_id, ["cancel_type" => 2, "cancel_reason" => "该仓库不支持该地区"]);
				}
				$is_policy = false;
			}
			if (strpos($contents["msg"], '收货地址地区信息过长') !== false) {
				// 自动取消
				$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id);
				if ($cancel_req) {
					OrderConsignee::updateById($this->package_id, ["cancel_type" => 2, "cancel_reason" => "该仓库不支持该地区"]);
				}
				$is_policy = false;
			}
			if (strpos($contents["msg"], '该地区暂不支持派送') !== false) {
				// 自动取消
				$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id);
				if ($cancel_req) {
					OrderConsignee::updateById($this->package_id, ["cancel_type" => 2, "cancel_reason" => "该地区已停发"]);
				}
				$policy_msg["msg"] = "退款已成功";
				$is_policy = false;
//				WarehouseService::getClass($this->channel)->saveProduct();
			}
			if (strpos($contents["msg"], '由于疫情原因') !== false) {
				// 自动取消
				$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id);
				if ($cancel_req) {
					OrderConsignee::updateById($this->package_id, ["cancel_type" => 2, "cancel_reason" => "该地区已停发"]);
				}
				$policy_msg["msg"] = "退款已成功";
				$is_policy = false;
//				WarehouseService::getClass($this->channel)->saveProduct();
			}
			//这个上游，这个msg(看文字)不再预警，不断的请求（直到返回正确值），直到下午4点还是这样直接取消订单，
			//如果是下午4点后的订单，直接取消
			if (strpos($contents["msg"], '电子面单账户余额不足') !== false) {
				$is_policy = false;
				$h = date("H");
				if($h>15) {
					// 自动取消
					$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id);
					if ($cancel_req) {
						OrderConsignee::updateById($this->package_id, ["cancel_type" => 4, "cancel_reason" => "商品库存不足,请换其他商品"]);
					}
				}
			}
			if ($contents["code"] == 111 && $function = "请求下单") {
				//补上游的坑 有时候上游下单成功了没告诉我们  再次下单提示单号重复 单号和taskid 返回了给我们了  
				if (!empty($contents["data"])) {
					if (!empty($contents["data"]["express_no"]) && !empty($contents["data"]["taskid"])) {
						return $contents["data"] ?? [];
					}
				}
			}
			if($is_policy) {
				QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM") . "礼品岛" . json_encode($policy_msg, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), env("POLICE_CODE"));
			}
			throw new OuterApiException(sprintf("%s error:%s", $class_name, json_encode($contents)));
		} else if($this->requestUrl == "/api/goods/submit"){
			//如果是请求下单
			/**
			 * @var \Redis $redis
			 */
			$redis = app("redis");
			$redis->del($heimingdanKey);
		}
		return $contents["data"] ?? [];
	}
}
