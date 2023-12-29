<?php


namespace App\Services\Warehouses;


use App\Enums\ErrorEnum;
use App\Exceptions\ApiException;
use App\Http\Bean\Utils\CustomExpress\YundaCreateBmOrderBean;
use App\Http\Logic\ChannelSyncLogic;
use App\Http\Utils\CustomExpress\YundaGuangzhouQingtianUtil;
use App\Http\Utils\LoggerFactoryUtil;
use App\Models\OrderConsignee;
use App\Services\Vtool\ErpService;
use Tool\ShanTaoTool\HttpCurl;
use Tool\ShanTaoTool\QiWeiTool;

class YundaGuangzhouQingtianWarehouse extends AbstractWarehouse
{
	protected function requestWarehouse()
	{
		return false;
		// TODO: Implement requestWarehouse() method.
	}

	protected function requestProduct($page = 1, $page_size = 100)
	{
		return false;
		// TODO: Implement requestProduct() method.
	}

	private function searchBmCount()
	{
		$api_result = YundaGuangzhouQingtianUtil::searchBmCount();
	}

	public function requestTbOrder($product, $userOrder, $orderConsignee)
	{
		$requestParams["vvtype"] = 2;
		$requestParams["shop_id"] = "173926031";
		$requestParams["param_waybill_cloud_print_apply_new_request"] = [
			"cp_code" => "YUNDA",
			"sender" => [
				"address" => [
					"city" => "郑州市",
					"detail" => "河南省郑州市中牟县九龙镇润之新物流园",
					"district" => "中牟县",
					"province" => "河南省"
				],
				"mobile" => "13946501647",
				"name" => "河南大客户部星辰服务部"
			],
			"trade_order_info_dtos" => [
				[
					"object_id" => $orderConsignee->id,
					"order_info" => [
						"order_channels_type" => "TB",
						"trade_order_list" => [
							$orderConsignee->ext_platform_order_sn
						],
					],
					"package_info" => [
						"items" => [
							"count" => 1,
							"name" => "其它-百货"
						]
					],
					"recipient" => [
//						"address" => [
//							"detail" => "新疆",//"翠*街道**路***号学苑春**幢",
//							"province" => "新疆省",//"浙江省",
//						],
						"address" => [
							"detail" =>$orderConsignee->address,//"翠*街道**路***号学苑春**幢",
							"province" => $orderConsignee->province,//"浙江省",
						],
						"mobile" => $orderConsignee->mobile,
						"name" => $orderConsignee->consignee,
						"oaid" => $orderConsignee->oaid,
					],
					"template_url" => "http://cloudprint.cainiao.com/template/standard/401/198",
					"user_id" => $orderConsignee->shop_id
				]
			]
		];
		$url = "/tool/erps/waybill";
		$erpService = new ErpService();
//		dd($requestParams);
		$api_result = $erpService->sentPostRequest($url, $requestParams);
		return $api_result;
	}
//	public function requestTbOrder($product, $userOrder, $orderConsignee)
//	{
//		$requestParams["vvtype"] = 2;
//		$requestParams["shop_id"] = "173926031";
//		$requestParams["param_waybill_cloud_print_apply_new_request"] = [
//			"cp_code" => "YUNDA",
//			"sender" => [
//				"address" => [
//					"city" => "郑州市",
//					"detail" => "河南省郑州市中牟县九龙镇润之新物流园",
//					"district" => "中牟县",
//					"province" => "河南省"
//				],
//				"mobile" => "13946501647",
//				"name" => "河南大客户部星辰服务部"
//			],
//			"trade_order_info_dtos" => [
//				[
//					"object_id" => $orderConsignee->id,
//					"order_info" => [
//						"order_channels_type" => "TB",
//						"trade_order_list" => [
//							$orderConsignee->ext_platform_order_sn
//						],
//					],
//					"package_info" => [
//						"items" => [
//							"count" => 1,
//							"name" => "其它-百货"
//						]
//					],
//					"recipient" => [
//						"address" => [
//							"detail" => "新疆",//"翠*街道**路***号学苑春**幢",
//							"province" => "新疆省",//"浙江省",
//						],
//						"mobile" => $orderConsignee->mobile,
//						"name" => $orderConsignee->consignee,
//						"oaid" => $orderConsignee->oaid,
//					],
//					"template_url" => "http://cloudprint.cainiao.com/template/standard/401/198",
//					"user_id" => $orderConsignee->shop_id
//				]
//			]
//		];
//		$header=[
//			"userKey"=>"4bf2083b0587230c887da7bd0cb26d89",
//			"accountId"=>"228897764",
//			"appId"=>7,
//			"oauthType"=>2   //拼多多1,菜鸟2
//		];
//		$url = "/api/waybill/v2/take/code";
//		$baseUrl = env("MIANDAN_DOAMIN");
//		$api_result = HttpCurl::postCurl($baseUrl.$url,$requestParams,$header);
//		return $api_result["data"];
//		return $api_result;
//	}
//	public function requestPddOrder1($product, $userOrder, $orderConsignee)
//	{
//		$requestParams["account_id"] = "228897764";
//		$requestParams["data"] = [
//			"param_waybill_cloud_print_apply_new_request" => [
//				"need_encrypt" => true,
//				"wp_code" => "YUNDA",
//				"sender" => [
//					"address" => [
//						"city" => "郑州市",
//						"detail" => "河南省郑州市中牟县九龙镇润之新物流园",
//						"district" => "中牟县",
//						"province" => "河南省"
//					],
//					"mobile" => "13946501647",
//					"name" => "河南大客户部星辰服务部"
//				],
//				"trade_order_info_dtos" => [
//					[
//						"object_id" => $orderConsignee->id,
//						"order_info" => [
//							"order_channels_type" => "PDD",
//							"trade_order_list" => [
//								$orderConsignee->ext_platform_order_sn
//							],
//						],
//						"package_info" => [
//							"items" => [
//								[
//									"count" => 1,
//									"name" =>  "其它-百货"
//								]
//							]
//						],
//						"recipient" => [
//							"address" => [
//								"city" => $orderConsignee->city,//"翠*街道**路***号学苑春**幢",
//								"detail" => $orderConsignee->address,
//								"district" => $orderConsignee->district,
//								"province" => $orderConsignee->province
//							],
//							"mobile" => $orderConsignee->mobile,
//							"name" => $orderConsignee->consignee,
//						],
//						"template_url" => "http://cloudprint.cainiao.com/template/standard/401/198",
//						"user_id" => $orderConsignee->shop_id
//					]
//				]
//			],
//		];
//		$url = "/tool/accounts/pdd-print-electronic-sheet";
//		$erpService = new ErpService();
//		$api_result = $erpService->sentPostRequest($url, $requestParams);
//		return $api_result;
//	}
	public function requestPddOrder($product, $userOrder, $orderConsignee)
	{
		$requestParams["account_id"] = "228897764";
		$requestParams["data"] = [
			"param_waybill_cloud_print_apply_new_request" => [
				"need_encrypt" => true,
				"wp_code" => "YUNDA",
				"sender" => [
					"address" => [
						"city" => "郑州市",
						"detail" => "河南省郑州市中牟县九龙镇润之新物流园",
						"district" => "中牟县",
						"province" => "河南省"
					],
					"mobile" => "13946501647",
					"name" => "河南大客户部星辰服务部"
				],
				"trade_order_info_dtos" => [
					[
						"object_id" => $orderConsignee->id,
						"order_info" => [
							"order_channels_type" => "PDD",
							"trade_order_list" => [
								$orderConsignee->ext_platform_order_sn
							],
						],
						"package_info" => [
							"items" => [
								[
									"count" => 1,
									"name" =>  "其它-百货"
								]
							]
						],
						"recipient" => [
							"address" => [
								"city" => $orderConsignee->city,//"翠*街道**路***号学苑春**幢",
								"detail" => $orderConsignee->address,
								"district" => $orderConsignee->district,
								"province" => $orderConsignee->province
							],
							"mobile" => $orderConsignee->mobile,
							"name" => $orderConsignee->consignee,
						],
						"template_url" => "http://cloudprint.cainiao.com/template/standard/401/198",
						"user_id" => $orderConsignee->shop_id
					]
				]
			],
		];
		
		$header=[
			"userKey"=>"4bf2083b0587230c887da7bd0cb26d89",
			"accountId"=>"228897764",
			"appId"=>7,
			"oauthType"=>1
		];
		$url = "/api/waybill/v2/take/code";
		$baseUrl = env("MIANDAN_DOAMIN");
		$api_result = HttpCurl::postCurl($baseUrl.$url,$requestParams,$header);
		return $api_result["data"];
	}
	protected function requestOrder($product, $userOrder, $orderConsignee)
	{
		// 如果是加密订单
		if ($orderConsignee->is_encryption == 1) {
			switch ($userOrder->source) {
				case "taobao":
				case "tb":
					try {
						$api_result = $this->requestTbOrder($product, $userOrder, $orderConsignee);
						if (isset($api_result["modules"]["waybill_cloud_print_response"][0]["waybill_code"])) {
							return [
								"third_order_sn" => $api_result["modules"]["waybill_cloud_print_response"][0]["object_id"],
								"express_no" => $api_result["modules"]["waybill_cloud_print_response"][0]["waybill_code"],
								"status" => PACKAGE_STATUS_SHIPPED,
								"sync_status" => USER_ORDER_SYNC_STATUS_SUCCESS,
							];
						}
					} catch (\Exception $e) {
						$policy_msg["功能"] = "密闻下单失败";
						$policy_msg["包裹id"] = $orderConsignee->id;
						$policy_msg["商品id"] = $this->baseProductId;
						$policy_msg["仓库id"] = $this->baseExpressId;
						$policy_msg["仓源id"] = $this->baseWarehouseId;
						QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM")."韵达广州擎天仓库".json_encode($policy_msg,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),env("POLICE_CODE"));
						if($e->getCode() == 2014) {
							//地址停发 自动取消
							$cancel_req = ChannelSyncLogic::syncCancelPackage($orderConsignee->id);
							if($cancel_req) {
								OrderConsignee::updateById($orderConsignee->id,["cancel_type"=>4,"cancel_reason"=>"收货地址错误"]);
							}
							$policy_msg["msg"] = "退款已成功";
							$policy_msg["错误消息"] = $e->getMessage();
						}
					}
					throw new ApiException(ErrorEnum::ERROR_YUNDA_WAREHOUSE_RESULT);
					break;
				case "pdd":
					$api_result = $this->requestPddOrder($product, $userOrder, $orderConsignee);
					if(isset($api_result["pdd_waybill_get_response"]["modules"][0]["waybill_code"])) {
						return [
							"third_order_sn" => $api_result["pdd_waybill_get_response"]["request_id"],
							"express_no" => $api_result["pdd_waybill_get_response"]["modules"][0]["waybill_code"],
							"status" => PACKAGE_STATUS_SHIPPED,
							"sync_status" => USER_ORDER_SYNC_STATUS_SUCCESS,
						];
					}
					break;
			}
		} else {
			$params["orderNumber"] = $orderConsignee->id;
			$params["sendName"] = "李薇薇";
			$params["sendProvince"] = "广东省";
			$params["sendCity"] = "广州市";
			$params["sendCountry"] = "白云区";
			$params["sendAddress"] = "广东省广州市白云区太和镇";
			$params["receivePhone"] = $orderConsignee->mobile;
			$params["receiveName"] = $orderConsignee->consignee;
			$params["receiveProvince"] = $orderConsignee->province;
			$params["receiveCity"] = $orderConsignee->city;
			$params["receiveCountry"] = $orderConsignee->district;
			$params["receiveAddress"] = $orderConsignee->address;
			$YundaCreateBmOrderBean = new YundaCreateBmOrderBean($params);
			$api_result = YundaGuangzhouQingtianUtil::createBmOrder($YundaCreateBmOrderBean);
			if ($api_result[0]["status"] == 1) {
				return [
					"third_order_sn" => $api_result[0]["orderId"],
					"ext_order_sn" => $api_result[0]["orderId"],
					"express_no" => $api_result[0]["mail_no"],
					"status" => PACKAGE_STATUS_SHIPPED,
					"sync_status" => USER_ORDER_SYNC_STATUS_SUCCESS,
				];
			}
		}
		$base_url = $config = config("customExpress.yunda");
		$policy_msg["功能"] = "请求下单";
		$policy_msg["错误"] = "请求下单成功返回数据错误";
		$policy_msg["请求链接"] = $base_url["url"] . "accountOrder/createBmOrder";
		$policy_msg["请求参数"] = $params;
		$policy_msg["响应结果"] = $api_result;
		$policy_msg['信息时间'] = date("Y-m-d H:i:s");
		$policy_msg['damaijai_user_id'] = $this->damaijia_user_id;
		$policy_msg["商品id"] = $this->baseProductId;
		$policy_msg["仓库id"] = $this->baseExpressId;
		$policy_msg["仓源id"] = $this->baseWarehouseId;
		if(strpos($api_result[0]["msg"], '该快件收货地址所在网点已关停') !== false) {
			//地址停发 自动取消
			$cancel_req = ChannelSyncLogic::syncCancelPackage($orderConsignee->id);
			if($cancel_req) {
				OrderConsignee::updateById($orderConsignee->id,["cancel_type"=>2,"cancel_reason"=>"该收件地已停发"]);
			}
			$policy_msg["msg"] = "退款已成功";
		}
		//群里小溪说的该情况不再预警处理
//		QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM") . "韵达广州擎天" . json_encode($policy_msg, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
		throw new ApiException(ErrorEnum::ERROR_YUNDA_WAREHOUSE_RESULT);
	}

	protected
	function requestOrderQuery($orderConsignee)
	{
		return false;
		// TODO: Implement requestOrderQuery() method.
	}

	protected
	function requestCancelOrder($orderConsignee)
	{
		$api_result = YundaGuangzhouQingtianUtil::cancelBmOrder($orderConsignee->id, $orderConsignee->express_no);
		if ($api_result[0]["status"] == 1) {
			return true;
		}
		return false;
	}
}
