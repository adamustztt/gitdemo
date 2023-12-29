<?php
namespace App\Http\Controllers;

use App\Models\CustomWarehouseExpressModel;
use App\Models\CustomWarehouseModel;
use App\Models\ExpressProductModel;
use App\Services\UserService;
use App\Services\Warehouses\WarehouseService;
use Base;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use mysql_xdevapi\Exception;
use Param;
use App\Models\UserOrder;
use OrderConsignee AS Package;
use Filter;
use Sort;
use XiuPinJieSync;
use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Helper\WhereUtil;
use App\Models\OrderConsignee;
class PackageController extends BaseController
{

	/**
	 * @SWG\Post(
	 *     path="/package_get_list",
	 *     tags={"订单管理"},
	 *     summary="包裹列表",
	 *     description="包裹列表",
	 *     produces={"application/json"},
	 *     @SWG\Parameter(
	 *         name="body",
	 *         in="body",
	 *         required=true,
	 *          @SWG\Schema(
	 *            @SWG\Property(
	 *                  property="filter.order_sn",
	 *                  type="string",
	 *                  description="订单号",
	 *              )
	 *          )
	 *     ),
	 *     @SWG\Response(
	 *         response="200",
	 *         description="success",
	 *          @SWG\Schema(ref="#/definitions/PackageGetListBean")
	 *     ),
	 *     @SWG\Response(
	 *         response="403",
	 *         description="fail",
	 *         @SWG\Schema(ref="#/definitions/ErrorBean")
	 *     )
	 * )
	 */
	public function getList(Request $request) {
		$data = $this->validate($request, [
			'filter.create_time'=>'date_array',
			'filter.order_sn'=>'string',
			'filter.mobile'=>'phone',
			'filter.express_no'=>'string',
			'filter.status'=>'string',
			'range' => 'array',
		]);
		$date = date("Y-m-d H:i:s",time()-3600*24*30*6);

		if(empty($data["filter"]["create_time"])) {
			$data["filter"]["create_time"][0] = $date;
			$data["filter"]["create_time"][1] = date("Y-m-d H:i:s");
		} else {
			$data["filter"]["create_time"][0] = $date;
		}
		$data['filter']['user_id'] = $this->_user_info['id'];
		$sort = ["order_consignee.id","desc"];
		$where = [];
		$whereUtil = new WhereUtil($data['filter'],$where);
		$whereUtil->applyFilter('order_sn','user_order.order_sn');
		$whereUtil->applyFilter('mobile','order_consignee.mobile');
		$whereUtil->applyFilter('express_no','order_consignee.express_no');
		$whereUtil->applyFilter('status','order_consignee.status');
		$whereUtil->applyFilter('user_id','user_order.user_id');
		$whereUtil->applyDateFilter('create_time','user_order.create_time');
		$list = OrderConsignee::listOrderConsignee($where,$data['range'], $sort);
		$product_ids=[];
		foreach ($list as $k=>$v) {
			//如果包裹为待付款且超过两个小时 状态为取消状态
			if(($v['status'] ==PACKAGE_STATUS_PAYMENT) && (time()-strtotime($v['create_time'])>7200)) {
				$list[$k]['status'] = PACKAGE_STATUS_CANCELED;
				$list[$k]['express_company_name'] = PACKAGE_STATUS_CANCELED;
			}
			$product_ids[] = $v["product_id"];
		}
		$express_product = ExpressProductModel::query()->whereIn("product_id",$product_ids)->pluck("damaijia_express_id","product_id")->toArray();
		foreach ($list as $k=>$v) {
			$damaijia_express_id = $express_product[$v["product_id"]];
			$list[$k]["express_id"] = $damaijia_express_id;
			$custom_warehouse_id = CustomWarehouseExpressModel::query()->where("express_id",$damaijia_express_id)->value("custom_warehouse_id");
			$custom_warehouse_name = CustomWarehouseModel::query()->where("id",$custom_warehouse_id)->value("custom_warehouse_name");
			if($custom_warehouse_name) {
				$list[$k]["express_company_name"] = $custom_warehouse_name;
			}
			if(!empty($v["consignee_mask"])) {
				$list[$k]["address"] = $v["address_mask"];
				$list[$k]["consignee"] = $v["consignee_mask"];
				$list[$k]["mobile"] = $v["mobile_mask"];
			}
		}
		$total = OrderConsignee::getCount($where);
		return $this->responseJson([
			'index' => $data['range'][0],
			'total'=>$total,
			'list' => $list
		]);
	}
	public function getListV2(Request $request) {
		$data = $this->validate($request, [
			'filter.create_time'=>'date_array',
			'filter.order_sn'=>'string',
			'filter.mobile'=>'phone',
			'filter.express_no'=>'string',
			'filter.status'=>'string',
			'range' => 'array',
		]);
		$date = date("Y-m-d H:i:s",time()-3600*24*30*6);

		
		$query = OrderConsignee::query()->join("user_order","user_order.id","=","order_consignee.order_id")
			->join("product","product.id","=","user_order.product_id")
			->join("warehouse","warehouse.id","=","product.warehouse_id")
			->select("order_consignee.*","user_order.order_sn","user_order.warehouse_id", "user_order.price", "user_order.shipping_fee",
				"user_order.product_number", "user_order.create_time", "user_order.channel_id", "user_order.user_id","user_order.product_id","user_order.source",
				"product.weight AS product_weight","product.alias_name AS product_name","product.ext_id AS product_ext_id","warehouse.ext_id AS warehouse_ext_id")
			->with("warehouse:id,alias_name")
			->with("expressSheet")
			->where("user_order.user_id",$this->_user_info['id'])
			->orderBy("order_consignee.id","desc");
		if(empty($data["filter"]["create_time"])) {
			$data["filter"]["create_time"][0] = $date;
			$data["filter"]["create_time"][1] = date("Y-m-d H:i:s");
		} else {
			$data["filter"]["create_time"][0] = $date;
		}
		$query->whereBetween("user_order.create_time",$data["filter"]["create_time"]);
		if(!empty($data["filter"]["order_sn"])) {
			$query->where("user_order.order_sn",$data["filter"]["order_sn"]);
		}
		if(!empty($data["filter"]["mobile"])) {
			$query->where("order_consignee.mobile",$data["filter"]["mobile"]);
		}
		if(!empty($data["filter"]["express_no"])) {
			$query->where("order_consignee.express_no",$data["filter"]["express_no"]);
		}
		if(!empty($data["filter"]["status"])) {
			// 出单中
			if($data["filter"]["status"] == "d") {
				$query = $query->where("order_consignee.status","s");
				$query = $query->where("order_consignee.express_no","");
			} else if($data["filter"]["status"] == "s") {
				$query = $query->where("order_consignee.status","s");
				$query = $query->where("order_consignee.express_no","!=","");
			} else {
				$query->where("order_consignee.status",$data["filter"]["status"]);
			}
		}
		$total = $query->count();
		$list = $query->offset($data['range'][0])->limit($data['range'][1])->get();
		$product_ids=[];
		foreach ($list as $k=>$v) {
			//如果包裹为待付款且超过两个小时 状态为取消状态
			if(($v['status'] ==PACKAGE_STATUS_PAYMENT) && (time()-strtotime($v['create_time'])>7200)) {
				$list[$k]['status'] = PACKAGE_STATUS_CANCELED;
				$list[$k]['express_company_name'] = PACKAGE_STATUS_CANCELED;
			}
			$product_ids[] = $v["product_id"];
		}
		$express_product = ExpressProductModel::query()->whereIn("product_id",$product_ids)->pluck("damaijia_express_id","product_id")->toArray();
		foreach ($list as $k=>$v) {
			$damaijia_express_id = $express_product[$v["product_id"]];
			$list[$k]["express_id"] = $damaijia_express_id;
			$custom_warehouse_id = CustomWarehouseExpressModel::query()->where("express_id",$damaijia_express_id)->value("custom_warehouse_id");
			$custom_warehouse_name = CustomWarehouseModel::query()->where("id",$custom_warehouse_id)->value("custom_warehouse_name");
			if($custom_warehouse_name) {
				$list[$k]["express_company_name"] = $custom_warehouse_name;
			}
			if(!empty($v["consignee_mask"])) {
				$list[$k]["address"] = $v["address_mask"];
				$list[$k]["consignee"] = $v["consignee_mask"];
				$list[$k]["mobile"] = $v["mobile_mask"];
			}
		}
		return $this->responseJson([
			'index' => $data['range'][0],
			'total'=>$total,
			'list' => $list
		]);
	}
	/**
	 * 取消发货
	 */
//	public function cancelShip()
//	{
//		$req = Base::getRequestJson();
//		Base::checkAndDie([
//			'package_id' => Param::IS_INT . ERROR_INVALID_ID,
//		], $req);
//		$package_info = Package::getInfo($req['package_id']);
//		if ($package_info === null) {
//			CommonUtil::throwException(ErrorEnum::ERROR_EXT_INVALID_ORDER_ID);
//		}
//		if($package_info['status'] != PACKAGE_STATUS_PENDING){
//			CommonUtil::throwException(ErrorEnum::ERROR_PACKAGE_STATUS);
//		}
//		// 如果还未发货，直接取消即可
//		if ($package_info['status'] === PACKAGE_STATUS_PENDING) {
//			Package::shipCancelAndRefund($req['package_id']);
//			Base::dieWithResponse();
//		}
//		switch ($package_info['channel_id']) {
//			case 1:
//				$ret = XiuPinJieSync::cancelSingleOrder($package_info['id'], $package_info['ext_order_sn']);
//				if ($ret === false) {
//					CommonUtil::throwException(ErrorEnum::ERROR_EXT_CANCEL_ORDER_FAILED);
//				}
//				break;
//			case 2:
//				CommonUtil::throwException(ErrorEnum::INTERNAL_ERROR);
//				break;
//			case 3:
//
//				break;
//		}
//		Base::dieWithResponse();
//	}
	/**
	 * @author ztt
	 * 取消发货
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 * @throws \App\Exceptions\ApiException
	 * @throws \Throwable
	 */
	public function cancelShip(Request $request)
	{
		$params = $this->validate($request, [
			'package_id'=>'required|int',
		]);
		$package_id = $params['package_id'];
		// 获取包裹信息
		$order_consignee = OrderConsignee::getOrderConsigneeById($package_id)->toArray();
		if(empty($order_consignee) || ($order_consignee['status'] != PACKAGE_STATUS_PENDING)){
			CommonUtil::throwException(ErrorEnum::ERROR_EXT_INVALID_PACKAGE_ID);
		}
		DB::beginTransaction();
		try{
			//更改包裹状态
			OrderConsignee::updateById($package_id, ['status' => PACKAGE_STATUS_CANCELED]);
			$where['order_id'] = $order_consignee['order_id'];
			$where['order_consignee.status'] = PACKAGE_STATUS_PENDING;
			// 更改订单包裹退款状态
			if(OrderConsignee::getCount($where)>0){
				//订单包裹部分退款
				if($order_consignee['user_order']['consignee_status']==ORDER_CONSIGNEE_STATUS_NOTHING) {
					UserOrder::updateById($order_consignee['order_id'],['consignee_status' => ORDER_CONSIGNEE_STATUS_PART]);
				}
			} else {
				//订单包裹全部退款
				UserOrder::updateById($order_consignee['order_id'],['consignee_status' => ORDER_CONSIGNEE_STATUS_FULL]);
			}
			$user_id = $order_consignee['user_order']['user_id'];
			$amount = $order_consignee['user_order']['shipping_fee']+$order_consignee['user_order']['price'];
			$userService = new userService();
			$platform_profit = $order_consignee["platform_profit"];
			// 用户金额变动  用户资金流水
			$userService->incrUserBalance($user_id,$amount,$package_id,"（用户退款）包裹退款ID:".$package_id,"p",$platform_profit,1);
			DB::commit();
			return $this->responseJson();
		}catch (\Exception $e) {
			DB::rollBack();
			Log::error($e->getMessage());
			throw $e;
		}
	}
}
