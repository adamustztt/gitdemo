<?php


namespace App\Services\Warehouses;


use App\Enums\ErrorEnum;
use App\Enums\WarehouseChannelEnum;
use App\Exceptions\OuterApiException;
use App\Helper\CommonUtil;
use App\Http\Logic\ChannelSyncLogic;
use App\Http\Logic\OrderConsigneeLogic;
use App\Http\Utils\LoggerFactoryUtil;
use App\Models\OrderConsignee;
use App\Models\Warehouse;
use App\Services\BanCityService;
use App\Services\OrderConsigneePushDownService;
use Psr\Http\Message\ResponseInterface;
use Tool\ShanTaoTool\QiWeiTool;

class KuaidiyunNewWarehouse extends AbstractWarehouse
{
	//http://vip.xjsbbk.com/newer/member/api-doc
	//132916784751
	//c13291678475
	protected $channel = WarehouseChannelEnum::KUAIDIYUNNEW;
	protected $baseUrl = "http://vip.xjbbk.com/api";
	protected $userName = 13291678475;
	protected $key;
	protected $password = "c13291678475";
	protected $package_id = "";
	public function source($source)
	{
		//平台，1淘宝 2拼多多，3京东 4抖音
		switch ($source) {
			case "taobao":return 1;
			case "tb":return 1;
			case "tmall":return 1;
			case "pdd":return 2;
			case "jd":return 3;
			case "dy":return 4;
		}
	}
	protected function requestWarehouse()
	{

		$response = $this->sendRequest("post", "/lipin/list");
		$warehouseList = $this->handleResponse($response);
		$list = [];
		foreach ($warehouseList as $warehouseKey => $warehouseValue) {
			$list[] = [
				"ext_id" => $warehouseValue["id"],
				"name" => $warehouseValue["subname"],
				"cost_price" => (integer)floatval($warehouseValue["price"] * 1000) / 10,
				"price" => (integer)floatval($warehouseValue["price"] * 1000) / 10, // 初始平台价
				"address" => $warehouseValue["subname"],
				"channel_id" => $this->channel,
				"status" => WARE_HOUSE_STATUS_NORMAL,
				"ext_express_id" => $warehouseValue["cang"],
			];
		}
		return $list;

	}
//	public function __construct() {
//		$this->baseUrl=config("warehouse.Kuaidiyun.domain");
//		$this->userName=config("warehouse.Kuaidiyun.userName");
//		$this->key=config("warehouse.Kuaidiyun.key");
//	}
	protected function requestProduct($page = 1, $page_size = 100)
	{

		$response = $this->sendRequest("post", "/lipin/goods");

		$productList = $this->handleResponse($response);
		$warehouse = Warehouse::query()->where("channel_id", $this->channel)->get();
		$map = [];
		foreach ($warehouse as $k => $v) {
			$map[$v["ext_id"]] = $v["id"];
		}
		$i=0;
		foreach ($productList as $k => $v) {
//			$warehouse = Warehouse::firstByChannelAndExtId($this->channel, $v["kuaidi_id"]);
			
			$list[] = [
				"name" => $v["goods_name"],
				"thumb" => $v["goods_pic"],
				"up_cost_price" => (integer)floatval($v["price"] * 1000) / 10,
//				"weight" => $v["weight"] * 1000,
				"ext_id" => $v["goods_id"],
				"status" => PRODUCT_STATUS_OFFLINE,
				"warehouse_id" => $map[$v["kuaidi_id"]] ?? 0,
				"stock" => 9999,
				"sales" => rand(100, 9999),
				"up_status" => 1, // 上游商品状态 0:待上架 1:上架 -1 已删除
				"channel_id" => $this->channel,
			];
		}
		return $list;
	}

	protected function requestOrder($product, $userOrder, $orderConsignee)
	{
		$warehouse = Warehouse::query()->where("id",$userOrder["warehouse_id"])->first();
		$this->package_id = $orderConsignee->id;
		$rec_list[] = [
			"order_sn" => $orderConsignee->id,
			"rec_name" => $orderConsignee->consignee,
			"rec_mobile" => $orderConsignee->mobile,
			"rec_province" => $orderConsignee->province,
			"rec_city" => $orderConsignee->city,
			"rec_district" => $orderConsignee->district,
			"rec_address" => $orderConsignee->address,
		];
		$params = [
			//浙江省绍兴市上虞区曹娥街道经济开发区志云仓库 李薇薇 18058406712
			"platform"=>$this->source($userOrder["source"]),
			"kuaidi_id"=>$warehouse["ext_id"],
			"goods_id"=>$product["ext_id"],
			"cang_id"=>$warehouse["ext_express_id"],
			"send_name" => "李薇薇",
			"send_mobile" => "13291678475",
			"rec_list"=>$rec_list,
		];
		$is_policy = true;
		$response = $this->sendRequest("post", "/lipin/buy", $params);
		$req = $this->handleResponse($response);
		return [
			"third_order_sn" => $req[$orderConsignee->id]["order_sn"],
			"ext_order_sn" => "",
			"express_no" => $req[$orderConsignee->id]["kuaidi_sn"],
			"status" => PACKAGE_STATUS_SHIPPED,
			"sync_status" => USER_ORDER_SYNC_STATUS_SUCCESS,
		];
	}

	protected function requestOrderQuery($orderConsignee)
	{
		$params["order_sn"] = [$orderConsignee->id];
		$response = $this->sendRequest("post", "/lipin/status", $params);
		$api_req = $this->handleResponse($response);
		$result = [
			"express_no" => $api_req[$orderConsignee->id]["kuaidi_sn"],
			"sync_query_status" => USER_ORDER_SYNC_QUERY_STATUS_SUCCESS,
		];
		return $result;
	}

	public function requestOrderQueryV1($package_id)
	{
		$orderConsignee = OrderConsignee::query()->where("id", $package_id)->first();
		$params["orderNos"] = empty($orderConsignee->repeat_id) ? (string)$package_id : $orderConsignee->repeat_id;
		if ($orderConsignee["is_encryption"] == 1) {
			$params["orderNos"] = (string)$orderConsignee->ext_platform_order_sn;
		}
		$response = $this->sendRequest("post", "/openApi/orderQuery", $params);
		$req = $this->handleResponse($response);
		if (empty($req["items"][0]["mailNo"]) && $req["items"][0]["isCreate"] == false) {
			// leo说不重复请求了 跟上游确认单号不会重复 上游改验证传的订单所有信息是否唯一
//			OrderConsignee::query()->where("id",$package_id)->update(["repeat_id"=>$package_id.time()]);
			return false;
		}
		$orderRes = [
			"third_order_sn" => $req["items"][0]["mailNo"],
			"ext_order_sn" => $req["items"][0]["mailNo"],
			"express_no" => $req["items"][0]["mailNo"],
			"status" => PACKAGE_STATUS_SHIPPED,
			"sync_status" => USER_ORDER_SYNC_STATUS_SUCCESS,
		];
		OrderConsignee::query()->where("id", $package_id)->update($orderRes);
		$add_push = OrderConsigneePushDownService::addPush($package_id, 2); // 推送
		OrderConsigneeLogic::checkExpressNo($req["items"][0]["mailNo"], $package_id); // 检查单号是否重复报警
		return [
			"third_order_sn" => $req["items"][0]["mailNo"],
			"ext_order_sn" => $req["items"][0]["mailNo"],
			"express_no" => $req["items"][0]["mailNo"],
			"status" => PACKAGE_STATUS_SHIPPED,
			"sync_status" => USER_ORDER_SYNC_STATUS_SUCCESS,
		];
	}

	protected function requestCancelOrder($orderConsignee)
	{
		$params["mailNos"] = $orderConsignee->third_order_sn;
		$response = $this->sendRequest("post", "/openApi/orderCancel", $params);
		$req = $this->handleResponse($response);
		if ($req["items"][0]["IsSuccess"]) {
			return true;
		} else {
			CommonUtil::throwException(["229", $req["items"][0]["Message"]]);
		}

	}

	private function requestUserInfo()
	{
		$response = $this->sendRequest("post", "/openApi/UserInfoQuery");
		$info = $this->handleResponse($response);
		return $info;
	}

	public function getSign()
	{
		$password = "c13291678475";
		$username = "13291678475";
		$sign = $username . md5(md5($password)) . time();
		return md5($sign);
	}

	private function sendRequest(string $method, $uri, array $data = [])
	{
		$client = $this->getHttpClient();
		$params = $data;
		$params["username"] = $this->userName;
		$params["sid"] = time();
		$params["sign"] = $this->getSign();
		$uri = $this->baseUrl . $uri;
		$this->requestParams = $params;
		$this->requestUrl = $uri;
		return $client->request($method, $uri, [
			"json" => $params,
		]);
	}

	private function handleResponse(ResponseInterface $response)
	{
		$contents = $response->getBody()->getContents();
		if (is_string($contents)) {
			$contents = json_decode($contents, true);
		}
		$instance = new LoggerFactoryUtil(FabWarehouse::class);

		$instance->info("快递云返回数据" . json_encode([
				"params" => $this->requestParams,
				"response" => $contents,
			]));
//		dd($this->requestParams,$contents);
		if ($contents["code"] != 0) {
			$policy_msg["请求参数"] = $this->requestParams;
			$policy_msg["返回结果"] = $contents;
			QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM") . "新快递云仓库" . json_encode($policy_msg, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), env("ROUTINE_POLICY"));
			$class_name = CommonUtil::getClassName(get_class($this));
//			$function = '';
//			switch ($this->requestUrl) {
//				case $this->baseUrl . "/openApi/emptyTypeList":
//					$function = "获取商品";
//					break;
//				case $this->baseUrl . "/openApi/UserInfoQuery":
//					$function = "获取用户信息";
//					break;
//				case $this->baseUrl . "/openApi/orderCreate":
//					$function = "请求下单";
//					break;
//				case $this->baseUrl . "/openApi/orderCancel":
//					$function = "取消订单";
//					break;
//			}
//			$policy_msg = [
//				'功能' => $function,
//				'请求链接' => $this->requestUrl,
//				'请求参数' => $this->requestParams,
//				'响应结果' => $contents,
//				'信息时间' => date("Y-m-d H:i:s")
//			];
//			if ($function == "请求下单") {
//				$policy_msg["商品id"] = $this->baseProductId;
//				$policy_msg["仓库id"] = $this->baseExpressId;
//				$policy_msg["仓源id"] = $this->baseWarehouseId;
//			}
//			$instance->info("快递云返回msg1:" . $contents["msg"]);
//			if (strpos($contents["msg"], '请在收件人姓名或地址后增加分机号[含括号及4位数字]，请与下单人确认具体分机号') !== false) {
//				//地址停发 自动取消
//				$instance->info("快递云返回msg2:" . $contents["msg"]);
//				$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id, "已取消，请填写正确的分机号");
//				if ($cancel_req) {
//					OrderConsignee::updateById($this->package_id, ["cancel_type" => 4, "cancel_reason" => "已取消，请填写正确的分机号"]);
//				}
//				$policy_msg["msg"] = "退款已成功";
//			}
//			if (strpos($contents["msg"], '该收件地已停发') !== false) {
//				//地址停发 自动取消
//				$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id);
//				if ($cancel_req) {
//					OrderConsignee::updateById($this->package_id, ["cancel_type" => 2, "cancel_reason" => "该收件地已停发"]);
//				}
//				$policy_msg["msg"] = "退款已成功";
//				$is_policy = false;
//			}
//			if ($contents["msg"] == '账户余额不足') {
//				//地址停发 自动取消
//				$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id);
//				if ($cancel_req) {
//					OrderConsignee::updateById($this->package_id, ["cancel_type" => 2, "cancel_reason" => "该仓库不支持该地区"]);
//				}
//				$policy_msg["msg"] = "退款已成功";
//				$is_policy = false;
//			}
//			if (strpos($contents["msg"], '超出服务范围') !== false) {
//				//地址停发 自动取消
//				$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id);
//				if ($cancel_req) {
//					OrderConsignee::updateById($this->package_id, ["cancel_type" => 2, "cancel_reason" => "该收件地已停发"]);
//				}
//				$policy_msg["msg"] = "退款已成功";
//				$is_policy = false;
//			}
//
//			if (strpos($contents["msg"], '处于黑名单无法下单') !== false) {
//				//地址停发 自动取消
//				$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id);
//				if ($cancel_req) {
//					OrderConsignee::updateById($this->package_id, ["cancel_type" => 5, "cancel_reason" => "收货人已经被多个店铺标黑禁止一件代发"]);
//				}
//				$policy_msg["msg"] = "退款已成功";
//			}
//			if (strpos($contents["msg"], '该批订单已经执行过了,不可重复执行。如需执行请稍后重试或换个礼品。') !== false) {
//				$orderRes = $this->requestOrderQueryV1($this->package_id);
//				throw new OuterApiException(sprintf("%s error:%s", $class_name, json_encode($contents)));
//			}
//			if ($is_policy) {
//				if (empty($contents)) { // 如果快递云返回null 请求三次预警
//					/**
//					 * @var \Redis $redis
//					 */
//					$redis = app("redis");
//					$redis->incr("kuaidiyun_return_null");
//					$request_count = $redis->get("kuaidiyun_return_null");
//					$m = $request_count % 3;
//					// 每三次返回null预警一次
//					if (($request_count > 1) && ($m == 0)) {
//						// 超过三次预警
//						QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM") . "快递云仓库" . json_encode($policy_msg, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), env("ROUTINE_POLICY"));
//					}
//				} else {
//					QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM") . "快递云仓库" . json_encode($policy_msg, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), env("ROUTINE_POLICY"));
//				}
//			}
			throw new OuterApiException(sprintf("%s error:%s", $class_name, json_encode($contents)));
		}

		return $contents["data"] ?? [];
	}
}
