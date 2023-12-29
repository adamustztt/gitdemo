<?php


namespace App\Http\Controllers\External;


use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Http\Controllers\BaseController;
use App\Http\Logic\External\SendGoodsLogic;
use App\Models\BaseModel;
use Illuminate\Http\Request;

class SendGoodsController extends BaseController
{
	//(淘)店铺后台发货
	public function taoStoreBackstageDelivery(Request $request)
	{
		$params = $this->validate($request, [
			'seller_nick' => 'required',
			'order_no' => 'required',
			'wp_code' => 'required',
			'waybill_no' => 'required',
		]);
		$data = SendGoodsLogic::taoStoreBackstageDelivery();
		return $this->responseJson($data);
	}

	//(淘)备注标旗(批量)
	public function taoNoteFlagBatch(Request $request)
	{
		$params = $this->validate($request, [
			'seller_nick' => 'required',
			'params' => 'required',
		]);
		foreach ($params["params"] as $k => $v) {
			if (empty($v["order_no"]) || empty($v["flag"]) || empty($v["remark"])) {
				CommonUtil::throwException(ErrorEnum::VALIDATE_ERROR);
			}
		}
		$data = SendGoodsLogic::taoNoteFlagBatch();
		return $this->responseJson($data);
	}

	//(淘)获取订单信息
	public function taoToObtainOrderInformation(Request $request)
	{
		$params = $this->validate($request, [
			'seller_nick' => 'required',
			'order_no' => 'required',
		]);
		$data = SendGoodsLogic::taoToObtainOrderInformation();
		return $this->responseJson($data);
	}

	//(淘)获取订单(待发货)
	public function taoGetTheOrderForShipment(Request $request)
	{
		$params = $this->validate($request, [
			'seller_nick' => 'required',
			'page' => '',
			'page_size' => '',
			'start_time' => 'required',
			'end_time' => 'required',
			'status' => '',
		]);
		$data = SendGoodsLogic::taoGetTheOrderForShipment();
		return $this->responseJson($data);
	}

	//(淘)获取面单(智能)
	public function taoForSurfaceSingleZn(Request $request)
	{
		$params = $this->validate($request, [
			'seller_nick' => 'required',
			'order_no' => 'required',
			'sender_province' => 'required',
			'sender_city' => 'required',
			'sender_district' => 'required',
			'sender_address' => 'required',
			'sender_name' => 'required',
			'sender_phone' => 'required',
			'template_url' => 'required',
			'shop_code' => 'required',
			'wp_code' => 'required',
			'goods_info_list' => 'required',
		]);
		foreach ($params["goodsInfoMap"] as $k => $v) {
			if (empty($v["goodsCount"])) {
				CommonUtil::throwException([422, "商品数量不能为空"]);
			}
			if (empty($v["goodsId"])) {
				CommonUtil::throwException([422, "商品ID不能为空"]);
			}
			if (empty($v["goodsWeight"])) {
				CommonUtil::throwException([422, "商品重量不能为空"]);
			}
			if (empty($v["goodsName"])) {
				CommonUtil::throwException([422, "商品名称不能为空"]);
			}
		}
		$data = SendGoodsLogic::taoForSurfaceSingleZn();
		return $this->responseJson($data);
	}

	//(淘)获取面单(非智能)
	public function taoForSurfaceSingleFzn(Request $request)
	{
		$params = $this->validate($request, [
			'seller_nick' => 'required',
			'order_no' => 'required',
			'receiver_province' => 'required',
			'receiver_city' => 'required',
			'receiver_district' => 'required',
			'receiver_address' => 'required',
			'receiver_name' => 'required',
			'receiver_phone' => 'required',
			'sender_province' => 'required',
			'sender_city' => 'required',
			'sender_district' => 'required',
			'sender_address' => 'required',
			'sender_name' => 'required',
			'sender_phone' => 'required',
			'template_url' => 'required',
			'shop_code' => 'required',
			'wp_code' => 'required',
			'goods_info_list' => 'required',
		]);
		foreach ($params["goods_info_list"] as $k => $v) {
			if (empty($v["goodsCount"])) {
				CommonUtil::throwException([422, "商品数量不能为空"]);
			}
			if (empty($v["goodsId"])) {
				CommonUtil::throwException([422, "商品ID不能为空"]);
			}
			if (empty($v["goodsWeight"])) {
				CommonUtil::throwException([422, "商品重量不能为空"]);
			}
			if (empty($v["goodsName"])) {
				CommonUtil::throwException([422, "商品名称不能为空"]);
			}
		}
		$data = SendGoodsLogic::taoForSurfaceSingleFzn();
		return $this->responseJson($data);
	}

	//(淘)子订单发货
	public function suborderDelivery(Request $request)
	{
		$params = $this->validate($request, [
			'seller_nick' => 'required',
			'order_no' => 'required',
			'sub_tids' => '',
			'wp_code' => 'required',
			'waybill_no' => 'required'
		]);
		$data = SendGoodsLogic::suborderDelivery();
		return $this->responseJson($data);
	}

	//(拼)待发订单
	public function pinToOrder(Request $request)
	{
		$params = $this->validate($request, [
			'order_no_str' => '',
			'seller_id' => 'required',
			'page' => '',
			'page_size' => '',
			'start_time' => '',
			'remark' => '',
			'end_time' => ''
		]);
		$data = SendGoodsLogic::pinToOrder();
		return $this->responseJson($data);
	}

	//(拼)获取面单(非智能)
	public function pinForSurfaceSingleFzn(Request $request)
	{
		$params = $this->validate($request, [
			'shop_code' => "required",
			'order_no' => 'required',
			'receiver_province' => 'required',
			'receiver_city' => 'required',
			'receiver_district' => 'required',
			'receiver_address' => 'required',
			'receiver_name' => 'required',
			'receiver_phone' => 'required',
			'sender_province' => 'required',
			'sender_city' => 'required',
			'sender_district' => 'required',
			'sender_address' => 'required',
			'sender_name' => 'required',
			'sender_phone' => 'required',
			'template_url' => 'required',
			'wp_code' => 'required',
			'goods_info_list' => 'required',
			'waybill_seller_nick' => 'required'
		]);
		foreach ($params["goods_info_list"] as $k => $v) {
			if (empty($v["goodsCount"])) {
				CommonUtil::throwException([422, "商品数量不能为空"]);
			}
			if (empty($v["goodsId"])) {
				CommonUtil::throwException([422, "商品ID不能为空"]);
			}
			if (empty($v["goodsWeight"])) {
				CommonUtil::throwException([422, "商品重量不能为空"]);
			}
			if (empty($v["goodsName"])) {
				CommonUtil::throwException([422, "商品名称不能为空"]);
			}
		}
		$data = SendGoodsLogic::pinForSurfaceSingleFzn();
		return $this->responseJson($data);
	}

	//(拼)获取面单(智能)
	public function pinForSurfaceSingleZn(Request $request)
	{
		$params = $this->validate($request, [
			'shop_code' => "required",
			'order_no' => 'required',
			'sender_province' => 'required',
			'sender_city' => 'required',
			'sender_district' => 'required',
			'sender_address' => 'required',
			'sender_name' => 'required',
			'sender_phone' => 'required',
			'template_url' => 'required',
			'wp_code' => 'required',
			'goods_info_list' => 'required',
			"waybill_seller_id" => "required",
			"sellerId" => "required"
		]);
		foreach ($params["goods_info_list"] as $k => $v) {
			if (empty($v["goodsCount"])) {
				CommonUtil::throwException([422, "商品数量不能为空"]);
			}
			if (empty($v["goodsId"])) {
				CommonUtil::throwException([422, "商品ID不能为空"]);
			}
			if (empty($v["goodsWeight"])) {
				CommonUtil::throwException([422, "商品重量不能为空"]);
			}
			if (empty($v["goodsName"])) {
				CommonUtil::throwException([422, "商品名称不能为空"]);
			}
		}
		$data = SendGoodsLogic::pinForSurfaceSingleZn();
		return $this->responseJson($data);
	}

	//(拼)根据订单号同步
	public function pinSynchronization(Request $request)
	{
		$params = $this->validate($request, [
			'order_sns' => 'required',
			'owner_id' => 'required',
		]);
		$data = SendGoodsLogic::pinSynchronization();
		return $this->responseJson($data);
	}

	//(拼)自动发货
	public function pinAutomaticDelivery(Request $request)
	{
		$params = $this->validate($request, [
			'order_no' => 'required',
			'seller_id' => 'required',
			'wp_code' => 'required',
			'waybill_no' => 'required',
		]);
		$data = SendGoodsLogic::pinAutomaticDelivery();
		return $this->responseJson($data);
	}
	//(淘)获取商家订购时长
	public function taoAccessToTheMerchantOrderTime(Request $request)
	{
		$params = $this->validate($request, [
			'seller_nick' => 'required'
		]);
		$data = SendGoodsLogic::taoAccessToTheMerchantOrderTime();
		return $this->responseJson($data);
	}

	//(拼)获取订购时长
	public function pinAccessToTheMerchantOrderTime(Request $request)
	{
		$params = $this->validate($request, [
			'seller_nick' => 'required'
		]);
		$data = SendGoodsLogic::pinAccessToTheMerchantOrderTime();
		return $this->responseJson($data);
	}


}
