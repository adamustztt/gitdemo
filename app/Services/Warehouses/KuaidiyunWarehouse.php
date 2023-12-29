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
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Tool\ShanTaoTool\QiWeiTool;

class KuaidiyunWarehouse extends AbstractWarehouse
{
	protected $channel = WarehouseChannelEnum::KUAIDIYUN;
	protected $baseUrl;
	protected $userName;
	protected $key;
	protected $package_id = "";
	protected function requestWarehouse()
	{
		return false;
	}
	public function __construct() {
		$this->baseUrl=config("warehouse.Kuaidiyun.domain");
		$this->userName=config("warehouse.Kuaidiyun.userName");
		$this->key=config("warehouse.Kuaidiyun.key");
	}
	protected function requestProduct($page = 1, $page_size = 100)
	{
		
		$response = $this->sendRequest("post", "/openApi/emptyTypeList");
		
		$productList = $this->handleResponse($response);
		foreach ($productList as $k => $v) {
			$list[] = [
				"name" => $v["typeName"],
				"thumb" => "",
				"up_cost_price" => $v["price"] * 100,
//				"weight" => $v["weight"] * 1000,
				"ext_id" => $v["typeId"],
				"status" => PRODUCT_STATUS_OFFLINE,
				"warehouse_id" => env("KUAIDIYUN_WAREHOUSE_ID"),
				"stock" => 9999,
				"sales" => rand(100, 9999),
				"up_status"=>1, // 上游商品状态 0:待上架 1:上架 -1 已删除
				"channel_id" => $this->channel,
			];
		}
		return $list;
	}

	protected function requestOrder($product, $userOrder, $orderConsignee)
	{
		$this->package_id = $orderConsignee->id;
		$expressInfo = $this->requestUserInfo();
		if($expressInfo["userMoney"]<50) {
			$policy_msg["msg"] = "快递云余额不足50元";
			$policy_msg["剩余金额"] = $expressInfo["userMoney"];
			QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM").json_encode($policy_msg,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),env("ROUTINE_POLICY"));
		}
		if($expressInfo["userMoney"]*100<$product->up_cost_price) {
			CommonUtil::throwException(ErrorEnum::ERROR_EXT_BALANCE_NOT_ENOUGH);
		}
		if($orderConsignee->is_encryption ==1) {
			switch ($userOrder->source) {
				case "taobao":
					$addressList[] = [
						"orderNo" => (string)$orderConsignee->ext_platform_order_sn,
						"weight"=>1,
						"shopName"=>"",
						"ContactName"=>$orderConsignee->consignee,
						"ContactPhone"=>$orderConsignee->mobile,
						"Province"=>$orderConsignee->province,
						"City"=>$orderConsignee->city,
						"District"=>$orderConsignee->district,
						"AddressDetail"=>$orderConsignee->address,
						"oaid" => $orderConsignee->oaid
					];
					break;
				case "pdd":
					$addressList[] = [
						"OrderNO" => (string)$orderConsignee->ext_platform_order_sn,
						"weight"=>1,
						"shopName"=>"",
						"ContactName"=>$orderConsignee->consignee,
						"ContactName1"=>$orderConsignee->consignee,
						"ContactPhone"=>$orderConsignee->mobile,
						"ContactMobile1"=>$orderConsignee->mobile,
						"Province"=>$orderConsignee->province,
						"City"=>$orderConsignee->city,
						"District"=>$orderConsignee->district,
						"AddressDetail"=>$orderConsignee->address,
						"AddressDetail1"=>$orderConsignee->address,
//						"oaid" => $orderConsignee->oaid
					];
					break;
			}
		} else {
			$addressList[] = [
				"orderNo" => empty($orderConsignee->repeat_id) ? (string)$orderConsignee->id : $orderConsignee->repeat_id,
				"weight"=>1,
				"shopName"=>"",
				"ContactName"=>$orderConsignee->consignee,
				"ContactPhone"=>$orderConsignee->mobile,
				"Province"=>$orderConsignee->province,
				"City"=>$orderConsignee->city,
				"District"=>$orderConsignee->district,
				"AddressDetail"=>$orderConsignee->address,
			];
		}
//		dd($orderConsignee->is_encryption,$addressList,$userOrder->source);
		$params = [
			//浙江省绍兴市上虞区曹娥街道经济开发区志云仓库 李薇薇 18058406712
			"senderName" => "李薇薇", 
			"senderPhone" => "13291678475", 
			"senderProv"=>"浙江省",
			"senderCity"=>"绍兴市",
			"senderCounty"=>"上虞区",
			"senderAddress" => "曹娥街道经济开发区志云仓库",
			"emptyTypeId"=>$product->ext_id, //  134tb pdd 126
			"addresslist" => json_encode($addressList),
		];
		$is_policy = true;
		$response = $this->sendRequest("post", "/openApi/orderCreate", $params);
		$req = $this->handleResponse($response);
		if($req["items"][0]["mailNo"] == null || !isset($req["items"][0]["mailNo"]) || empty($req["items"][0]["mailNo"])) {
			$policy_msg["功能"] = "请求下单";
			$policy_msg["请求链接"] = $this->baseUrl."/openApi/orderCreate";
			$policy_msg["请求参数"] = $this->requestParams;
			$policy_msg["响应结果"] =$req;
			$policy_msg["错误"] ="获取运单号失败";
			$policy_msg["damaijia_user_id"] =$this->damaijia_user_id;
			$policy_msg["商品id"] = $this->baseProductId;
			$policy_msg["仓库id"] = $this->baseExpressId;
			$policy_msg["仓源id"] = $this->baseWarehouseId;
			if(strpos($req["items"][0]["msg"], '该收件地已停发') !== false) {
				//地址停发 自动取消
				$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id);
				if($cancel_req) {
					OrderConsignee::updateById($this->package_id,["cancel_type"=>2,"cancel_reason"=>"该收件地已停发"]);
				}
				$is_policy = false;
				$policy_msg["msg"] = "退款已成功";
			}
			if(strpos($req["items"][0]["msg"], '快递总部设置为停发') !== false) {
				//地址停发 自动取消
				$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id);
				if($cancel_req) {
					OrderConsignee::updateById($this->package_id,["cancel_type"=>2,"cancel_reason"=>"该地区已停发"]);
				}
				$is_policy = false;
				$policy_msg["msg"] = "退款已成功";
			}
			if(strpos($req["items"][0]["msg"], '当地暂时无法提供收派服务') !== false) {
				//地址停发 自动取消
				$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id);
				if($cancel_req) {
					OrderConsignee::updateById($this->package_id,["cancel_type"=>2,"cancel_reason"=>"该收件地已停发"]);
				}
				$is_policy = false;
				$policy_msg["msg"] = "退款已成功";
			}

			if(strpos($req["items"][0]["msg"], '处于黑名单无法下单') !== false) {
				//地址停发 自动取消
				$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id);
				if($cancel_req) {
					OrderConsignee::updateById($this->package_id,["cancel_type"=>5,"cancel_reason"=>"收货人已经被多个店铺标黑禁止一件代发"]);
				}
				$policy_msg["msg"] = "退款已成功";
			}
			if(strpos($req["items"][0]["msg"], '该地已停发') !== false) {
				//地址停发 自动取消
				$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id);
				if($cancel_req) {
					OrderConsignee::updateById($this->package_id,["cancel_type"=>2,"cancel_reason"=>"该收件地已停发"]);
				}
				$is_policy = false;
				$policy_msg["msg"] = "退款已成功";
			}
			if(strpos($req["items"][0]["msg"], '该区域物流公司总部设置为停发') !== false) {
				//地址停发 自动取消
				$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id);
				if($cancel_req) {
					OrderConsignee::updateById($this->package_id,["cancel_type"=>2,"cancel_reason"=>"该收件地已停发"]);
				}
				$policy_msg["msg"] = "退款已成功";
				BanCityService::addBanCityPackage($product->id,$orderConsignee);
				$is_policy = false;
			}
			if(strpos($req["items"][0]["msg"], '不支持该地区申请面单') !== false) {
				//地址停发 自动取消
				$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id);
				if($cancel_req) {
					OrderConsignee::updateById($this->package_id,["cancel_type"=>2,"cancel_reason"=>"该收件地已停发"]);
				}
				$policy_msg["msg"] = "退款已成功";
				$is_policy = false;
			}
			if(strpos($req["items"][0]["msg"], '由于疫情原因') !== false) {
				//地址停发 自动取消
				$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id);
				if($cancel_req) {
					OrderConsignee::updateById($this->package_id,["cancel_type"=>2,"cancel_reason"=>"该收件地已停发"]);
				}
				$policy_msg["msg"] = "退款已成功";
				$is_policy = false;
			}
			if(strpos($req["items"][0]["msg"], '请使用有效的SessionKey') !== false) {
				//快递云仓库，这个错误直接取消订单，不再预警出来，取消原因该仓库不支持该地区
				$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id);
				if($cancel_req) {
					OrderConsignee::updateById($this->package_id,["cancel_type"=>2,"cancel_reason"=>"该仓库不支持该地区"]);
				}
				$is_policy = false;
			}
			if(strpos($req["items"][0]["msg"], '请在收件人姓名或地址后增加分机号[含括号及4位数字]，请与下单人确认具体分机号') !== false) {
				//地址停发 自动取消
				$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id,"已取消，请填写正确的分机号");
				if($cancel_req) {
					OrderConsignee::updateById($this->package_id,["cancel_type"=>4,"cancel_reason"=>"已取消，请填写正确的分机号"]);
				}
				$policy_msg["msg"] = "退款已成功";
			}
			if(strpos($req["items"][0]["msg"], '超出服务范围') !== false) {
				//地址停发 自动取消
				$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id,"该仓库不支持该地区");
				if($cancel_req) {
					OrderConsignee::updateById($this->package_id,["cancel_type"=>4,"cancel_reason"=>"该仓库不支持该地区"]);
				}
				$is_policy = false;
				$policy_msg["msg"] = "退款已成功";
			}
			if(strpos($req["items"][0]["msg"], 'access_token已过期') !== false) {
				$is_policy = false;
				$redisKey =  $orderConsignee->id;
				$redis = app('redis')->client();
				$flag = $redis->set($redisKey, 1,["NX","EX"=>60*60*8]);
				if($flag) {
					QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM")."快递云仓库".json_encode($policy_msg,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),"123");
				}
			}
			if(strpos($req["items"][0]["msg"], '电子面单账户余额不足') !== false) {
				// 出现这样的问题一直不断请求不需要预警处理 需求日期2022年7.14
				$is_policy = false;
			}
			if(strpos($req["items"][0]["msg"], '余额不足') !== false) {
				// 出现这样的问题一直不断请求不需要预警处理 需求日期2022年08月18
				$is_policy = false;
				//20111107一小个小时后自动退款
				$redisKey =  "cancel".$orderConsignee->id;
				/**
				 * @var \Redis $redis
				 */
				$redis = app('redis');
				$time = time();
				$flag = $redis->get($redisKey);
				if(!$flag) {
					$redis->set($redisKey, $time,["NX","EX"=>60*60*8]);
				} else {
					if($flag<($time-3600)) {
						// 自动退款
						//地址停发 自动取消
						$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id,"该仓库不支持该地区");
						if($cancel_req) {
							OrderConsignee::updateById($this->package_id,["cancel_type"=>4,"cancel_reason"=>"该仓库不支持该地区"]);
						}
					}
				}
			}
			if($is_policy) {
				QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM")."快递云仓库".json_encode($policy_msg,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),env("ROUTINE_POLICY"));

			}
			CommonUtil::throwException(ErrorEnum::EXCEPTION_ERROR);
		}
		return [
			"third_order_sn" => $req["items"][0]["mailNo"],
			"ext_order_sn" => $req["items"][0]["mailNo"],
			"express_no" => $req["items"][0]["mailNo"],
			"status" => PACKAGE_STATUS_SHIPPED,
			"sync_status" => USER_ORDER_SYNC_STATUS_SUCCESS,
		];
	}
	protected function requestOrderQuery($orderConsignee)
	{
		return false;
	}
	public function requestOrderQueryV1($package_id)
	{
		$orderConsignee = OrderConsignee::query()->where("id",$package_id)->first();
		$params["orderNos"] = empty($orderConsignee->repeat_id) ? (string)$package_id : $orderConsignee->repeat_id;
		if($orderConsignee["is_encryption"] == 1) {
			$params["orderNos"] = (string)$orderConsignee->ext_platform_order_sn;
		}
		$response = $this->sendRequest("post", "/openApi/orderQuery",$params);
		$req= $this->handleResponse($response);
		if(empty($req["items"][0]["mailNo"]) && $req["items"][0]["isCreate"] == false) {
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
		OrderConsignee::query()->where("id",$package_id)->update($orderRes);
		$add_push = OrderConsigneePushDownService::addPush($package_id,2); // 推送
		OrderConsigneeLogic::checkExpressNo($req["items"][0]["mailNo"],$package_id); // 检查单号是否重复报警
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
		$response = $this->sendRequest("post", "/openApi/orderCancel",$params);
		$req= $this->handleResponse($response);
		if($req["items"][0]["IsSuccess"]) {
			return true;
		} else {
			CommonUtil::throwException(["229",$req["items"][0]["Message"]]);
		}
		
	}
	private function requestUserInfo(){
		$response = $this->sendRequest("post", "/openApi/UserInfoQuery");
		$info = $this->handleResponse($response);
		return $info;
	}
	private function sendRequest(string $method, $uri, array $data = [])
	{
		$client = $this->getHttpClient();
		$params = $data;
		$params["userName"]=$this->userName;
		$params["key"]=$this->key;
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
		if ($contents["status"] != 1) {
			$is_policy = true;
			$class_name = CommonUtil::getClassName(get_class($this));
			$function='';
			switch ($this->requestUrl){
				case $this->baseUrl . "/openApi/emptyTypeList": $function="获取商品"; break;
				case $this->baseUrl . "/openApi/UserInfoQuery": $function="获取用户信息"; break;
				case $this->baseUrl . "/openApi/orderCreate": $function="请求下单"; break;
				case $this->baseUrl . "/openApi/orderCancel": $function="取消订单"; break;
			}
			$policy_msg = [
				'功能'=> $function,
				'请求链接'=> $this->requestUrl,
				'请求参数'=> $this->requestParams,
				'响应结果'=> $contents,
				'信息时间'=>date("Y-m-d H:i:s")
			];
			if($function=="请求下单") {
				$policy_msg["商品id"] = $this->baseProductId;
				$policy_msg["仓库id"] = $this->baseExpressId;
				$policy_msg["仓源id"] = $this->baseWarehouseId;
			}
			$instance->info("快递云返回msg1:" . $contents["msg"]);
			if(strpos($contents["msg"], '请在收件人姓名或地址后增加分机号[含括号及4位数字]，请与下单人确认具体分机号') !== false) {
				//地址停发 自动取消
				$instance->info("快递云返回msg2:" . $contents["msg"]);
				$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id,"已取消，请填写正确的分机号");
				if($cancel_req) {
					OrderConsignee::updateById($this->package_id,["cancel_type"=>4,"cancel_reason"=>"已取消，请填写正确的分机号"]);
				}
				$policy_msg["msg"] = "退款已成功";
			}
			if(strpos($contents["msg"], '该收件地已停发') !== false) {
				//地址停发 自动取消
				$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id);
				if($cancel_req) {
					OrderConsignee::updateById($this->package_id,["cancel_type"=>2,"cancel_reason"=>"该收件地已停发"]);
				}
				$policy_msg["msg"] = "退款已成功";
				$is_policy = false;
			}
			if($contents["msg"] == '账户余额不足') {
				//地址停发 自动取消
				$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id);
				if($cancel_req) {
					OrderConsignee::updateById($this->package_id,["cancel_type"=>2,"cancel_reason"=>"该仓库不支持该地区"]);
				}
				$policy_msg["msg"] = "退款已成功";
				$is_policy = false;
			}
			if(strpos($contents["msg"], '超出服务范围') !== false) {
				//地址停发 自动取消
				$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id);
				if($cancel_req) {
					OrderConsignee::updateById($this->package_id,["cancel_type"=>2,"cancel_reason"=>"该收件地已停发"]);
				}
				$policy_msg["msg"] = "退款已成功";
				$is_policy = false;
			}

			if(strpos($contents["msg"], '处于黑名单无法下单') !== false) {
				//地址停发 自动取消
				$cancel_req = ChannelSyncLogic::syncCancelPackage($this->package_id);
				if($cancel_req) {
					OrderConsignee::updateById($this->package_id,["cancel_type"=>5,"cancel_reason"=>"收货人已经被多个店铺标黑禁止一件代发"]);
				}
				$policy_msg["msg"] = "退款已成功";
			}
			if(strpos($contents["msg"], '该批订单已经执行过了,不可重复执行。如需执行请稍后重试或换个礼品。') !== false) {
				$orderRes = $this->requestOrderQueryV1($this->package_id);
				throw new OuterApiException(sprintf("%s error:%s", $class_name, json_encode($contents)));
			}
			if($is_policy) {
				if(empty($contents)) { // 如果快递云返回null 请求三次预警
					/**
					 * @var \Redis $redis
					 */
					$redis = app("redis");
					$redis->incr("kuaidiyun_return_null");
					$request_count = $redis->get("kuaidiyun_return_null");
					$m = $request_count % 3;
					// 每三次返回null预警一次
					if(($request_count>1) && ($m == 0)) {
						// 超过三次预警
						QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM")."快递云仓库".json_encode($policy_msg,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),env("ROUTINE_POLICY"));
					}
				} else {
					QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM")."快递云仓库".json_encode($policy_msg,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),env("ROUTINE_POLICY"));
				}
			}
			throw new OuterApiException(sprintf("%s error:%s", $class_name, json_encode($contents)));
		}

		return $contents["data"] ?? [];
	}
}
