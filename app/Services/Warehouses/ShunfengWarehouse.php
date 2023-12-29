<?php


namespace App\Services\Warehouses;


use App\Enums\ErrorEnum;
use App\Enums\WarehouseChannelEnum;
use App\Exceptions\ApiException;
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

class ShunfengWarehouse extends AbstractWarehouse
{
	protected $baseUrl = '';
	protected $requestUrl = '';
	protected $channel = WarehouseChannelEnum::SHUNFENG;
	protected $package_id = "";
	protected $orderConsignee;
	protected $userOrder;
	// 订单编号平台来源：101-淘宝，102-天猫，201-京东，301-拼多多，401-其他
	//sourcePlatform
	private $expressStatus = [
		USER_ORDER_SOURCE_TAOBAO => 101,
		USER_ORDER_SOURCE_TMALL => 102,
		USER_ORDER_SOURCE_PDD => 301,
		USER_ORDER_SOURCE_JD => 201,
		USER_ORDER_SOURCE_OTHER => 401,
	];
	public function __construct(){
		$this->baseUrl = config("warehouse.shunfeng.domain");
	}
	private function getWarehousePrice($warehouse_ids = []) {
		$params["warehouse_ids"] = $warehouse_ids;
		$response = $this->sendRequest("post", "/admin/other/warehouse/price",json_encode($params));
		$req_data = $this->handleResponse($response);
		if(empty($req_data) || empty($req_data["weightPrice"])) {
			CommonUtil::throwException(ErrorEnum::ERROR_WAREHOUSE_PRICE);
		}
		return $req_data["weightPrice"];
	}
	private function getUserMoney() {
		$response = $this->sendRequest("post", "/admin/other/user/info");
		$req_data = $this->handleResponse($response);
		if($req_data["info"]["balance"] < 50) {
//			QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM")."顺丰仓库账户余额已不足50元");
			$policy_msg["msg"] = "顺丰仓库账户余额已不足50元";
			$policy_msg["剩余金额"] = $req_data["info"]["balance"];
			QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM").json_encode($policy_msg,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),env("CHANNEL_MONEY_POLICY"));
		}
	}

	/**
	 * @author ztt
	 * @return array|false|mixed
	 * @throws OuterApiException
	 * 获取仓库列表
	 */
	protected function requestWarehouse()
	{
		$response = $this->sendRequest("get", "/admin/other/warehouse/list");
		$req_data = $this->handleResponse($response);
		if(!isset($req_data["list"])) {
			return  false;
		}
		$expressList = $req_data["list"];
		if(empty($expressList)) {
			return  false;
		}
		$ext_ids = [];
		foreach ($expressList as $k => $v) {
			$ext_ids[] = $v["id"];
		}
		// 获取上游仓库价格列表
		$up_warehouse_price_info = $this->getWarehousePrice($ext_ids);
		$list = [];
		foreach ($expressList as $k => $v) {
			$price=0;
			foreach ($up_warehouse_price_info as $kk=>$vv) {
				if(($vv["warehouseId"] == $v["id"]) && ($vv["weightMin"] == 0)) { // 只取价格小于一千克的运费  产品说的
					$price = $vv["price"]*100;
					break;
				}
			}
			if($price ==0) {
				CommonUtil::throwException(ErrorEnum::ERROR_WAREHOUSE_PRICE);
			}
			$list[] = [
				"ext_id" => $v["id"],
				"name" => $v["name"],
				"typename" => $v["kdType"],
				"cost_price" => $price,
				"price" => $price+30, // 初始平台价
				"address" => $v["kdProvince"].$v["kdCity"].$v["kdArea"],
				"channel_id" => $this->channel,
				"status" => WARE_HOUSE_STATUS_NORMAL,
			];
		}
		return $list;
	}

	/**
	 * @author ztt
	 * @param int $page
	 * @param int $page_size
	 * 请求商品
	 * @return array|mixed
	 * @throws OuterApiException
	 */
	protected function requestProduct($page = 1, $page_size = 100)
	{
		$response = $this->sendRequest("get", "/admin/user/warehouse/goods/list?indexPageNum=1&warehouseId=1&size=1000&order=true&sortFieldNames=weights");
		$productList = $this->handleResponse($response);
		foreach ($productList["list"] as $k => $v) { 
			$warehouse = Warehouse::firstByChannelAndExtId($this->channel, 1); //仓库ID 暂时写死
			if (empty($warehouse)) {
				continue;
			}
			$list[] = [
				"name" => $v["name"],
				"thumb" => $v["picUrl"],
				"up_cost_price" => $v["price"] * 100,
				"weight" => $v["weight"] * 1000,
				"ext_id" => $v["id"],
				"channel_id" => $this->channel,
				"status" => PRODUCT_STATUS_OFFLINE,
				"warehouse_id" => $warehouse->id,
				"stock" => $v["stock"],
				"sales" => rand(100, 9999),
				"up_status"=>1  // 商品状态 0:待上架 1:上架 -1 已删除
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
		$this->getUserMoney();
		$this->package_id = $orderConsignee->id;
		$orderConsignee->user_id = $userOrder->user_id;
		$warehouse = Warehouse::getById($product->warehouse_id);
		$orderGoods[] = [
			"orderSn"=>(string)$orderConsignee->id,
			"consignee"=>$orderConsignee->consignee,
			"mobile"=>$orderConsignee->mobile,
			"province"=>$orderConsignee->province,
			"city"=>$orderConsignee->city,
			"district"=>$orderConsignee->district,
			"detailAddress"=>$orderConsignee->address,
			"warehouseId"=>(int)$warehouse->ext_id,
			"goodsName"=>"礼品",
			"goodsParam"=>["number"=>1,"goodsId"=>(int)$product->ext_id],
		];
		$params = [
			"node"=>$userOrder->remark,
			"sourcePlatform"=> $this->expressStatus[$userOrder->source],
			"orderGoods" => $orderGoods,
		];
		$response = $this->sendRequest("post", "/admin/other/warehouse/order", json_encode($params));
		$req = $this->handleResponse($response);
		if(empty($req["shipSnList"]) || $req["shipSnList"][0]["resultCode"] != "100") {
			QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM")."顺丰仓库".json_encode([
					'功能'=> "请求下单",
					'请求链接'=> $this->requestUrl,
					'请求参数'=> $this->requestParams,
					'响应结果'=> $this->apiResponse,
					"商品id"=>$this->baseProductId,
					"仓库id"=>$this->baseExpressId,
					"仓源id"=>$this->baseWarehouseId,
				],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
			CommonUtil::throwException(ErrorEnum::ERROR_SHENFENG_WAREHOUSE_ORDER);
		}
		return [
			"third_order_sn" => $req["shipSnList"][0]["orderSn"],
			"ext_order_sn" =>  $req["shipSnList"][0]["packageSn"],
			"express_no" => $req["shipSnList"][0]["shipSn"],
			"status" => PACKAGE_STATUS_SHIPPED,
			"sync_status" => USER_ORDER_SYNC_STATUS_SUCCESS,
		];
	}

	protected function requestOrderQuery($orderConsignee)
	{
		$params["packageSnList"][] = $orderConsignee->ext_order_sn;
		$response = $this->sendRequest("post", "/admin/other/order/info",json_encode($params));
		$api_req = $this->handleResponse($response);
		if(empty($api_req["list"][0]["shipSn"])) {
			if(($api_req["list"][0]["status"] == -3) || ($api_req["list"][0]["status"] ==7)) {
				//获取单号订单状态为取消 自动取消
				$cancel_req = ChannelSyncLogic::syncCancelPackage($orderConsignee->id);
				$add_push = OrderConsigneePushDownService::addPush($orderConsignee->id,1);// 如果是api用户 添加推送信息
				$policy_msg = [
					'功能'=>"获取运单号失败 苍源订单为退款状态",
					'请求链接'=> $this->requestUrl,
					'请求参数'=> $this->requestParams,
					'响应结果'=> $this->apiResponse,
					'信息时间'=>date("Y-m-d H:i:s"),
					'提示消息'=>"退款已成功"
				];
				QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM")."顺丰仓库".json_encode($policy_msg,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),env("POLICE_CODE"));
			}
		}
		return [
			"express_no" => $api_req["list"][0]["shipSn"],
			"sync_query_status" => USER_ORDER_SYNC_QUERY_STATUS_SUCCESS,
		];
	}
	protected function cancelQueryOrder($orderConsignee)
	{
		$params["packageSnList"][] = $orderConsignee->ext_order_sn;
		$response = $this->sendRequest("post", "/admin/other/order/info",json_encode($params));
		$api_req = $this->handleResponse($response);
		return $api_req["list"][0]["status"]; // "status":-1, // 状态：-1：已出单，-2 未出单 -3：用户取消， 5：已支付未出单 6已支付待出单 7管理员取消
	}
	protected function requestCancelOrder($orderConsignee)
	{
		$status = $this->cancelQueryOrder($orderConsignee);
		if(($status == -3) || ($status == 7))  {
			return true;
		}
		return false; //不支持取消
	}
	private function sendRequest(string $method, $uri,  $data = "")
	{
		$secret = "b1d518d6578c7289ff10d1f05c0b51ed";
		$client = $this->getHttpClient();
		$params["param"] = $data;
		$params["userId"] = 373;
		$params["t"] = time();
		$params["sign"] = md5($params["param"].$params["t"].$params["userId"].$secret);
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
		$this->apiResponse = $contents;
		$instance = new LoggerFactoryUtil(FabWarehouse::class);
		$instance->info("顺丰苍源返回数据" . json_encode([
				"params" => $this->requestParams,
				"response" => $contents,
			]));
		if ($contents["code"] != 1 || $contents["msg"] == "系统错误，请联系开发者") {
			$class_name = CommonUtil::getClassName(get_class($this));
			$function="";
			switch ($this->requestUrl){
				case $this->baseUrl . "/admin/other/warehouse/price": $function="获取运费"; break;
				case $this->baseUrl . "/admin/other/user/info": $function="获取用户信息"; break;
				case $this->baseUrl . "/admin/other/warehouse/list": $function="获取仓库列表"; break;
				case $this->baseUrl . "/admin/user/warehouse/goods/list?indexPageNum=1&warehouseId=1&size=1000&order=true&sortFieldNames=weights": $function="获取商品"; break;
				case $this->baseUrl . "/admin/other/warehouse/order": $function="请求下单"; break;
				case $this->baseUrl . "/admin/other/order/info": $function="获取订单"; break;
			}
			$policy_msg = [
				'功能'=>$function,
				'请求链接'=> $this->requestUrl,
				'请求参数'=> $this->requestParams,
				'响应结果'=> $contents,
				'信息时间'=>date("Y-m-d H:i:s"),
				'damaijia_user_id'=>$this->damaijia_user_id
			];
			if($contents["code"] == 11001 && $contents["msg"] == "商品库存不足") {
				//商品库存不足 自动取消
				$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id);
				if($cancel_req) {
					OrderConsignee::updateById($this->package_id,["cancel_type"=>4,"cancel_reason"=>"商品库存不足,请换其他商品"]);
				}
				$add_push = OrderConsigneePushDownService::addPush($this->package_id,1);// 如果是api用户 添加推送信息
				$new["user_id"] = $this->userOrder->user_id;
				$new["remark"] = "商品库存不足";
				$new["type"] = 1;
				$new["order_id"] = $this->userOrder->id;
				$new["package_id"] = $this->package_id;
				NewsModel::create($new); //创建通知
				$policy_msg["msg"] = "退款已成功";
			}
			QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM")."顺丰仓库".json_encode($policy_msg,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
			
			throw new OuterApiException(sprintf("%s error:%s", $class_name, json_encode($contents)));
		}
		return $contents["data"] ?? [];
	}
}
