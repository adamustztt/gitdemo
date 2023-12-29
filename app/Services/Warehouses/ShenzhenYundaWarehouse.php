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
use App\Models\Warehouse;
use App\Services\OrderConsigneePushDownService;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Tool\ShanTaoTool\QiWeiTool;

class ShenzhenYundaWarehouse extends AbstractWarehouse
{
	protected $baseUrl;// "http://zz.lipin100.vip";
	protected $username; // "yaodoudou";
	protected $password; //"lqqc3517294
	protected $sign;
	protected $channel = WarehouseChannelEnum::SHENZHENYUNDA;

	public function __construct()
	{
		$this->baseUrl = config("warehouse.shenzhenyunda.domain");
		$this->username = config("warehouse.shenzhenyunda.username");
		$this->password = config("warehouse.shenzhenyunda.password");
	}
	/**
	 * @param $expressSheetId
	 * @return mixed
	 * @throws ApiException
	 * @throws OuterApiException
	 * @author ztt
	 * 获取面单价格（快递价格）
	 */
	private function getExpressPrice()
	{
		$params = [];
		$response = $this->sendRequest("post", "/api/lipin/list", $params);
		$api_result = $this->handleResponse($response);
		return $api_result;
	}

	protected function requestWarehouse()
	{
		$expressList = $this->getExpressPrice(); // 快递列表
		$response = $this->sendRequest("post", "/api/lipin/cang");
		$warehouseList = $this->handleResponse($response); // 仓库列表
		$list = [];
		foreach ($warehouseList as $k => $warehouse) {
			$cost_price = '';
			$typename = '';
			$ext_express_id = "";
			foreach ($expressList as $kk => $express) {
				$cost_price = '';
				$typename = '';
				$ext_express_id = "";
				if ($express["cang"] == $warehouse["cang_id"]) {
					$cost_price = $express["price"];
					$typename = $express["name"];
					$ext_express_id = $express["id"];
					break;
				}
			}
			if (empty($cost_price)) {
				$policy_msg = [
					'功能' => "获取仓库",
					"错误" => "仓库未获取到运费",
					'请求链接' => $this->requestUrl,
					'请求参数' => $this->requestParams,
					'仓库列表响应结果' => $warehouseList,
					'快递列表响应结果' => $expressList,
					'信息时间' => date("Y-m-d H:i:s")
				];
//				QiWeiTool::sendMessageToBaoJing(
//					env("POLICE_FROM") . "深圳韵达上游" . json_encode($policy_msg, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
//					env("POLICE_CODE")
//				);
				continue;
			}
			$list[] = [
				"ext_id" => $warehouse["cang_id"],
				"ext_express_id" => $ext_express_id,
				"name" => $warehouse["cang_name"],
				"typename" => $typename,
				"cost_price" => (int)(string)($cost_price * 100),
				"price" => $cost_price * 100, // 初始平台价
				"address" => $warehouse["send_province"] . $warehouse["send_city"] . $warehouse["send_district"] . $warehouse["send_address"],
				"channel_id" => $this->channel,
				"status" => WARE_HOUSE_STATUS_NORMAL,
			];
		}
		return $list;
	}

	protected function requestProduct($page = 1, $page_size = 100)
	{
		$params = [];
		$response = $this->sendRequest("post", "/api/lipin/goods", $params);
		$list = [];
		$productList = $this->handleResponse($response);

		foreach ($productList as $k => $v) {
			$warehouse = Warehouse::query()->where(["channel_id" => $this->channel, "ext_express_id" => $v["kuaidi_id"]])->first();
			if (empty($warehouse)) {
				continue;
			}
			$list[] = [
				"name" => $v["goods_name"],
				"thumb" => $v["goods_pic"],
				"up_cost_price" => $v["price"] * 100,
				"weight" => $v["goods_weight"] * 1000,
				"ext_id" => $v["goods_id"],
				"channel_id" => $this->channel,
				"status" => PRODUCT_STATUS_OFFLINE,
				"warehouse_id" => $warehouse->id,
				"stock" => "9999",
				"sales" => rand(100, 9999),
			];
		}
		return $list;
	}

	protected function requestOrder($product, $userOrder, $orderConsignee)
	{
		$warehouse = Warehouse::getById($product->warehouse_id);
		// 发件人 浙江省绍兴市上虞区曹娥街道经济开发区志云仓库 李薇薇 18058406712
		// 测试下单收件人：姚兜兜   13291862254    浙江杭州市西湖区文二路西湖科技大 D座一楼大厅
		$params = [
			"goods_id" => (int)$product->ext_id, //用户自己的任务 id (唯一, 不能重复下单)
			"kuaidi_id" => $warehouse->ext_express_id, //第三方快递ID
			"cang_id" => $warehouse->ext_id,  // 第三方仓库ID
			"send_name" => "李薇薇",
			"send_mobile" => "13291678475",
			"send_province" => "浙江省",
			"send_city" => "绍兴市",
			"send_district" => "上虞区",
			"send_address" => "曹娥街道经济开发区志云仓库",
			"rec_list" => [
				[
					"order_sn" => (string)$orderConsignee->id,
					"rec_name" => $orderConsignee->consignee,
					"rec_mobile" => $orderConsignee->mobile,
					"rec_province" => $orderConsignee->province,
					"rec_city" => $orderConsignee->city,
					"rec_district" => $orderConsignee->district,
					"rec_address" => $orderConsignee->address,
					"shop_order" => $orderConsignee->ext_platform_order_sn,
				]
			],
		];
		$response = $this->sendRequest("post", "/api/lipin/buy", $params);
		$api_result = $this->handleResponse($response);
//		$req["220682"] =["order_sn"=>"220682","kuaidi_sn"=>"4607960843506","status"=>3];
		$req = array_values($api_result);
		if(!empty($req) && !empty( $req[0]["order_sn"])) {
			return [
				"third_order_sn" => $req[0]["order_sn"],
				"ext_order_sn" => $req[0]["order_sn"],
				"express_no" => $req[0]["kuaidi_sn"],
				"status" => PACKAGE_STATUS_SHIPPED,
				"sync_status" => USER_ORDER_SYNC_STATUS_SUCCESS,
			]; 
		}
		$this->policy($req,"深圳韵达仓库");
		CommonUtil::throwException([100000,"深圳韵达下单失败"]);
	}

	protected function requestOrderQuery($orderConsignee)
	{
		$params["order_sn"] = [$orderConsignee->third_order_sn];
		$response = $this->sendRequest("post", "/api/lipin/status", $params);
		$api_result = $this->handleResponse($response);
		$req = array_values($api_result);
		if(!empty($req) && !empty( $req[0]["order_sn"])) {
			return [
				"express_no" => $req[0]["kuaidi_sn"],
				"sync_status" => USER_ORDER_SYNC_STATUS_SUCCESS,
				"express_company_name" => $req[0]["kuaidi_name"],
				"sync_query_status" => USER_ORDER_SYNC_QUERY_STATUS_SUCCESS,
			];
		}
		$this->policy($req,"深圳韵达仓库");
		CommonUtil::throwException([100000,"深圳韵达查询失败"]);
		return false;
	}

	protected function requestCancelOrder($orderConsignee)
	{
		return false;
	}

	private function sendRequest(string $method, $uri, array $data = [])
	{
		$client = $this->getHttpClient();
		$time = time();
		$common_params = [
			"username" => $this->username,
			"sid" => $time,
			"sign" => md5($this->username . md5(md5($this->password)) . $time)
		];
		$params = array_merge($common_params, $data);
		$uri = $this->baseUrl . $uri;
		$this->requestParams = $params;
		$this->requestUrl = $uri;
		return $client->request($method, $uri, [
			"form_params" => $params,
			"headers" => [
				"Authorization" => $this->token
			]
		]);
	}

	private function handleResponse(ResponseInterface $response)
	{
		$contents = $response->getBody()->getContents();
		if (is_string($contents)) {
			$contents = json_decode($contents, true);
		}
		$instance = new LoggerFactoryUtil(ShenzhenYundaWarehouse::class);
		$instance->info("深圳韵达" . json_encode([
				'url' => $this->requestUrl,
				"params" => $this->requestParams,
				"response" => $contents,
			]));
		if (!$contents) {
			throw new OuterApiException(sprintf("%s error:%s", CommonUtil::getClassName(get_class($this)), json_encode($contents)));
		}
		if ($contents["code"] != 0) {
			$function = "";
			switch ($this->requestUrl) {
				case $this->baseUrl . "/api/lipin/cang":
					$function = "获取仓库";
					break;
				case $this->baseUrl . "/api/lipin/goods":
					$function = "获取商品";
					break;
				case $this->baseUrl . "/api/lipin/list":
					$function = "获取快递价格";
					break;
				case $this->baseUrl . "/api/lipin/buy":
					$function = "请求下单";
					break;
				case $this->baseUrl . "/api/lipin/status":
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
				'damaijia_user_id'=>$this->damaijia_user_id
			];
			if($function=="请求下单") {
				$policy_msg["商品id"] = $this->baseProductId;
				$policy_msg["仓库id"] = $this->baseExpressId;
				$policy_msg["仓源id"] = $this->baseWarehouseId;
			}
			QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM") . "深圳韵达仓库" . json_encode($policy_msg, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), env("POLICE_CODE"));
			throw new OuterApiException(sprintf("%s error:%s", $class_name, json_encode($contents)));
		}

		return $contents["data"] ?? [];
	}
}
