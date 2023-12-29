<?php


namespace App\Services\Warehouses;


use App\Enums\WarehouseChannelEnum;
use App\Exceptions\OuterApiException;
use App\Helper\CommonUtil;
use App\Http\Logic\ChannelSyncLogic;
use App\Http\Utils\LoggerFactoryUtil;
use App\Models\NewsModel;
use App\Models\OrderConsignee;
use App\Models\Warehouse;
use App\Services\OrderConsigneePushDownService;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Tool\ShanTaoTool\QiWeiTool;

class YinliuheLipwWarehouse extends AbstractWarehouse
{
	private $appKey; //"90735011";
	private $secret; // "79f37b6bdf77f94a8f2696d0846cf651";
	protected $baseUrl; // "http://yinliuhe.lipw.com/api/app";
	protected $channel = WarehouseChannelEnum::YINLIUHELIPW;
	private $belongTerrace = [
		USER_ORDER_SOURCE_TAOBAO => "TAOBAO",
		USER_ORDER_SOURCE_TMALL => "TAOBAO",
		USER_ORDER_SOURCE_PDD => "PDD",
		USER_ORDER_SOURCE_JD => "JD",
		USER_ORDER_SOURCE_OTHER => "OTHER",
	];

	protected $package_id = "";
	protected $orderConsignee;
	protected $userOrder;
	public function __construct()
	{
		$this->baseUrl = config("warehouse.yinliuhelipw.domain");
		$this->appKey = config("warehouse.yinliuhelipw.appKey");
		$this->secret = config("warehouse.yinliuhelipw.secret");
	}
	/*
	 * 获取运费
	 */
	private function getExpressPrice($uid)
	{
		$params["uid"] = $uid;
		$response = $this->sendRequest("post", "address.logistics.list", $params);
		$expressList = $this->handleResponse($response); // 快递列表
		return $expressList;
	}
	
	protected function requestWarehouse()
	{
		$params["platform"] = "TAOBAO";
		$response = $this->sendRequest("post", "address.list", $params);
		$warehouseList = $this->handleResponse($response); // 仓库列表
		$list=[];
		foreach ($warehouseList as $warehouseKey=>$warehouseValue) {
			try {
				$expressInfo = $this->getExpressPrice($warehouseValue["uid"]);
				foreach ($expressInfo as $expressKey=>$expressValue) {
					$list[] = [
						"ext_id" => $warehouseValue["uid"],
						"ext_express_id"=>$expressValue["id"],
						"name" => $warehouseValue["province"].$warehouseValue["city"],
						"typename" => $expressValue["name"],
						"cost_price" => $expressValue["price"]*100,
						"price" => $expressValue["price"]*100, // 初始平台价
						"address" => $warehouseValue["province"],
						"channel_id" => $this->channel,
						"status" => WARE_HOUSE_STATUS_NORMAL,
					];
				}
			} catch (\Exception $e) {
				continue;
			}
		}
		return $list;
	}

	protected function requestProduct($page = 1, $page_size = 100)
	{
		$params["pageSize"] = 100;
		$params["pageNum"] = 1;
		$response = $this->sendRequest("post", "address.all", $params);
		$productList = $this->handleResponse($response); // 商品列表
		foreach ($productList as $k => $v) {
			$warehouse = Warehouse::query()->where(["channel_id"=>$this->channel,"ext_id"=>$v["uid"],"ext_express_id"=>$v["logisticsId"]])->first();
			if (empty($warehouse)) {
				continue;
			}
			$list[] = [
				"name" => $v["goodsName"].$v["platform"],
				"thumb" => $v["picture"],
				"up_cost_price" => $v["goodsPrice"] * 100,
				"weight" => $v["weight"],
				"ext_id" => $v["goodsId"],
				"channel_id" => $this->channel,
				"status" => PRODUCT_STATUS_OFFLINE,
				"warehouse_id" => $warehouse->id,
				"stock" => $v["stock"],
				"sales" => rand(100, 9999),
			];
		}
		return $list;
	}

	protected function requestOrder($product, $userOrder, $orderConsignee)
	{
		$this->package_id = $orderConsignee->id;
		$orderConsignee->user_id = $userOrder->user_id;
		$this->orderConsignee = $orderConsignee;
		$this->userOrder = $userOrder;
		$warehouse = Warehouse::getById($product->warehouse_id);
		$params["logisticsId"] = $warehouse->ext_express_id;
		$params["goodsId"] = $product->ext_id;
		$params["platformType"] = $this->belongTerrace[$userOrder->source];
		$detailArray["orderNo"] = $orderConsignee->id;
		$detailArray["province"] = $orderConsignee->province;
		$detailArray["city"] = $orderConsignee->city;
		$detailArray["county"] = $orderConsignee->district;
		$detailArray["address"] = $orderConsignee->address;
		$detailArray["name"] = $orderConsignee->consignee;
		$detailArray["mobile"] = $orderConsignee->mobile;
		$params["detailArray"] = [$detailArray];
		$response = $this->sendRequest("post", "order.insert.v2", $params);
		$req = $this->handleResponse($response); 
		return [
			"third_order_sn" => $req["no"],
			"ext_order_sn" => "",
			"status" => PACKAGE_STATUS_SHIPPED,
			"sync_status" => USER_ORDER_SYNC_STATUS_SUCCESS,
		];
	}
	protected function requestOrderQuery($orderConsignee)
	{
		$params["no"] = $orderConsignee->third_order_sn;
		$response = $this->sendRequest("post", "order.detail.list", $params);
		$req = $this->handleResponse($response);
		$req = $req["list"];
		if(!empty($req)) {
			foreach ($req as $k=>$v) {
				if($orderConsignee["id"] == $v["orderNo"]) {
					if(empty($v["waybillNo"])) {
						$status = $v["status"];
						if(($status == "REFUND") || ($status == "CLOSE")) {
							//获取单号订单状态为取消 自动取消
							$cancel_req = ChannelSyncLogic::syncCancelPackage($orderConsignee->id);
							$add_push = OrderConsigneePushDownService::addPush($orderConsignee->id,1);// 如果是api用户 添加推送信息
							$policy_msg = [
								'功能'=>"获取运单号失败 苍源订单为退款状态",
								'请求链接'=> $this->requestUrl,
								'请求参数'=> $this->requestParams,
								'响应结果'=> $this->apiResponse,
								'信息时间'=>date("Y-m-d H:i:s"),
								'提示消息'=>"退款已成功",
								'damaijia_user_id'=>$this->damaijia_user_id
							];
							QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM")."闪电发货仓库".json_encode($policy_msg,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),env("POLICE_CODE"));
							break;
						}
					}
					return [
						"express_no" => $v["waybillNo"],
						"sync_status" => USER_ORDER_SYNC_STATUS_SUCCESS,
						"express_company_name" => $v["waybillCompany"],
						"sync_query_status" => USER_ORDER_SYNC_QUERY_STATUS_SUCCESS,
					]; 
				}
			}
		}
		return false;
	}
	protected function requestOrderQueryV0($orderConsignee)
	{
		$params["no"] = $orderConsignee->third_order_sn;
		$response = $this->sendRequest("post", "order.detail.list", $params);
		$req = $this->handleResponse($response);
		$req = $req["list"];
		if(!empty($req)) {
			if(empty($req[0]["waybillNo"])) {
				$status = $req[0]["status"];
				if(($status == "REFUND") || ($status == "CLOSE")) {
					//获取单号订单状态为取消 自动取消
					$cancel_req = ChannelSyncLogic::syncCancelPackage($orderConsignee->id);
					$add_push = OrderConsigneePushDownService::addPush($orderConsignee->id,1);// 如果是api用户 添加推送信息
					$policy_msg = [
						'功能'=>"获取运单号失败 苍源订单为退款状态",
						'请求链接'=> $this->requestUrl,
						'请求参数'=> $this->requestParams,
						'响应结果'=> $this->apiResponse,
						'信息时间'=>date("Y-m-d H:i:s"),
						'提示消息'=>"退款已成功",
						'damaijia_user_id'=>$this->damaijia_user_id
					];
					QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM")."闪电发货仓库".json_encode($policy_msg,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),env("POLICE_CODE"));
				}
			}
			return [
				"express_no" => $req[0]["waybillNo"],
				"sync_status" => USER_ORDER_SYNC_STATUS_SUCCESS,
				"express_company_name" => $req[0]["waybillCompany"],
				"sync_query_status" => USER_ORDER_SYNC_QUERY_STATUS_SUCCESS,
			];
		}
		return false;
	}

	protected function requestCancelOrder($orderConsignee)
	{
		$params["no"] = $orderConsignee->third_order_sn;
		$response = $this->sendRequest("post", "order.detail.list", $params);
		$req = $this->handleResponse($response);
		$req = $req["list"];
		if(!empty($req)) {
			// UNPAID 未付款；IN 已付款； ABNORMAL 异常；CANCEL 取消；REFUND 退款；SUCCESS 成功；CLOSE 关闭
			if(!empty($req[0]["status"]) && $req[0]["status"] == "REFUND") {
				return	true;
			}
			if(!empty($req[0]["status"]) && $req[0]["status"] == "CLOSE") {
				return	true;
			}
		}
		return false;
	}

	private function sendRequest(string $method, $uri, array $data = [])
	{
		$time = $this->getMsec();
		$sign = $this->sign($uri,json_encode($data),$time);
		$client = $this->getHttpClient();
		$params = [
			"method"=>$uri,
			"sign" => $sign,
			"appkey" => $this->appKey,
			"body" => json_encode($data),
			"timestamp" => $time,
		];
		$uri = $this->baseUrl;
		$this->requestParams = $params;
		$this->requestUrl = $uri;
		return $client->request($method, $uri, [
			"json" => $params
		]);
	}

	private function handleResponse(ResponseInterface $response)
	{
		$contents = $response->getBody()->getContents();
		if (is_string($contents)) {
			$contents = json_decode($contents, true);
		}
		$this->apiResponse = $contents;
		$instance = new LoggerFactoryUtil(ShenzhenYundaWarehouse::class);
		$instance->info("闪电发货" . json_encode([
				'url' => $this->requestUrl,
				"params" => $this->requestParams,
				"response" => $contents,
			]));
		if (!$contents) {
			throw new OuterApiException(sprintf("%s error:%s", CommonUtil::getClassName(get_class($this)), json_encode($contents)));
		}
		if ($contents["code"] != 0) {
			$function = "";
			$method = $this->requestParams["method"];
			switch ($this->requestUrl) {
				case "address.list":
					$function = "获取仓库";
					break;
				case "address.all":
					$function = "获取商品";
					break;
				case "address.logistics.list":
					$function = "获取快递";
					break;
				case "order.insert.v2":
					$function = "请求下单";
					break;
				case  "order.detail.list":
					$function = "查询订单";
					break;
			}
			$class_name = CommonUtil::getClassName(get_class($this));
			$policy_msg = [
				'功能' => $function,
				'请求链接' => $this->requestUrl,
				'请求参数' => $this->requestParams,
				'响应结果' => $contents,
				'信息时间' => date("Y-m-d H:i:s")
			];
			if($function=="请求下单") {
				$policy_msg["商品id"] = $this->baseProductId;
				$policy_msg["仓库id"] = $this->baseExpressId;
				$policy_msg["仓源id"] = $this->baseWarehouseId;
			}
			if($contents["code"] == 999 && mb_substr($contents["msg"],0,12,"utf-8") == "通过物流不发货地点过滤后") {
				//地址停发 自动取消
				$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id,"地址已停发, 请换其他快递公司");
				if($cancel_req) {
					OrderConsignee::updateById($this->package_id,["cancel_type"=>2,"cancel_reason"=>"地址已停发, 请换其他快递公司"]);
				}
//				$add_push = OrderConsigneePushDownService::addPush($this->package_id,1);// 如果是api用户 添加推送信息
				$new["user_id"] = $this->userOrder->user_id;
				$new["remark"] = "地址停发";
				$new["type"] = 1;
				$new["order_id"] = $this->userOrder->id;
				$new["package_id"] = $this->package_id;
				NewsModel::create($new); //创建通知
				$policy_msg["msg"] = "退款已成功";
				$policy_msg["damaijia_user_id"] = $this->damaijia_user_id;
			}
			
			QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM") . "闪电发货仓库" . json_encode($policy_msg, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), env("POLICE_CODE"));
			throw new OuterApiException(sprintf("%s error:%s", $class_name, json_encode($contents)));
		}
		return $contents["data"] ?? [];
	}
	// 获取签名
	private function sign($method,$data,$time)
	{
		$body["method"] = $method;
		$body["appkey"] = $this->appKey;
		$body["body"] = $data;
		$body["timestamp"] = $time;
		ksort($body);
		$bodyStr = "";
		foreach ($body as $k => $v) {
			$bodyStr .= $k . $v;
		}
		$bodyStr = $this->secret . $bodyStr . $this->secret;
		$bodyStrMd5Bin2hex = bin2hex(md5($bodyStr, true));
		return strtoupper($bodyStrMd5Bin2hex);
	}
}
