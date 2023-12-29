<?php


namespace App\Http\Controllers\Console;


use App\Http\Logic\ChannelSyncLogic;
use App\Http\Utils\LoggerFactoryUtil;
use App\Models\ConfigModel;
use App\Models\OrderConsignee;
use App\Models\OrderConsigneePushDown;
use App\Models\UserOrder;
use App\Services\OrderConsigneePushDownService;
use App\Services\Warehouses\WarehouseService;
use App\Services\Warehouses\YunlipinWarehouse;
use GuzzleHttp\Client;
use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Psr\Http\Message\ResponseInterface;
use Redis;
use Tool\ShanTaoTool\HttpCurl;
use Tool\ShanTaoTool\QiWeiTool;

class OrderConsigneeController
{

	/**
	 * 定时发送订单包裹 已废弃
	 */
	public function requestUpOrder()
	{
		//获取定时任务配置
		$config = ConfigModel::query()->where("config_key", "cron_order")->first();
		if (!$config) {
			return;
		}
		$config = $config->toArray();
		//判断是否开启
		if (!$config["config_val"]) {
			return;
		}
		$warehouse = env("NORMAL_WAREHOUSE");
		$warehouse_arr = explode(",", $warehouse);
		$instance = new LoggerFactoryUtil(OrderConsigneeController::class);
		OrderConsignee::query()
			->where(["status" => PACKAGE_STATUS_PENDING])
			->where("consignee", "!=", "测试110")
			->where(["sync_status" => USER_ORDER_SYNC_STATUS_PENDING])
			->lockForUpdate()
			->chunk(100, function ($orderConsigneeArr) use ($warehouse_arr, $instance) {
				foreach ($orderConsigneeArr as $index => $orderConsignee) {
					$instance->info("包裹信息1:" . json_encode($orderConsignee));
					$instance->info("包裹信息ID:" . $orderConsignee->id);
					// 如果包裹状态是禁发取消 不请求上游自动取消
					if (in_array($orderConsignee->cancel_type, [2, 5,6])) {

						//地址停发 自动取消
						$cancel_req = ChannelSyncLogic::syncCancelPackage($orderConsignee->id);
//						$add_push = OrderConsigneePushDownService::addPush($orderConsignee->id, 1);// 如果是api用户 添加推送信息
//						$policy_msg = [
//							'功能' => "平台验证包裹为禁发自动退款",
//							'信息时间' => date("Y-m-d H:i:s"),
//							'包裹ID' => $orderConsignee->id,
//							'包裹信息' => json_decode($orderConsignee),
//							'提示消息' => "退款已成功"
//						];
//						QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM") . "包裹退款" . json_encode($policy_msg, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), env("POLICE_CODE"));
						continue;
					}
					$order = UserOrder::getById($orderConsignee->order_id);
					if (!in_array($order->channel_id, $warehouse_arr)) {
						continue;
					}
					$abstractWarehouse = WarehouseService::getClass($order->channel_id);
					if (empty($abstractWarehouse)) {
						echo "continue" . PHP_EOL;
						OrderConsignee::updateById($orderConsignee->id, ["sync_status" => USER_ORDER_SYNC_STATUS_FAILED]);
						continue;
					}
					echo $orderConsignee->id . PHP_EOL;

					$instance->info("有效请求上游:" . json_encode($warehouse_arr));
					$instance->info("有效请求上游包裹ID:" . $orderConsignee->id);
					try {
						$bool = $abstractWarehouse->createOrder($orderConsignee);
					} catch (\Exception $e) {
						$policy_error["message"] = $e->getMessage();
						QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM") . "定时请求上游下单捕捉到的异常" . json_encode($policy_error, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), env("POLICE_CODE"));
						$instance->info("定时请求上游下单捕捉到的异常：", $e->getMessage());
						echo $e->getMessage();
					} finally {

					}
				}
			});
	}

	/**
	 * 定时请求上游下单V1
	 */
	public function requestUpOrderV1()
	{
		//获取定时任务配置
		$config = ConfigModel::query()->where("config_key", "cron_order")->first();
		if (!$config) {
			return;
		}
		$config = $config->toArray();
		//判断是否开启
		if (!$config["config_val"]) {
			return;
		}
		$warehouse = env("NORMAL_WAREHOUSE");
		$warehouse_arr = explode(",", $warehouse);

		/**
		 * @var \Redis $redis
		 */
		$redis = app("redis");
		$redisKey = "requestUpOrderV1";
		$flag = app('redis')->setnx($redisKey, 1);
		if ($flag) {
			//加锁成功
			//给锁设置过期时间
			app('redis')->setex($redisKey, 300, 1);
			$client = new Client();
			$domain = env("DAMAIJIA_DOMAIN");

			OrderConsignee::query()
				->where(["status" => PACKAGE_STATUS_PENDING])
				->where(["is_decrypt" => 0])
				->where("consignee", "!=", "测试110")
				->where(["sync_status" => USER_ORDER_SYNC_STATUS_PENDING])
				->chunk(100, function ($orderConsigneeArr) use ($warehouse_arr, $client, $domain) {
					foreach ($orderConsigneeArr as $index => $orderConsignee) {

						// 如果包裹状态是禁发取消 不请求上游自动取消
						if (in_array($orderConsignee->cancel_type, [2, 5, 6])) {
							//地址停发 自动取消
							$cancel_req = ChannelSyncLogic::syncCancelPackage($orderConsignee->id);
							$add_push = OrderConsigneePushDownService::addPush($orderConsignee->id, 1);// 如果是api用户 添加推送信息
							$policy_msg = [
								'功能' => ($orderConsignee->cancel_type == 6) ? "验证省市错误" : "平台验证包裹为禁发自动退款",
								'信息时间' => date("Y-m-d H:i:s"),
								'包裹ID' => $orderConsignee->id,
								'包裹信息' => json_decode($orderConsignee),
								'提示消息' => "退款已成功"
							];
//							QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM") . "包裹退款" . json_encode($policy_msg, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), env("POLICE_CODE"));
							continue;
						}
						$order = UserOrder::getById($orderConsignee->order_id);
						if (!in_array($order->channel_id, $warehouse_arr)) {
							continue;
						}
						$abstractWarehouse = WarehouseService::getClass($order->channel_id);
						if (empty($abstractWarehouse)) {
							echo "continue" . PHP_EOL;
							OrderConsignee::updateById($orderConsignee->id, ["sync_status" => USER_ORDER_SYNC_STATUS_FAILED]);
							continue;
						}
						$promise = $client->requestAsync('GET', $domain . '/vv/cronRequestUpOrderByPackageId', [
							"body" => json_encode(["package_id" => $orderConsignee->id]),
							"headers" => [
								"Content-Type" => "application/json"
							]
						])->then(function ($response) {
							echo 'I completed! ' . $response->getBody();
						});
					}
					if (isset($promise)) {
						$promise->wait();
					}

				});
			echo "success";
			app('redis')->del($redisKey);
//			$redis->del("requestUpOrderV1");
		}

	}

	//上游下单请求
	public function cronRequestUpOrderByPackageId(Request $request)
	{
		$params = app("request")->all();
		$packageId = $params["package_id"];
		$orderConsignee = OrderConsignee::getById($packageId);
		$instance = new LoggerFactoryUtil(OrderConsigneeController::class);
		$instance->info("包裹信息:" . json_encode($orderConsignee));
		$order = UserOrder::getById($orderConsignee->order_id);

		try {
			$abstractWarehouse = WarehouseService::getClass($order->channel_id);
			$bool = $abstractWarehouse->createOrder($orderConsignee);
		} catch (\Throwable $e) {
			if ($e->getMessage() != "礼速通仓库下单已改成手动下单") {
				$policy_error["message"] = $e->getMessage();
//				QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM") . "定时请求上游下单捕捉到的异常" . json_encode($policy_error, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), "neibubaojing");
				$instance->info("定时请求上游下单捕捉到的异常：", $e->getMessage());
				return response()->json([
					'data' => $e->getMessage(),
					'status' => $e->getCode(),
				]);
			}

		}
		return response()->json([
			'data' => $orderConsignee,
			'status' => 0,
		]);
	}

	// 定时更新订单包裹
	public function queryUpOrder()
	{
		OrderConsignee::query()
			->where(["status" => PACKAGE_STATUS_SHIPPED])
//			->where(["sync_query_status" => USER_ORDER_SYNC_QUERY_STATUS_PENDING])
			->where(function ($query) {
				$query->whereNull("express_no")->orWhere("express_no", "");
			})
			->chunk(30, function ($orderConsigneeArr) {
				foreach ($orderConsigneeArr as $index => $orderConsignee) {
					$order = UserOrder::getById($orderConsignee->order_id);
					$abstractWarehouse = WarehouseService::getClass($order->channel_id);
					//判断是否是云礼品
					if ($abstractWarehouse instanceof YunlipinWarehouse) {
						//云礼品仓库跳过(通过回调地址获取运单号)
						continue;
					}
					if (empty($abstractWarehouse)) {
						echo "continue" . PHP_EOL;
						OrderConsignee::updateById($orderConsignee->id, ["sync_query_status" => USER_ORDER_SYNC_QUERY_STATUS_FAILED]);
						continue;
					}
					echo $orderConsignee->id . PHP_EOL;
					try {
						$abstractWarehouse->saveOrderByQuery($orderConsignee);
					} catch (\Exception $exception) {
						//抛出异常不会影响其他仓库
					}
				}
			});
	}

	// 查询订单是否超时
	public function updateOrderOvertime()
	{
		$time = date("Y-m-d H:i:s", (time() - 2 * 60 * 60));
		$where["status"] = PACKAGE_STATUS_PAYMENT;
		// 检查订单超时现象
		UserOrder::query()
			->where($where)->where("create_time", "<", $time)
			->chunk(100, function ($userOrder) {
				foreach ($userOrder as $value) {
					UserOrder::query()->where(["id" => $value["id"]])->update([
						"status" => PACKAGE_STATUS_CANCELED, "update_time" => date("Y-m-d H:i:s")
					]);
				}
			});
		// 检查包裹超时现象
		OrderConsignee::query()
			->where($where)->where("create_time", "<", $time)
			->chunk(100, function ($orderConsignee) {
				foreach ($orderConsignee as $value) {
					OrderConsignee::query()->where(["id" => $value["id"]])->update([
						"status" => PACKAGE_STATUS_CANCELED, "update_time" => date("Y-m-d H:i:s")
					]);
				}
			});
	}

	// 推送下游订单
	public function syncDownOrderStatus()
	{
		$where["push_status"] = 1;
		$where["is_delete"] = 1;
		try {
			if (app('redis')->setnx("OrderConsigneePushDown", 1)) {
				$data = OrderConsigneePushDown::query()->where($where)->orderBY("id", "asc")->limit(10)->get()->toArray();
				$params = [];
				foreach ($data as $k => $v) {
					OrderConsigneePushDown::updateById($v["id"], ["push_status" => 2]);
					$params[] = json_decode($v["params"], true);
				}
				$url = env("VTOOL_API");
				$response = HttpCurl::postCurl($url, $params, [], false);
				if ($response["code"] === 0) {
					foreach ($data as $k => $v) {
						OrderConsigneePushDown::updateById($v["id"], ["push_status" => 3]);
					}
				}
				app('redis')->del("OrderConsigneePushDown");
			}
		} catch (\Exception $e) {
			echo $e->getCode();
			$e->getMessage();
		}
		app('redis')->del("OrderConsigneePushDown");
	}

	// 推送下游订单
	public function syncDownOrderStatusV1()
	{
		$where["is_delete"] = 1;
		/**
		 * @var  $redis Redis
		 */
		$redis = app('redis');
		try {
			if (app('redis')->setnx("OrderConsigneePushDown", 1)) {
				$redis->setex("OrderConsigneePushDown", 10, 1);
				OrderConsigneePushDown::query()->where($where)
					->where("count", "<", 3)
					->whereIn("push_status", [1, 4])
					->orderBY("id", "asc")
					->chunk(10, function ($data) {
						foreach ($data as $k => $v) {
							$url = $v->api;
							$params = json_decode($v["params"], true);
							$params["express_no"] = is_null($params["express_no"]) ? "" : $params["express_no"];
							$orderConsignee = OrderConsignee::query()
								->where([
									"site_order_consignee_id" => $params["site_order_consignee_id"],
									"mobile" => $params["mobile"]
								])->first();
							$params["cancel_reason"] = empty($params["cancel_reason"]) ? $orderConsignee["cancel_reason"] : $params["cancel_reason"];
							$params["additional"] = empty($params["additional"]) ? $orderConsignee["additional"] : $params["additional"];
							$response = HttpCurl::postCurl($url, $params, [], false);
							$log = new LoggerFactoryUtil(OrderConsigneeController::class);
							$log->info("请求参数：" . json_encode($params));
							$log->info("请求接口：" . $url);
							$log->info("回掉返回结果：" . json_encode($response));
							if ($response["code"] === 0) {
								OrderConsigneePushDown::updateById($v["id"], ["push_status" => 3]);
								echo "成功；推送ID:" . $v["id"] . "推送参数" . $v["params"] . "返回结果" . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
							} else {
								$pushStatus = OrderConsigneePushDown::query()->where("id",$v["id"])->value("push_status");
								if($pushStatus == 3) {
									continue;
								}
								OrderConsigneePushDown::updateById($v["id"], ["push_status" => 4, "count" => $v["count"] + 1]); //推送失败
								echo "失败；推送ID:" . $v["id"] . "推送参数" . $v["params"] . "返回结果" . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
							}
						}

					});
			}
			app('redis')->del("OrderConsigneePushDown");
		} catch (\Exception $e) {
			app('redis')->del("OrderConsigneePushDown");
			echo $e->getMessage() . $e->getCode();
		}
		app('redis')->del("OrderConsigneePushDown");
	}

	/**
	 * 订单第4次-第六次推送vtool
	 */
	public function pushVtoolOrderInfo()
	{
		$where["is_delete"] = 1;
		/**
		 * @var  $redis Redis
		 */
		$redis = app('redis');
		try {
			if (app('redis')->setnx("OrderConsigneePushDownVtool", 1)) {
				$redis->setex("OrderConsigneePushDownVtool", 10, 1);
				OrderConsigneePushDown::query()->where($where)
					->where("api", env("ORDER_CALLBACK_URL"))
					->where("create_time",">","2022-04-22")
					->whereBetween("count", [3,5])
					->where("push_status", 4)
					->orderBY("id", "asc")
					->chunk(10, function ($data) {
						foreach ($data as $k => $v) {
							$url = $v->api;
							$params = json_decode($v["params"], true);
							$params["express_no"] = is_null($params["express_no"]) ? "" : $params["express_no"];
							$orderConsignee = OrderConsignee::query()
								->where([
									"site_order_consignee_id" => $params["site_order_consignee_id"],
									"mobile" => $params["mobile"]
								])->first();
							$params["cancel_reason"] = empty($params["cancel_reason"]) ? $orderConsignee["cancel_reason"] : $params["cancel_reason"];
							$params["additional"] = empty($params["additional"]) ? $orderConsignee["additional"] : $params["additional"];
							$response = HttpCurl::postCurl($url, $params, [], false);
							$log = new LoggerFactoryUtil(OrderConsigneeController::class);
							$log->info("请求参数：" . json_encode($params));
							$log->info("请求接口：" . $url);
							$log->info("回掉返回结果：" . json_encode($response));
							if ($response["code"] === 0) {
								OrderConsigneePushDown::updateById($v["id"], ["push_status" => 3]);
								echo "成功；推送ID:" . $v["id"] . "推送参数" . $v["params"] . "返回结果" . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
							} else {
								// 
								$pushStatus = OrderConsigneePushDown::query()->where("id",$v["id"])->value("push_status");
								if($pushStatus == 3) {
									continue;
								}
								OrderConsigneePushDown::updateById($v["id"], ["push_status" => 4, "count" => $v["count"] + 1]); //推送失败
								if( $v["count"] == 5) {
									// 第六次了 预警
									QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM")."包裹信息推送vtool第六次失败".json_encode([
											'推送参数'=> $params,
											'响应结果'=> $response,
										],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),env("CHANNEL_MONEY_POLICY"));
								}
								echo "失败；推送ID:" . $v["id"] . "推送参数" . $v["params"] . "返回结果" . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
							}
						}

					});
			}
			app('redis')->del("OrderConsigneePushDownVtool");
		} catch (\Exception $e) {
			app('redis')->del("OrderConsigneePushDownVtool");
			echo $e->getMessage() . $e->getCode();
		}
		app('redis')->del("OrderConsigneePushDown");
	}
	public function getHttpClient(array $config = [])
	{
		$arr = [
			'base_uri' => $this->baseUrl,
			'http_errors' => false, // 禁用HTTP协议抛出的异常(如 4xx 和 5xx 响应)
			'timeout' => $this->requestTimeout, // 请求超时的秒数。使用 0 无限期的等待(默认行为)。
		];
		return new Client(array_merge($arr, $config));
	}

	private function handleResponse(ResponseInterface $response)
	{
		$contents = $response->getBody()->getContents();
		if (is_string($contents)) {
			$contents = json_decode($contents, true);
		}
		return $contents;
	}
}
