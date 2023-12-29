<?php


namespace App\Http\Logic\Console;


use App\Enums\TbEnum;
use App\Helper\CommonUtil;
use App\Http\Controllers\Console\OrderConsigneeDecryptController;
use App\Http\Logic\BaseLogic;
use App\Http\Logic\ChannelSyncLogic;
use App\Http\Utils\LoggerFactoryUtil;
use App\Models\OrderConsignee;
use App\Models\UserOrder;
use App\Models\UserShopModel;
use App\Services\Erp\TbErpService;
use App\Services\OrderConsigneePushDownService;
use App\Services\Vtool\ErpService;
use Tool\ShanTaoTool\HttpCurl;
use Tool\ShanTaoTool\QiWeiTool;

class OrderConsigneeDecryptLogic extends BaseLogic
{
	public static function pddDecrypt($package, $shopInfo)
	{
		try {
			$params = [
				'decrypt_report_type' => 0,
				'order_sn' => $package["ext_platform_order_sn"]
			];
			$header = [
				'Cookie' => "sidebarStatus=1; erp_token=" . md5(time() . mt_rand(0, 1000)) . "; laravel_session=eyJpdiI6Ik9RU1ZyN1F1TzhuK3owS0JCcGVuN2c9PSIsInZhbHVlIjoiZ21FVllwQ1IyMDd6alVIb0t6V2JTRk5ES1I0ZlhsS0t2K2JBVEJHbFNUNWJxSzh1Z3BSS0w5NVdUdzhsVW9CNyIsIm1hYyI6IjE0ODhiZGU3Njc2MGU4NWI4YWNlYTVjNDg3YjEwMjk0ZTQ3ZTg5NzRmOTliOWQ5MGNmNmU0NmI2NDVhMDE5YzYifQ%3D%3D",
				"Origin" => 'https://pdd1.vvxiaotu.top',
				'Referer' => 'https://pdd1.vvxiaotu.top/order/delivery',
				'token' => '123456-' . $shopInfo->access_token,
				'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.80 Safari/537.36',
				'Host' => 'pdd1.vvxiaotu.top'
			];
			$instance = new LoggerFactoryUtil(OrderConsigneeDecryptLogic::class);
			$instance->info("请求路径:" . env('PDD_ERP_DOMAIN') . '/pdd/control/decrypt/v1/receiverName');
			$instance->info("请求参数:" . json_encode($params));
			$req = HttpCurl::postCurl(env('PDD_ERP_DOMAIN') . '/pdd/control/decrypt/v1/receiverName', $params, $header);
			$instance->info("返回结果:".json_encode($req));
			$name = $req['order_info']['receiver_name'];
			if (strpos($name, '[') !== false) {
				$name = substr($name, 0, strpos($name, '['));
			}

			$header = [
				'Cookie' => "sidebarStatus=1; erp_token=" . md5(time() . mt_rand(0, 1000)) . "; laravel_session=eyJpdiI6Ik9RU1ZyN1F1TzhuK3owS0JCcGVuN2c9PSIsInZhbHVlIjoiZ21FVllwQ1IyMDd6alVIb0t6V2JTRk5ES1I0ZlhsS0t2K2JBVEJHbFNUNWJxSzh1Z3BSS0w5NVdUdzhsVW9CNyIsIm1hYyI6IjE0ODhiZGU3Njc2MGU4NWI4YWNlYTVjNDg3YjEwMjk0ZTQ3ZTg5NzRmOTliOWQ5MGNmNmU0NmI2NDVhMDE5YzYifQ%3D%3D",
				"Origin" => 'https://pdd1.vvxiaotu.top',
				'Referer' => 'https://pdd1.vvxiaotu.top/order/delivery',
				'token' => '123456-' . $shopInfo->access_token,
				'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.80 Safari/537.36',
				'Host' => 'pdd1.vvxiaotu.top'
			];
			$instance->info("请求路径:" . env('PDD_ERP_DOMAIN') . '/pdd/control/decrypt/v1/receiverPhone');
			$req = HttpCurl::postCurl(env('PDD_ERP_DOMAIN') . '/pdd/control/decrypt/v1/receiverPhone', $params, $header);
			$instance->info("返回结果:".json_encode($req));
			$mobile = $req['order_info']['receiver_phone'];

			$header = [
				'Cookie' => "sidebarStatus=1; erp_token=" . md5(time() . mt_rand(0, 1000)) . "; laravel_session=eyJpdiI6Ik9RU1ZyN1F1TzhuK3owS0JCcGVuN2c9PSIsInZhbHVlIjoiZ21FVllwQ1IyMDd6alVIb0t6V2JTRk5ES1I0ZlhsS0t2K2JBVEJHbFNUNWJxSzh1Z3BSS0w5NVdUdzhsVW9CNyIsIm1hYyI6IjE0ODhiZGU3Njc2MGU4NWI4YWNlYTVjNDg3YjEwMjk0ZTQ3ZTg5NzRmOTliOWQ5MGNmNmU0NmI2NDVhMDE5YzYifQ%3D%3D",
				"Origin" => 'https://pdd1.vvxiaotu.top',
				'Referer' => 'https://pdd1.vvxiaotu.top/order/delivery',
				'token' => '123456-' . $shopInfo->access_token,
				'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.80 Safari/537.36',
				'Host' => 'pdd1.vvxiaotu.top'
			];
			$instance->info("请求路径:" . env('PDD_ERP_DOMAIN') . '/pdd/control/decrypt/v1/receiverAddress');
			$req = HttpCurl::postCurl(env('PDD_ERP_DOMAIN') . '/pdd/control/decrypt/v1/receiverAddress', $params, $header);
			$instance->info("返回结果:".json_encode($req));
			$address = $req['order_info']['receiver_address'];
			OrderConsignee::query()->where('id', $package['id'])->update([
				'consignee' => $name,
				'consignee_mask' => $name,
				'mobile' => $mobile,
				'mobile_mask' => $mobile,
				'address' => $address,
				'address_mask' => $address,
				'is_decrypt' => 0
			]);
		} catch (\Throwable $exception) {
			//地址停发 自动取消
			$cancel_req = ChannelSyncLogic::syncCancelPackage($package->id, "解密百分比已达上线，联系客服提升额度");
			$add_push = OrderConsigneePushDownService::addPush($package->id, 1);// 如果是api用户 添加推送信息
			// 解密百分比已达上线，联系客服提升额度
		}
	}

	// 淘宝解密
	public static function tbDecrypt($package, $shopInfo)
	{
		$instance = new LoggerFactoryUtil(OrderConsigneeDecryptLogic::class);
		try {
//			$url = "/tool/erps/tb-oaid";
//			$erpService = new ErpService();
//			$requestParams1 = [
//				"shop_id" => $shopInfo["shop_id"],
//				"query_list" => [
//					["oaid" => $package->oaid, "tid" => $package["ext_platform_order_sn"], "scene" => 1002]
//				],
//				"vvtype"=>$shopInfo["version_type"],
//			];
//			try {
//				$api_result = $erpService->sentPostRequest($url, $requestParams1);
//				if ($api_result) {
//					OrderConsignee::query()->where('id', $package['id'])->update([
//						'consignee' => $api_result['receiver_list']['receiver'][0]['name'],
//						'mobile' => $api_result['receiver_list']['receiver'][0]['mobile'],
//						'address' => $api_result['receiver_list']['receiver'][0]['address_detail'],
//						'is_decrypt' => 0
//					]);
//					return "解密成功";
//				}
//			} catch (\Exception $e) {
//
//			}
//			$baseUrl = env("ERP_DOMAIN");
//			$url = "api/tb/request";
//			$callback_params = json_decode($shopInfo["callback_params"], true);
//			$requestParams1["appkey"] = $callback_params["sellernick"];
//			$requestParams1["secretKey"] = $callback_params["code"];
//			$req = HttpCurl::postCurl($baseUrl . $url, $requestParams1);
//			$instance = new LoggerFactoryUtil(OrderConsigneeDecryptLogic::class);
//			$instance->info("请求接口:" . $baseUrl . $url);
//			$instance->info("请求参数:" . $requestParams1);
//			$instance->info("返回结果:" . json_encode($req));
//			if ($req["data"]["status"]) {
			// 解密成功
//				$tbOrderInfo = $req["data"]["data"]["top_oaid_decrypt_response"]["receiver_list"]["receiver"][0];
//				OrderConsignee::query()->where('id', $package['id'])->update([
//					'consignee' => $tbOrderInfo["name"],
//					'mobile' => $tbOrderInfo["mobile"],
//					'address' => $tbOrderInfo["address_detail"],
//					'is_decrypt' => 0
//				]);
//				return "包裹id" . $package['id'] . "解密成功";
			if (1 == 0) {

			} else {
				$url = "/tool/erps/express_search";
				$erpService = new ErpService();
				$api_result = $erpService->sentPostRequest($url, ["shop_id" => $shopInfo["shop_id"], "vvtype" => $shopInfo["version_type"]]);
				$waybill_apply_subscription_info = $api_result["waybill_apply_subscription_cols"]["waybill_apply_subscription_info"];
				$user_waybill = [];
				foreach ($waybill_apply_subscription_info as $k => $v) {
					$cp_code = $v["cp_code"];
					foreach ($v["branch_account_cols"]["waybill_branch_account"] as $vv) {
						if ($vv["quantity"] > 0) {
							$user_waybill = $vv;
							break 2;
						}
					}
				}
				$requestParams2["vvtype"] = 2;
				$requestParams2["shop_id"] = $package->shop_id;
				$requestParams2["param_waybill_cloud_print_apply_new_request"] = [
					"cp_code" => $cp_code,
					'need_encrypt' => "true",
					"sender" => [
						"address" => [
//							"city" => "郑州市",
//							"detail" => "河南省郑州市中牟县九龙镇润之新物流园",
//							"district" => "中牟县",
//							"province" => "河南省",
							'city' => $user_waybill['shipp_address_cols']['address_dto'][0]['city'],
							'detail' => $user_waybill['shipp_address_cols']['address_dto'][0]['detail'],
							'district' => $user_waybill['shipp_address_cols']['address_dto'][0]['district'],
							'province' => $user_waybill['shipp_address_cols']['address_dto'][0]['province'],
						],
						"mobile" => 13311114444,
						"name" => $user_waybill["branch_name"]
					],
					"trade_order_info_dtos" => [
						[
							"object_id" => $package->id,
							"order_info" => [
								"order_channels_type" => "TB",
								"trade_order_list" => [
									$package->ext_platform_order_sn
								],
							],
							"package_info" => [
								"items" => [
									"count" => 1,
									"name" => "其它-百货"
								]
							],
							"recipient" => [
								"address" => [
									"detail" => $package->address,//"翠*街道**路***号学苑春**幢",
									"province" => $package->province,//"浙江省",
								],
								"mobile" => $package->mobile,
								"name" => $package->consignee,
								"oaid" => $package->oaid,
							],
							"template_url" => "http://cloudprint.cainiao.com/template/standard/401/198",
							"user_id" => $package->shop_id
						]
					]
				];
				$url = "/tool/erps/waybill";
				$erpService = new ErpService();
				$api_result = $erpService->sentPostRequest($url, $requestParams2);
				if (isset($api_result["modules"]["waybill_cloud_print_response"][0]["print_data"])) {
					$print_data = $api_result["modules"]["waybill_cloud_print_response"][0]["print_data"];
					$print_data = json_decode($print_data, true);
					$encryptedData = $print_data['encryptedData'];
					$res3 = HttpCurl::postCurl(env("VTOOL2_URL") . "waybill/decrypt", ['encryptedData' => $encryptedData]);
					$instance->info("上游返回数据:" . json_encode($res3));
					if (isset($res3['code']) && $res3['code'] == 0) {
						OrderConsignee::query()->where('id', $package['id'])->update([
							'consignee' => $res3['data']['recipient']['name'],
							'consignee_mask' => $res3['data']['recipient']['name'],
							'mobile' => $res3['data']['recipient']['mobile'],
							'mobile_mask' => $res3['data']['recipient']['mobile'],
							'address' => $res3['data']['recipient']['address']['detail'],
							'address_mask' => $res3['data']['recipient']['address']['detail'],
							'is_decrypt' => 0
						]);
						echo "包裹id" . $package['id'] . "解密成功";
					} else {
						CommonUtil::throwException([422, "解密失败"]);
					}
					$url = "/tool/erps/cainiao-waybill-cancel";
					try {
						$req = $erpService->sentPostRequest($url, [
							"shop_id" => $shopInfo["shop_id"],
							"vvtype" => 2,
							"cp_code" => $api_result["modules"]["waybill_cloud_print_response"][0]["cp_code"],
							"waybill_code" => $api_result["modules"]["waybill_cloud_print_response"][0]["waybill_code"]
						]);
					} catch (\Exception $e) {
						// 取消失败
					}

				} else {
					CommonUtil::throwException([422, "解密失败"]);
				}
			}
		} catch (\Exception $exception) {
			//地址停发 自动取消
			$cancel_req = ChannelSyncLogic::syncCancelPackage($package->id, "解密百分比已达上线，联系客服提升额度");
			CommonUtil::throwException([422, $exception->getMessage()]);
		}
	}
	public static function tbDecryptV1($package, $shopInfo)
	{
		$instance = new LoggerFactoryUtil(OrderConsigneeDecryptLogic::class);
		try {
			if (1 == 0) {

			} else {
				$requestParams2["vvtype"] = 2;
				$requestParams2["shop_id"] = 173926031;
				$requestParams2["param_waybill_cloud_print_apply_new_request"] = [
					"cp_code" => "POSTB",
					'need_encrypt' => "true",
					"sender" => [
						"address" => [
							"city" => "泉州市",
							"detail" => "吾都",
							"district" => "安溪县",
							"province" => "福建省",
						],
						"mobile" => 17185714920,
						"name" => "小王"
					],
					"trade_order_info_dtos" => [
						[
							"object_id" => $package->id,
							"order_info" => [
								"order_channels_type" => "TB",
								"trade_order_list" => [
									$package->ext_platform_order_sn
								],
							],
							"package_info" => [
								"items" => [
									"count" => 1,
									"name" => "其它-百货"
								]
							],
							"recipient" => [
								"address" => [
									"detail" => $package->address,//"翠*街道**路***号学苑春**幢",
									"province" => $package->province,//"浙江省",
								],
								"mobile" => $package->mobile,
								"name" => $package->consignee,
								"oaid" => $package->oaid,
							],
							"template_url" => "http://cloudprint.cainiao.com/template/standard/401/198",
							"user_id" => 173926031
						]
					]
				];
				$url = "/tool/erps/waybill";
				$erpService = new ErpService();
				$api_result = $erpService->sentPostRequest($url, $requestParams2);
				if (isset($api_result["modules"]["waybill_cloud_print_response"][0]["print_data"])) {
					$print_data = $api_result["modules"]["waybill_cloud_print_response"][0]["print_data"];
					$print_data = json_decode($print_data, true);
					$encryptedData = $print_data['encryptedData'];
					try {
						$res3 = HttpCurl::postCurl(env("VTOOL2_URL") . "waybill/decrypt", ['encryptedData' => $encryptedData]);
						$instance->info("上游返回数据:" . json_encode($res3));
						if (isset($res3['code']) && $res3['code'] == 0) {
							OrderConsignee::query()->where('id', $package['id'])->update([
								'consignee' => $res3['data']['recipient']['name'],
								'consignee_mask' => $res3['data']['recipient']['name'],
								'mobile' => $res3['data']['recipient']['mobile'],
								'mobile_mask' => $res3['data']['recipient']['mobile'],
								'address' => $res3['data']['recipient']['address']['detail'],
								'address_mask' => $res3['data']['recipient']['address']['detail'],
								'is_decrypt' => 0
							]);
							echo "包裹id" . $package['id'] . "解密成功";
						} else {
							CommonUtil::throwException([422, "解密失败"]);
						}
					} catch (\Exception $e) {
						
					}
					
					$url = "/tool/erps/cainiao-waybill-cancel";
					try {
						$req = $erpService->sentPostRequest($url, [
							"shop_id" => 173926031,
							"vvtype" => 2,
							"cp_code" => $api_result["modules"]["waybill_cloud_print_response"][0]["cp_code"],
							"waybill_code" => $api_result["modules"]["waybill_cloud_print_response"][0]["waybill_code"]
						]);
					} catch (\Exception $e) {
						// 取消失败
						$policy_msg["msg"] = "回收单号失败";
						$policy_msg["params"] = [
							"shop_id" => 173926031,
							"vvtype" => 2,
							"cp_code" => $api_result["modules"]["waybill_cloud_print_response"][0]["cp_code"],
							"waybill_code" => $api_result["modules"]["waybill_cloud_print_response"][0]["waybill_code"]
						];
						QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM").json_encode($policy_msg,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
						$req = $erpService->sentPostRequest($url, [
							"shop_id" => 173926031,
							"vvtype" => 2,
							"cp_code" => $api_result["modules"]["waybill_cloud_print_response"][0]["cp_code"],
							"waybill_code" => $api_result["modules"]["waybill_cloud_print_response"][0]["waybill_code"]
						]);
						CommonUtil::throwException([422, "解密失败"]);
					}

				} else {
					CommonUtil::throwException([422, "解密失败"]);
				}
			}
		} catch (\Exception $exception) {
			//地址停发 自动取消
			$cancel_req = ChannelSyncLogic::syncCancelPackage($package->id, "解密百分比已达上线，联系客服提升额度");
			CommonUtil::throwException([422, $exception->getMessage()]);
		}
	}
	/**
	 * @return string
	 * 包裹解密
	 */
	public static function taskPackageDecrypt()
	{
		$data = OrderConsignee::query()->where('is_decrypt', 1)->where("status","=","p")->get();
		if ($data->count()) {
			foreach ($data as $package) {
				$user_id = UserOrder::query()->where("id",$package["order_id"])->value("user_id");
				$shopInfo = UserShopModel::query()->where(["shop_id"=>$package["shop_id"],"user_id"=>$user_id,"is_delete"=>0])->first();
				try {
					switch ($shopInfo->shop_type) {
						case "pdd":
							self::pddDecrypt($package, $shopInfo);
							break;
						case "tb":
//							self::tbDecrypt($package, $shopInfo);
							self::tbDecryptV1($package, $shopInfo);
							break;
					}
				} catch (\Exception $e) {
					echo $e->getMessage();
				}
				
			}
			return "操作完成";
		} else {
			return "暂无数据";
		}
	}
}
