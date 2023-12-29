<?php


namespace App\Services\Warehouses;


use App\Enums\ErrorEnum;
use App\Enums\WarehouseChannelEnum;
use App\Exceptions\ApiException;
use App\Exceptions\OuterApiException;
use App\Helper\CommonUtil;
use App\Http\Controllers\ChannelSyncController;
use App\Http\Logic\ChannelSyncLogic;
use App\Http\Utils\LoggerFactoryUtil;
use App\Models\NewsModel;
use App\Models\OrderConsignee;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\OrderConsigneePushDownService;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Psr\Http\Message\ResponseInterface;
use Tool\ShanTaoTool\QiWeiTool;

class KuaixiaojianWarehouse extends AbstractWarehouse
{
	protected $baseUrl = "";
	protected $token = "";
	protected $account = "";
	protected $password = "";
	protected $secret = "";
	protected $package_id = "";
	protected $orderConsignee;
	protected $userOrder;
	protected $channel = WarehouseChannelEnum::KUAIXIAOJIAN;

	/**
	 * "belongTerrace" 1: 其他 2: 淘宝/天猫 3: 京东 4: 拼多多
	 * @var int[]
	 */
	private $belongTerrace = [
		USER_ORDER_SOURCE_TAOBAO => 2,
		USER_ORDER_SOURCE_TMALL => 2,
		USER_ORDER_SOURCE_PDD => 4,
		USER_ORDER_SOURCE_JD => 3,
		USER_ORDER_SOURCE_OTHER => 1,
	];
	/**
	 * "expressStatus": 1, //0 其他面单（不自动生成单号）, 1 通用电子面单，2 菜鸟电子面单，3 拼多多 4 京东
	 * @var int[]
	 */
	private $expressStatus = [
		USER_ORDER_SOURCE_TAOBAO => 2,
		USER_ORDER_SOURCE_TMALL => 2,
		USER_ORDER_SOURCE_PDD => 3,
		USER_ORDER_SOURCE_JD => 4,
		USER_ORDER_SOURCE_OTHER => 1,
	];

	public function __construct()
	{
		$this->account = config("warehouse.kuaixiaojian.account");
		$this->password = config("warehouse.kuaixiaojian.password");
		$this->secret = config("warehouse.kuaixiaojian.secret");
		$this->baseUrl = config("warehouse.kuaixiaojian.domain");
		$this->token = $this->login()["token"];
	}

	/**
	 * @param $storeId
	 * 获取面单（快递信息）
	 * @param int $expressStatus
	 * @return mixed
	 * @throws ApiException
	 * @throws OuterApiException
	 * @author ztt
	 */
	private function getExpressInfo($storeId, $expressStatus = "",$order_consignee_id=0)
	{
		$params = ["storeId" => $storeId];
		$response = $this->sendRequest("post", "/api/bus/expressSheet/list", $params);
		$expressList = $this->handleResponse($response);
		// "expressStatus": 1, //0 其他面单（不自动生成单号）, 1 通用电子面单，2 菜鸟电子面单，3 拼多多 4 京东
		if(empty($expressStatus)) { // 仓库成本价  不同面单成本价一样的
			foreach ($expressList as $k => $v) {
				return $v;
			}
		}
		foreach ($expressList as $k => $v) {
			if ($v["expressStatus"] == $expressStatus) {
				return $v;
			}
		}
        //判断该仓库的面单是否有可替换的   1 通用电子面单，2 菜鸟电子面单(天猫、淘宝)，3 拼多多 4 京东
        //特殊处理(仓库为ext_id=42,京东用拼多多免单代替)
        if($expressStatus==4 && $storeId==42){
            foreach ($expressList as $k => $v) {
                if ($v["expressStatus"] == 3) {
                    return $v;
                }
            }
        }

		$errorMsg = [
		    0=>"其他面单",
		    1=>"通用电子面单",
		    2=>"菜鸟电子面单",
		    3=>"拼多多面单",
		    4=>"京东面单",
        ];
		$err = isset($errorMsg[$expressStatus])?"快小件仓库ID:$storeId ".$errorMsg[$expressStatus]."不存在":"快小件仓库面单类型不存在";
		$policy_msg["请求获取面单接口"] = "/api/bus/expressSheet/list";
		$policy_msg["仓库ID"] = $storeId;
		$policy_msg["包裹ID"] = $order_consignee_id;
		$policy_msg["返回结果"] = $expressList;
		QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM")."快小件仓库获取面单失败".json_encode($policy_msg,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
		CommonUtil::throwException([233,$err]);
	}

	/**
	 * @param $expressSheetId
	 * @return mixed
	 * @throws ApiException
	 * @throws OuterApiException
	 * @author ztt
	 * 获取面单价格（快递价格）
	 */
	private function getExpressPrice($expressSheetId)
	{
		$params = ["expressSheetId" => $expressSheetId];
		$response = $this->sendRequest("post", "/api/bus/vipLevel/getExpressFree", $params);
		$info = $this->handleResponse($response);
		foreach ($info["list"] as $k => $v) {
			if ($v["min_weight"] == 0) {
				return $v["price"];
			}
		}
		CommonUtil::throwException(ErrorEnum::ERROR_HANDLE_UP_PARAMS);
	}

	/**
	 * @return mixed
	 * @throws ApiException
	 * @throws OuterApiException
	 * @author ztt
	 * 获取仓库信息
	 */
	public function requestWarehouse()
	{
		$response = $this->sendRequest("post", "/api/bus/goodsInfo/public/getStore");
		$expressList = $this->handleResponse($response);
		$list = [];
		foreach ($expressList as $k => $v) {
			try {
				$expressInfo = $this->getExpressInfo($v["id"]);
				if(!$expressInfo) {
					continue;
				}
				$expressPrice = $this->getExpressPrice($expressInfo["id"]);
				if(!$expressPrice) {
					continue;
				}
			} catch (\Exception $e) {
				continue;
			}
			
			$list[] = [
				"ext_id" => $v["id"],
				"name" => $v["name"],
				"typename" => $expressInfo["shipperName"],
				"cost_price" => $expressPrice * 100,
				"price" => 160, // 初始平台价
				"address" => "",
				"channel_id" => $this->channel,
				"status" => WARE_HOUSE_STATUS_NORMAL,
			];
		}
		return $list;
	}

	/**
	 * @param int $page
	 * @param int $page_size
	 * @return array|mixed
	 * @throws OuterApiException
	 * @author ztt
	 * 获取商品信息
	 */
	public function requestProduct($page = 1, $page_size = 100)
	{
		$params = [
			"pageNo" => $page,
			"pageSize" => $page_size,
		];
		$response = $this->sendRequest("post", "/api/bus/goodsInfo/public/list", $params);
		$list=[];
		$productList = $this->handleResponse($response);
		foreach ($productList["list"] as $k => $v) {
			$warehouse = Warehouse::firstByChannelAndExtId($this->channel, $v["storeId"]);
			if (empty($warehouse)) {
				continue;
			}
			if($v["giStatus"] == 1) { //"giStatus": 1, // 商品状态 0:待上架 1:上架 -1 已删除
				$up_status = 1;
			} else {
				$up_status = 2;
			}
			$list[] = [
				"name" => $v["name"],
				"thumb" => "https://www.kuaixiaojian.com".$v["cover"],
				"up_cost_price" => $v["price"] * 100,
				"weight" => $v["weight"] * 1000,
				"ext_id" => $v["giId"],
				"channel_id" => $this->channel,
				"status" => PRODUCT_STATUS_OFFLINE,
				"warehouse_id" => $warehouse->id,
				"stock" => $v["repertory"],
				"sales" => rand(100, 9999),
				"up_status"=>$up_status
			];
		}
		return $list;
	}

	public function requestOrder($product, $userOrder, $orderConsignee)
	{
		$this->package_id = $orderConsignee->id;
		$orderConsignee->user_id = $userOrder->user_id;
		$this->orderConsignee = $orderConsignee;
		$this->userOrder = $userOrder;
		$warehouse = Warehouse::getById($product->warehouse_id);
		$instance = new LoggerFactoryUtil(KuaixiaojianWarehouse::class);
		$instance->info("快小件数据的快递状态:".$this->expressStatus[$userOrder->source]);
		$expressInfo = $this->getExpressInfo($warehouse->ext_id, $this->expressStatus[$userOrder->source],$orderConsignee->id);
		$recipientInfo[] = [
			"orderNo" => (string)$orderConsignee->id,
			"province" => $orderConsignee->province,
			"phone" => $orderConsignee->mobile,
			"city" => $orderConsignee->city,
			"name" => $orderConsignee->consignee,
			"county" => $orderConsignee->district,
			"address" => $orderConsignee->address,
			"tradeName" => $product->name,
			"encryptName" => "",
			"encryptPhone" => "",
			"encryptAddress" => "",
		];
		$params = [
			"goodsId" => (int)$product->ext_id, //用户自己的任务 id (唯一, 不能重复下单)
			"expressSheetId" => $expressInfo["id"], // 面单ID
			"goodsNum" => 1,
			"belongTerrace" => $this->belongTerrace[$userOrder->source],
			"remark" => $userOrder->remark,
			"importType" => 1,//1:手动输入 2: 模板导入 3: 智能筛选，（这三个值都可以）
			"recipientInfo" => json_encode($recipientInfo),
		];
		$response = $this->sendRequest("post", "/api/bus/order/sendCargo", $params);
		$req = $this->handleResponse($response);
		return [
			"third_order_sn" => $req,
			"ext_order_sn" => "",
			"status" => PACKAGE_STATUS_SHIPPED,
			"sync_status" => USER_ORDER_SYNC_STATUS_SUCCESS,
		];
	}

	/**
	 * @param $orderConsignee
	 * @return array
	 * @throws OuterApiException
	 * @author ztt
	 * 查询订单  获取快递单号和快递公司名称
	 */
	public function requestOrderQuery($orderConsignee)
	{
	    //判断包裹是否存在上游订单号
        if(!$orderConsignee->third_order_sn){
            throw new ApiException(ErrorEnum::ERROR_INVALID_ORDER);
        }
		$params = [
			"pageNo" => 1,
			"pageSize" => 1,
			"orderId" => $orderConsignee->third_order_sn
		];
		$response = $this->sendRequest("post", "/api/bus/pkInfo/list", $params);
		$data = $this->handleResponse($response);
		if (empty($data["list"][0]) || empty($data["list"][0]["expressNo"])) {
			// 如果运单号没返回 且订单状态是退款状态自动退款
			if($data["list"][0]["status"] == -1 || $data["list"][0]["status"] ==4) {
				//地址停发 自动取消
				$cancel_req = ChannelSyncLogic::syncCancelPackage($orderConsignee->id);
				$add_push = OrderConsigneePushDownService::addPush($orderConsignee->id,1);// 如果是api用户 添加推送信息
				$policy_msg = [
					'功能'=>"获取运单号失败 苍源订单为退款状态",
					'请求链接'=> $this->requestUrl,
					'请求参数'=> $this->requestParams,
					'响应结果'=> $this->apiResponse,
					'信息时间'=>date("Y-m-d H:i:s"),
					'提示消息'=>"退款已成功",
					"damaijia_user_id"=>$this->damaijia_user_id
				];
				QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM")."快小件仓库".json_encode($policy_msg,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),env("POLICE_CODE"));
			}
			return [
				"sync_query_status" => USER_ORDER_SYNC_QUERY_STATUS_PENDING,
			];
		}
		return [
			"express_company_name" => $data["list"][0]["expressCompany"],
			"express_no" => $data["list"][0]["expressNo"],
			"sync_query_status" => USER_ORDER_SYNC_QUERY_STATUS_SUCCESS,
		];
	}
	/**
	 * @author ztt
	 * @param $orderConsignee
	 * 取消订单查询
	 * @return array|mixed|string
	 * @throws ApiException
	 * @throws GuzzleException
	 * @throws OuterApiException
	 */
	private function cancelQueryOrder($orderConsignee) {
		//判断包裹是否存在上游订单号
		if(!$orderConsignee->third_order_sn){
			throw new ApiException(ErrorEnum::ERROR_INVALID_ORDER);
		}
		$params = [
			"pageNo" => 1,
			"pageSize" => 1,
			"orderId" => $orderConsignee->third_order_sn
		];
		$response = $this->sendRequest("post", "/api/bus/pkInfo/list", $params);
		$data = $this->handleResponse($response);
		return $data["list"][0]["status"]; // 包裹状态; 0待付款, 1待发货, 2已发货, 4 已退款, 6交易成功, -1 撤销
	}
	protected function requestCancelOrder($orderConsignee)
	{
		//先查询上游订单状态是否已经取消
		$up_order_status = $this->cancelQueryOrder($orderConsignee);
		if($up_order_status == -1 || $up_order_status==4) { // 包裹状态; 0待付款, 1待发货, 2已发货, 4 已退款, 6交易成功, -1 撤销
			return true;
		}
		// 没取消接口 不支持取消
		throw new ApiException(ErrorEnum::ERROR_FABI_NOT_CANCEL);
		return false;

	}

	private function sendRequest(string $method, $uri, array $data = [])
	{
		$client = $this->getHttpClient();
		$params = $data;
		$uri = $this->baseUrl . $uri;
		$this->requestParams = $params;
		$this->requestToken = $this->token;
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
		$this->apiResponse = $contents;
		$instance = new LoggerFactoryUtil(KuaixiaojianWarehouse::class);
		$instance->info("发B上游返回数据" . json_encode([
				"请求链接"=>$this->requestUrl,
				"请求参数" => $this->requestParams,
				"响应结果" => $contents,
			]));
		if ($contents["code"] != 0) {
			$class_name = CommonUtil::getClassName(get_class($this));
			$function="";
			switch ($this->requestUrl){
				case $this->baseUrl . "/api/bus/goodsInfo/public/getStore": $function="获取仓库"; break;
				case $this->baseUrl . "/api/bus/goodsInfo/public/list": $function="获取商品"; break;
				case $this->baseUrl . "/api/bus/vipLevel/getExpressFree": $function="获取快递价格"; break;
				case $this->baseUrl . "/api/bus/order/sendCargo": $function="请求下单"; break;
				case $this->baseUrl . "/api/bus/pkInfo/list": $function="查询订单"; break;
				case $this->baseUrl . "/api/bus/info/public/apiAuthority": $function="登录"; break;
			}
			$policy_msg = [
				'功能'=>$function,
				'请求链接'=> $this->requestUrl,
				'请求参数'=> $this->requestParams,
				'响应结果'=> $contents,
				'信息时间'=>date("Y-m-d H:i:s"),
				"damaijia_user_id"=>$this->damaijia_user_id
			];
			if($function=="请求下单") {
				$policy_msg["商品id"] = $this->baseProductId;
				$policy_msg["仓库id"] = $this->baseExpressId;
				$policy_msg["仓源id"] = $this->baseWarehouseId;
			}
			if($contents["code"] == 100 && mb_substr($contents["result"],0,5,"utf-8") == "地址已停发") {
				//地址停发 自动取消
				$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id);
				if($cancel_req) {
					OrderConsignee::updateById($this->package_id,["cancel_type"=>2,"cancel_reason"=>"地址已停发, 请换其他快递公司"]);
				}
				$add_push = OrderConsigneePushDownService::addPush($this->package_id,1);// 如果是api用户 添加推送信息
				$new["user_id"] = $this->userOrder->user_id;
				$new["remark"] = "地区禁发";
				$new["type"] = 1;
				$new["order_id"] = $this->userOrder->id;
				$new["package_id"] = $this->package_id;
				NewsModel::create($new); //创建通知
				$policy_msg["msg"] = "退款已成功";
			}
			if($contents["code"] == 100 && mb_substr($contents["result"],0,5,"utf-8") == "仓库已关闭") {
				//地址停发 自动取消
				$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id);
				if($cancel_req) {
					OrderConsignee::updateById($this->package_id,["cancel_type"=>4,"cancel_reason"=>"仓库调整中，暂无商品可下单"]);
				}
				$add_push = OrderConsigneePushDownService::addPush($this->package_id,1);// 如果是api用户 添加推送信息
				$new["user_id"] = $this->userOrder->user_id;
				$new["remark"] = "商品下架";
				$new["type"] = 1;
				$new["order_id"] = $this->userOrder->id;
				$new["package_id"] = $this->package_id;
				NewsModel::create($new); //创建通知
				$policy_msg["msg"] = "退款已成功";
			}
			if($contents["code"] == 100 && $contents["result"] == "礼品库存不足") {
				//地址停发 自动取消
				$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id);
				if($cancel_req) {
					OrderConsignee::updateById($this->package_id,["cancel_type"=>4,"cancel_reason"=>"商品库存不足,请换其他商品"]);
				}
				$add_push = OrderConsigneePushDownService::addPush($this->package_id,1);// 如果是api用户 添加推送信息
				$new["user_id"] = $this->userOrder->user_id;
				$new["remark"] = "礼品库存不足";
				$new["type"] = 1;
				$new["order_id"] = $this->userOrder->id;
				$new["package_id"] = $this->package_id;
				NewsModel::create($new); //创建通知
				$policy_msg["msg"] = "退款已成功";
			}
			QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM")."快小件仓库".json_encode($policy_msg,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),env("POLICE_CODE"));
			throw new OuterApiException(sprintf("%s error:%s", $class_name, json_encode($contents)));
		}

		return $contents["result"] ?? [];
	}

	/**
	 * @author ztt
	 * 登录
	 * @return array|mixed
	 * @throws OuterApiException
	 */
	public function login()
	{
		$params = [
			"account" => $this->rsa_encode($this->account),
			"password" => $this->rsa_encode($this->password),
			"secret" => $this->secret,
		];
		$response = $this->sendRequest("post", "/api/bus/info/public/apiAuthority", $params);
		$user_info = $this->handleResponse($response);
		if(!empty($user_info)) {
			if($user_info["info"]["balance"]<50) {
				$policy_msg["msg"] = "快小件余额不足50元";
				$policy_msg["剩余金额"] = $user_info["info"]["balance"];
				QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM").json_encode($policy_msg,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),env("CHANNEL_MONEY_POLICY"));
			}
		}
		
		return $user_info;
	}

	/**
	 * @param $pwd
	 * @return false|string
	 * @author ztt
	 * 参数加密
	 */
	private function rsa_encode($pwd)
	{
		//公钥
		$publickey = "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCiVpDqm3OUlg5PD6nlenP9uTWok3CxQWcb7Sab
					7Q72W5Wx5fo8VJjcoUhMd83izHWCO2i4MdK7ElES5r8B9PjgVoxlS4XN+k/lZ4+8XhY4xDgkFLd1weY3NK1XLMhWrynjjbuK/N
					8pQ/JXvAi/OAkzDfHYi7C7/2zXKNLKi7AdwQIDAQAB";
		$publickey = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($publickey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
		$r = openssl_public_encrypt($pwd, $encrypted, $publickey);
		if ($r) {
			return base64_encode($encrypted);
		}
		return false;
	}
}
