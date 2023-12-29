<?php


namespace App\Http\Controllers\External;


use App\Helper\CommonUtil;
use App\Http\Controllers\BaseController;
use App\Http\Logic\External\ErpLogic;
use App\Http\Logic\External\TbErpLogic;
use App\Http\Utils\BaseUtil;
use App\Models\UserShopModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ErpController extends BaseController
{
	//淘宝1回调 普通版
	public function callbackShopInfo(Request $request)
	{
		$params = $this->validate($request, [
			'dmd_u' => 'required',
			'access_token' => 'required',
			'shop_id' => 'required',
		]);
		$dmd_top_sign = $params["dmd_u"];
		$user_id = (substr(strrev($dmd_top_sign), 8, -6) + 5) / 5;
		$shop_id = $params["shop_id"];
		$access_token = $params["access_token"];
		$map["shop_id"] = $shop_id;
		$map["user_id"] = $user_id;
		$map["version_type"] = 1;
		$map["access_token"] = $access_token;
		$shop = UserShopModel::query()->where(["shop_id"=>$shop_id,"user_id"=>$user_id,"version_type"=>1])->first();
		if($shop) {
			UserShopModel::query()->where(["shop_id"=>$shop_id,"user_id"=>$user_id,"version_type"=>1])->update($map);
		} else {
			UserShopModel::create($map);
		}
		return "SUCCESS";
	}
	// 快手ERP回掉
	public function ksErpShopCallback(Request $request)
	{
		$params = $this->validate($request, [
			'uid' => 'required',
			'shop_id' => 'required',
			'shop_name' => 'required',
			'expires_at' => 'required',
		]);
		$map["shop_id"] = $params["shop_id"];
		$map["user_id"] = $params["uid"];
		$map["shop_type"] = "ks";
		$map["shop_name"] = $params["shop_name"];
		$map["expiration_time"] = $params["expires_at"];
		$map["authorization_time"] = date("Y-m-d H:i:s");
		$map["site_id"] = 1;
		$map["callback_params"] = json_encode(app("request")->all());
		UserShopModel::create($map);
		return "SUCCESS";
	}
	// dyERP回掉
	public function dyErpShopCallback(Request $request)
	{
		$params = $this->validate($request, [
			'uid' => 'required',
			'shop_id' => 'required',
			'shop_name' => '',
			'expires_at' => '',
		]);
		$map["shop_id"] = $params["shop_id"];
		$map["user_id"] = $params["uid"];
		$map["shop_type"] = "dy";
		$map["shop_name"] = $params["shop_name"];
		$map["expiration_time"] = $params["expires_at"];
		$map["authorization_time"] = date("Y-m-d H:i:s");
		$map["site_id"] = 1;
		$map["callback_params"] = json_encode(app("request")->all());
		$where=["user_id"=>$map["user_id"],"shop_id"=>$map["shop_id"],"shop_type"=>$map["shop_type"]];
		$data = UserShopModel::query()
			->where($where)
			->first();
		if($data) {
			UserShopModel::query()
				->where($where)
				->update($map);
		} else {
			UserShopModel::create($map);
		}
		return "SUCCESS";
	}


	// 淘宝店铺授权
	public function getShopAuthorize(Request $request)
	{
		$params = $this->validate($request, [
			'shopId' => 'required',
		]);
		$data = ErpLogic::getShopAuthorize();
		return $this->responseJson($data);
	}

	// 获取店铺信息
	public function getShopInfo(Request $request)
	{
		$params = $this->validate($request, [
			'goods_url' => 'required',
			'type' => [
				Rule::In([0, 1, 2]),
			], // 类型（0-淘宝，1-天猫，2-拼多多）
		]);
		$data = ErpLogic::getShopInfo();
		return $this->responseJson($data);
	}

	//订购时间查询
	public function getSubscribe(Request $request)
	{
		$params = $this->validate($request, [
			'nick' => 'required',
		]);
		$data = ErpLogic::getSubscribe();
		return $this->responseJson($data);
	}

	//订单列表查询
	public function listSold(Request $request)
	{
		$params = $this->validate($request, [
			'fields' => 'required',
			'shopId' => 'required',
			'startCreated' => 'string',
			'endCreated' => 'string',
			'status' => 'string',
			'buyerNick' => 'string',
			'type' => 'string',
			'extType' => 'string',
			'rateStatus' => 'string',
			'tag' => 'string',
			'pageNo' => 'integer',
			'pageSize' => 'integer',
			'useHasNext' => 'bool',
			'buyerOpenId' => 'string',
			'includeOaid' => 'string',
		]);
		$data = ErpLogic::listSold();
		return $this->responseJson($data);
	}

	//获取订单明细
	public function getFullInfo(Request $request)
	{

		$params = $this->validate($request, [
			'shopId' => 'required',
			'fields' => 'required',
			'tid' => 'required|integer',
			'includeOaid' => 'string',
		]);
		$data = ErpLogic::getFullInfo();
		return $this->responseJson($data);
	}

	//订单备注
	public function setMemo(Request $request)
	{
		$params = $this->validate($request, [
			'shopId' => 'required',
			'tid' => 'required',
			'memo' => 'required',
			'flag' => 'string',
		]);
		$data = ErpLogic::setMemo();
		return $this->responseJson($data);
	}


	//增量订单查询
	public function listIncrement(Request $request)
	{
		$params = $this->validate($request, [
			'fields' => 'required',
			'shopId' => 'required',
			'startCreate' => 'required',
			'endCreate' => 'required',
			'status' => 'string',
			'type' => 'string',
			'extType' => 'string',
			'tag' => 'string',
			'pageNo' => 'integer',
			'pageSize' => 'integer',
			'useHasNext' => 'bool',
			'includeOaid' => 'string',
		]);
		$data = ErpLogic::listIncrement();
		return $this->responseJson($data);
	}

//评价列表查询
	public function listTraderates(Request $request)
	{
		$params = $this->validate($request, [
			'shopId' => 'required',
			'fields' => 'required',
			'rateType' => 'required',
			'result' => 'String',
			'pageNo' => '',
			'pageSize' => '',
			'startDate' => '',
			'endDate' => 'bool',
			'tid' => '',
			'useHasNext' => '',
			'numIid' => '',
		]);
		$data = ErpLogic::listTraderates();
		return $this->responseJson($data);
	}

	//快递公司查询
	public function getCompanies(Request $request)
	{

		$params = $this->validate($request, [
			'fields' => 'required',
			'isRecommended' => 'bool',
			'orderMode' => 'string',
		]);
		$data = ErpLogic::getCompanies();
		return $this->responseJson($data);
	}

	//获取物流详情
	public function getCompaniesDetail(Request $request)
	{
		$params = $this->validate($request, [
			'shopId' => 'required',
			'fields' => 'required',
			'tid' => '',
			'buyerNick' => 'string',
			'status' => 'string',
			'seller_confirm' => 'string',
			'receiver_name' => 'string',
			'start_created' => 'string',
			'end_created' => 'string',
			'freight_payer' => 'string',
			'type' => 'string',
			'page_no' => 'string',
			'page_size' => 'string',
		]);
		$data = ErpLogic::getCompaniesDetail();
		return $this->responseJson($data);
	}

	//线下物流发货
	public function send(Request $request)
	{
		$params = $this->validate($request, [
			'subTid' => 'required',
			'shopId' => 'required',
			'tid' => 'required',
			'isSplit' => '',
			'outSid' => '',
			'companyCode' => '',
			'senderId' => '',
			'cancelId' => '',
			'feature' => '',
			'sellerIp' => '',
		]);
		$data = ErpLogic::send();
		return $this->responseJson($data);
	}

	//修改快递单号
	public function setResend(Request $request)
	{
		$params = $this->validate($request, [
			'shopId' => 'required',
			'tid' => 'required',
			'subTid' => '',
			'isSplit' => '',
			'outSid' => 'required',
			'companyCode' => 'required',
			'senderId' => '',
			'cancelId' => '',
			'feature' => '',
			'sellerIp' => '',
		]);
		$data = ErpLogic::setResend();
		return $this->responseJson($data);
	}

	//解密接口
	public function decode(Request $request)
	{
		$params = $this->validate($request, [
			'shopId' => 'required',
			'param' => 'required',
		]);
		$data = ErpLogic::decode();
		return $this->responseJson($data);
	}

    /**
     * 淘宝云打印接口
     */
    public function waybill()
    {
        $datas = TbErpLogic::requestTbErp("waybill");
        return $this->responseJson($datas);
	}

	//拼多多订购时间查询（拼）
	public function pgetShopInfo(Request $request)
	{
		$params = $request->all();
		$validator = Validator::make($params, [
			"owner_name" => "required",
			'edition' => 'integer｜in:1,2',
		]);
		if ($validator->fails()) {
			CommonUtil::throwException([422, $validator->errors()->first()]);
		}
		$data = ErpLogic::pgetShopInfo();

		$data = BaseUtil::parseArrayToLine($data);
		return $this->responseJson($data);
	}

	//订单列表查询（拼)
	public function pagingOrders(Request $request)
	{
		$params = $request->all();
		$validator = Validator::make($params, [
			"owner_id" => "required",
			'edition' => 'integer｜in:1,2',
			'page' => 'integer',
			'page_size' => 'integer',
			'start_time' => 'string',
			'end_time' => 'string',
			'remark' => 'string',
			'remark_tag' => 'integer',
			'remark_tag_name' => 'string',
		]);
		if ($validator->fails()) {
			CommonUtil::throwException([422, $validator->errors()->first()]);
		}
		$data = ErpLogic::pagingOrders();
		$data = BaseUtil::parseArrayToLine($data);
		return $this->responseJson($data);
	}

	//通过订单号获取订单（拼）
	public function mpagingOrders(Request $request)
	{
		$params = $request->all();
		$validator = Validator::make($params, [
			"owner_id" => "required",
			'edition' => 'integer｜in:1,2',
			'order_sns' => 'required|array',
		]);
		if ($validator->fails()) {
			CommonUtil::throwException([422, $validator->errors()->first()]);
		}
		$data = ErpLogic::mpagingOrders();
		$data = BaseUtil::parseArrayToLine($data);
		return $this->responseJson($data);
	}

	//非官方订单出单
	public function getwaybillfreewn(Request $request)
	{
		$params = $request->all();
		$validator = Validator::make($params, [
			"owner_id" => "required",
			"order_sn" => "required",
			"sender_name" => "required",
			"sender_mobile" => "required",
			"sender_province" => "required",
			"sender_city" => "required",
			"sender_town" => "required",
			"sender_detail" => "required",
			"wp_code" => "required",
			"waybill_type" => "required",
			"shop_code" => "required",
			"receiver_province" => "required",
			"receiver_city" => "required",
			"receiver_town" => "required",
			"receiver_address" => "required",
			"receiver_phone" => "required",
			"receiver_name" => "required",
			'edition' => 'integer｜in:1,2',
		]);
		if ($validator->fails()) {
			CommonUtil::throwException([422, $validator->errors()->first()]);
		}
		$data = ErpLogic::getwaybillfreewn();
		$data = BaseUtil::parseArrayToLine($data);
		return $this->responseJson($data);
	}

	//平台出单（拼）
	public function platformTheSingle(Request $request)
	{
		$params = $request->all();
		$validator = Validator::make($params, [
			"owner_id" => "required",
			"sender_name" => "required",
			"sender_mobile" => "required",
			"province" => "required",
			"city" => "required",
			"district" => "required",
			"detail" => "required",
			"wp_code" => "required",
			"waybill_type" => "required",
			"shop_code" => "required",
			"order_sn_list" => "required",
			'edition' => 'integer｜in:1,2',
		]);
		if ($validator->fails()) {
			CommonUtil::throwException([422, $validator->errors()->first()]);
		}
		$data = ErpLogic::platformTheSingle();
		$data = BaseUtil::parseArrayToLine($data);
		return $this->responseJson($data);
	}

	//批量发货（拼）
	public function pddShipFree(Request $request)
	{
		$params = $request->all();
		$validator = Validator::make($params, [
			"params" => "required|array",
		]);
		if ($validator->fails()) {
			CommonUtil::throwException([422, $validator->errors()->first()]);
		}
		$data = ErpLogic::pddShipFree();
		$data = BaseUtil::parseArrayToLine($data);
		return $this->responseJson($data);
	}

	//获取店铺信息(拼)
	public function pddShopInfo(Request $request)
	{
		$params = $request->all();
		$validator = Validator::make($params, [
			"item_id" => "required",
		]);
		if ($validator->fails()) {
			CommonUtil::throwException([422, $validator->errors()->first()]);
		}
		$data = ErpLogic::pddShopInfo();
		$data = BaseUtil::parseArrayToLine($data);

		return $this->responseJson($data);
	}

	//拼获取退货地址(拼)
	public function pddGetRefundAddress(Request $request)
	{
		$params = $request->all();
		$validator = Validator::make($params, [
			"owner_id" => "required",
		]);
		if ($validator->fails()) {
			CommonUtil::throwException([422, $validator->errors()->first()]);
		}
		$data = ErpLogic::pddGetRefundAddress();
		$data = BaseUtil::parseArrayToLine($data);
		return $this->responseJson($data);
	}

	//获取网点信息(拼)
	public function pddGetNetworkInformation(Request $request)
	{
		$params = $request->all();
		$validator = Validator::make($params, [
			"owner_id" => "required",
			"wp_code" => ""
		]);
		if ($validator->fails()) {
			CommonUtil::throwException([422, $validator->errors()->first()]);
		}
		$data = ErpLogic::pddGetNetworkInformation();
		$data = BaseUtil::parseArrayToLine($data);
		return $this->responseJson($data);
	}

	//单号回收(拼)（拼）
	public function pddWaybillRecovery(Request $request)
	{
		$params = $request->all();
		$validator = Validator::make($params, [
			"params" => "required|array",
		]);
		if ($validator->fails()) {
			CommonUtil::throwException([422, $validator->errors()->first()]);
		}
		$data = ErpLogic::pddWaybillRecovery();
		$data = BaseUtil::parseArrayToLine($data);
		return $this->responseJson($data);
	}

	//用订单同步接口(拼)
	public function pddSynchronizationOrder(Request $request)
	{
		$params = $request->all();
		$validator = Validator::make($params, [
			"params" => "required|array",
		]);
		if ($validator->fails()) {
			CommonUtil::throwException([422, $validator->errors()->first()]);
		}
		$data = ErpLogic::pddSynchronizationOrder();
		$data = BaseUtil::parseArrayToLine($data);
		return $this->responseJson($data);
	}
}
