<?php


namespace App\Http\Controllers;

use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Models\OrderConsignee;
use Illuminate\Http\Request;


class WarehouseCallbackController extends BaseController
{
	/**
	 * @author ztt
	 * @param Request $request
	 * 云礼品订单回调
	 * @return \Illuminate\Http\JsonResponse
	 * @throws \App\Exceptions\ApiException
	 */
	public function yunlipinOrderCallback(Request $request)
	{
		$params = $request->all();
		if ($params["type"] != 2) {
			CommonUtil::throwException(ErrorEnum::ERROR_UP_PARAMS);
		}
		$id = (int)$params["data"]["orderCode"];
		$express_no = $params["data"]["deliverCode"];
		$express_company_name = $params["data"]["deliveryTypeName"];
		$package = OrderConsignee::getOrderConsigneeById($id);
		if (empty($package)) {
			CommonUtil::throwException(ErrorEnum::DATA_NOT_EXIST);
		}
		// 判断订单来源
		if ($package->userOrder->channel_id != 7) {
			CommonUtil::throwException(ErrorEnum::ERROR_EXT_INVALID_SOURCE);
		}
		// 如果订单状态不是代发货
		if ($package->status != PACKAGE_STATUS_PENDING) {
			CommonUtil::throwException(ErrorEnum::ERROR_INVALID_STATUS);
		}
		
		$package->status = PACKAGE_STATUS_SHIPPED;
		$package->express_company_name = $express_company_name;
		$package->express_no = $express_no;
		$result = $package->save();
		if ($result) {
			return response()->json([
				"msg" => "success",
				"code" => 0
			]);
		}
		CommonUtil::throwException(ErrorEnum::ERROR_UP_PARAMS);
	}

	/**
	 * @author ztt
	 * 礼速通订单回调
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 * @throws \App\Exceptions\ApiException
	 */
	public function lisutongOrderCallback(Request $request)
	{
		$params = $request->all();
		if (empty($params["content"])) {
			CommonUtil::throwException(ErrorEnum::ERROR_UP_PARAMS);
		}
		$id = (int)$params["content"]["thirdOrderNo"];
		$package = OrderConsignee::getById($id);
		if (empty($package)) {
			CommonUtil::throwException(ErrorEnum::DATA_NOT_EXIST);
		}
		// 如果订单状态不是代发货
		if ($package->status != PACKAGE_STATUS_PENDING) {
			CommonUtil::throwException(ErrorEnum::ERROR_INVALID_STATUS);
		}


		$express_company_name = $params["content"]["thirdOrderNo"];
		//logisticsType "1","圆通速递"), ("2","邮政小包"), ("3","韵达速递"), ("4","申通快递"), ("5","中通快递"),
		$express_no = "";
		switch ($params["content"]["logisticsType"]) {
			case "1":
				$express_no = "圆通速递";
				break;
			case "2":
				$express_no = "邮政小包";
				break;
			case "3":
				$express_no = "韵达速递";
				break;
			case "4":
				$express_no = "申通快递";
				break;
			case "5":
				$express_no = "中通快递";
				break;
		}
		$package->status = PACKAGE_STATUS_SHIPPED;
		$package->express_company_name = $express_company_name;
		$package->express_no = $express_no;
		$result = $package->save();
		if ($result) {
			return response()->json([
				"msg" => "success",
				"code" => 0
			]);
		}
		CommonUtil::throwException(ErrorEnum::ERROR_UP_PARAMS);
	}

}
