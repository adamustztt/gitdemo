<?php

namespace App\Http\Controllers;

use App\Enums\WarehouseChannelEnum;
use App\Exceptions\ApiException;
use App\Http\Logic\ChannelSyncLogic;
use App\Http\Utils\LoggerFactoryUtil;
use App\Models\NewsModel;
use App\Models\OrderConsignee;
use App\Models\Site;
use App\Models\SiteBalanceLog;
use App\Models\User;
use App\Models\UserBalanceLog;
use App\Models\UserOrder;
use App\Services\OrderConsigneePushDownService;
use App\Services\SiteService;
use App\Services\UserService;
use App\Services\Warehouses\CaoshudaifaWarehouse;
use App\Services\Warehouses\MuhuoWarehouse;
use App\Services\Warehouses\WarehouseService;
use Illuminate\Http\Request;
use Base;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use mysql_xdevapi\Exception;
use Param;
use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use OrderConsignee as Package;
use Tool\ShanTaoTool\QiWeiTool;

class ChannelSyncController extends BaseController
{
	public function syncWarehouse(Request $request)
	{
		$req = Base::getRequestJson();
		Base::checkAndDie([
			'channel_id' => Param::IS_INT_ID . ERROR_INVALID_ID,
			'token' => Param::stringLength(64) . ERROR_INVALID_TOKEN
		], $req);

		if ($req['token'] !== config('app.admin_token')) {
			Base::dieWithError(ERROR_INVALID_TOKEN);
		}
		$ret = false;
//		switch ($req['channel_id']) {
////			case 1:
////				$ret = XiuPinJieSync::warehouseSync2Local();
////				break;
////			case 2:
////				$ret = DaiFaTuSync::warehouseSync2Local();
////				break;
////			case 3:
////				$ret = KB658Sync::warehouseSync2Local();
////				break;
//			case WarehouseChannelEnum::MUHUO:
//				$ret = (new MuhuoWarehouse())->saveWarehouse();
//				break;
//			case WarehouseChannelEnum::CSDF:
//				$ret = (new CaoShuDaiFaWarehouse())->saveWarehouse();
//				break;
//		}
		$ret = WarehouseService::getClass($req['channel_id'])->saveWarehouse();

		if ($ret === false) {
			//Base::dieWithError(ERROR_INTERNAL);
			CommonUtil::throwException(ErrorEnum::INTERNAL_ERROR);
		}
		return $this->responseJson();
	}

	public function syncProduct()
	{
		$req = Base::getRequestJson();
		Base::checkAndDie([
			'channel_id' => Param::IS_INT_ID . ERROR_INVALID_ID,
			'token' => Param::stringLength(64) . ERROR_INVALID_TOKEN
		], $req);

		if ($req['token'] !== config('app.admin_token')) {
			Base::dieWithError(ERROR_INVALID_TOKEN);
		}
		$ret = false;
//		switch ($req['channel_id']) {
////			case 1:
////				$ret = XiuPinJieSync::productSync2Local();
////				break;
////			case 2:
////				DaiFaTuSync::();
////				break;
////			case 3:
////				$ret = KB658Sync::productSync2Local();
////				break;
//			case WarehouseChannelEnum::MUHUO:
//				$ret = (new MuhuoWarehouse())->saveProduct();
//				break;
//		}
		$ret = WarehouseService::getClass($req['channel_id'])->saveProduct();

		if ($ret === false) {
			//Base::dieWithError(ERROR_INTERNAL);
			CommonUtil::throwException(ErrorEnum::INTERNAL_ERROR);
		}
		return $this->responseJson();
	}

	/**
	 * 请求下单接口
	 * @author wzz
	 */
	/*public function syncPackage()
	{
		$req = Base::getRequestJson();
		Base::checkAndDie([
			'token' => Param::stringLength(64) . ERROR_INVALID_TOKEN,
			'package_id' => Param::IS_INT_ID . ERROR_INVALID_ID,
		], $req);
		if ($req['token'] !== config('app.admin_token')) {
			Base::dieWithError(ERROR_INVALID_TOKEN);
		}

		$package_info = Package::getInfo($req['package_id']);
		if ($package_info === null) {
			//Base::dieWithError(ERROR_EXT_INVALID_ORDER_ID);
			CommonUtil::throwException(ErrorEnum::ERROR_EXT_INVALID_PACKAGE_ID);
		}
		$bool = 0;
//		switch ($package_info['channel_id']) {
////			case 1:
////				\XiuPinJieSync::syncSingleOrder($package_info);
////				break;
////			case 2:
////				Base::dieWithError(ERROR_INTERNAL);
////				break;
//			case WarehouseChannelEnum::MUHUO:
//				$muhuoWarehouse = new MuhuoWarehouse();
//				$bool = $muhuoWarehouse->createOrder($package_info);
//				break;
//		}
		$bool = WarehouseService::getClass($package_info['channel_id'])->createOrder($package_info);

		if (!$bool) {
			//Base::dieWithError(ERROR_INTERNAL);
			CommonUtil::throwException(ErrorEnum::INTERNAL_ERROR);
		}
		Base::dieWithResponse();
	}*/
	/**
	 * @author ztt
	 * 请求下单接口
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 * @throws \App\Exceptions\ApiException
	 */
	public function syncPackage(Request $request) {
		$params = $this->validate($request, [
			'token' => 'required|string',
			'package_id' => 'required|int',
		]);
		$package_info = OrderConsignee::getOrderConsigneeById($params['package_id'])->toArray();
		if(empty($package_info)) {
			CommonUtil::throwException(ErrorEnum::ERROR_EXT_INVALID_PACKAGE_ID);
		}
		//判断包裹是否已发货
        if($package_info["status"] != "p"){
            throw new ApiException(ErrorEnum::ERROR_PACKAGE_STATUS);
        }
		if (in_array($package_info["cancel_type"], [2,5])) {

			//地址停发 自动取消
			$cancel_req = ChannelSyncLogic::syncCancelPackage($params["package_id"]);
			$add_push = OrderConsigneePushDownService::addPush($params["package_id"], 1);// 如果是api用户 添加推送信息
			$policy_msg = [
				'功能' => "平台验证包裹为黑名单自动退款",
				'信息时间' => date("Y-m-d H:i:s"),
				'包裹ID' => $params["package_id"],
				'包裹信息' => json_decode($package_info),
				'提示消息' => "退款已成功"
			];
			QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM") . "包裹退款" . json_encode($policy_msg, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), env("POLICE_CODE"));
		}
        $log = new LoggerFactoryUtil(ChannelSyncController::class);
		$log->info(json_encode($package_info));
        //发货加锁防止重复发货
        /**
         * @var \Redis $redis
         */
        $lockKey = "syncPackage:".$params["package_id"];
        $lockFlag = Redis::setnx($lockKey,1);
        if($lockFlag){//加锁成功
            //给锁设置过期时间
            Redis::setex($lockKey,60,1);
            $bool = WarehouseService::getClass($package_info['user_order']['channel_id'])->createOrder((object)$package_info);
            if (!$bool) {
                $redis->del($lockKey);
                CommonUtil::throwException(ErrorEnum::INTERNAL_ERROR);
            }
        }else{
            //加锁失败则提示
            throw new ApiException(ErrorEnum::ERROR_PACKAGE_LOCK);
        }
        Redis::del($lockKey);
		return $this->responseJson();
	}
	/**
	 * 请求取消包裹
	 * @author wzz
	 */
	/*public function syncCancelPackage()
	{
		$req = Base::getRequestJson();
		Base::checkAndDie([
			'token' => Param::OPTIONAL . Param::stringLength(64) . ERROR_INVALID_TOKEN,
			'package_id' => Param::IS_INT_ID . ERROR_INVALID_ID,
		], $req);

		if ($req['token'] !== config('app.admin_token')) {
			Base::dieWithError(ERROR_INVALID_TOKEN);
		}

		$package_info = Package::getInfo($req['package_id']);
		if ($package_info === null) {
			//Base::dieWithError(ERROR_EXT_INVALID_ORDER_ID);
			CommonUtil::throwException(ErrorEnum::ERROR_EXT_INVALID_PACKAGE_ID);
		}
		$bool = 0;
//		switch ($package_info['channel_id']) {
////			case 1:
////				\XiuPinJieSync::cancelSingleOrder($package_info['id'], $package_info['ext_order_sn']);
////				break;
////			case 2:
////				Base::dieWithError(ERROR_INTERNAL);
////				break;
////			case 3:
////
////				break;
//			case WarehouseChannelEnum::MUHUO:
//				$muhuoWarehouse = new MuhuoWarehouse();
//				$bool = $muhuoWarehouse->cancelOrder($package_info);
//				break;
//		}
		$bool = WarehouseService::getClass($package_info['channel_id'])->cancelOrder($package_info);

		if (!$bool) {
			//Base::dieWithError(ERROR_INTERNAL);
			CommonUtil::throwException(ErrorEnum::INTERNAL_ERROR);
		}
		Base::dieWithResponse();
	}*/

	/**
	 * @author ztt
	 * 取消包裹
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 * @throws \App\Exceptions\ApiException
	 */
	public function syncCancelPackage(Request $request)
	{
		$param = $this->validate($request, [
			'token' => 'required|string',
			'package_id' => 'required|int',
		]);
		$package_id = $param['package_id'];
		// 包裹可以取消状态 (代发货  已发货) 
		$status_arr = [PACKAGE_STATUS_PENDING,PACKAGE_STATUS_SHIPPED];
		// 获取包裹信息
		$order_consignee = OrderConsignee::getOrderConsigneeById($package_id)->toArray();
		if(empty($order_consignee) || !in_array($order_consignee['status'],$status_arr)){
			CommonUtil::throwException(ErrorEnum::ERROR_EXT_INVALID_PACKAGE_ID);
		}
		DB::beginTransaction();
		try{
			$user_id = $order_consignee['user_order']['user_id'];
			// 如果包裹状态是代发货 不用请求上游
			if($order_consignee['status'] == PACKAGE_STATUS_PENDING) {
				//更改包裹状态
				OrderConsignee::updateById($package_id, ['status' => PACKAGE_STATUS_CANCELED]);
				$add_push = OrderConsigneePushDownService::addPush($package_id,3); //取消订单 添加推送信息
				$new["user_id"] = $user_id;
				$new["remark"] = "取消包裹";
				$new["type"] = 1;
				$new["order_id"] = $order_consignee["order_id"];
				$new["package_id"] = $package_id;
				NewsModel::create($new); //创建通知
			} else {
				$bool = WarehouseService::getClass($order_consignee['user_order']['channel_id'])->cancelOrder((object)$order_consignee);
				if (!$bool) {
					DB::rollBack();
					CommonUtil::throwException(ErrorEnum::INTERNAL_ERROR);
				}
			}
			$where['order_id'] = $order_consignee['order_id'];
			$where[] = [function($query) use ($status_arr){
				$query->whereIn('order_consignee.status', $status_arr);
			}];
			// 更改订单包裹退款状态
			if(OrderConsignee::getCount($where)>0){
				//订单包裹部分退款
				if($order_consignee['user_order']['consignee_status']==ORDER_CONSIGNEE_STATUS_NOTHING) {
					$result = UserOrder::updateById($order_consignee['order_id'],['consignee_status' => ORDER_CONSIGNEE_STATUS_PART]);
					if($result===false) {
						CommonUtil::throwException(ErrorEnum::INTERNAL_ERROR);
					}
				}
			} else {
				//订单包裹全部退款
				$result = UserOrder::updateById($order_consignee['order_id'],['consignee_status' => ORDER_CONSIGNEE_STATUS_FULL]);
				if($result===false) {
					CommonUtil::throwException(ErrorEnum::INTERNAL_ERROR);
				}
			}
			
			$amount = $order_consignee['user_order']['shipping_fee']+$order_consignee['user_order']['price'];
			$userService = new userService();
			$platform_profit = $order_consignee["platform_profit"];
			// 用户金额变动  用户资金流水
			$userService->incrUserBalance($user_id,$amount,$package_id,"包裹退款ID:".$package_id,"p",$platform_profit,1);
			// 如果不是主站 并且分站利润已经计算    则退回分站利润
			if($order_consignee["site_id"] != 1 && $order_consignee["is_belonged_site_income"] ==1) {
				// 查询分站该包裹的利润记录
				$logData = SiteBalanceLog::query()->where(array("context_id"=>$package_id,"site_id"=>$order_consignee["site_id"],"type"=>1,"type_name"=>4))->first();
				if($logData) {
					// 防止重复退款
					if(!SiteBalanceLog::query()->where(array("context_id"=>$package_id,"site_id"=>$order_consignee["site_id"],"type"=>2,"type_name"=>4))->first()) {
						SiteService::siteRefund($order_consignee["site_id"],$package_id,$logData->change_balance);
					}
				}
				// 退回上级站长利润
				$upSiteId = Site::query()->where("id",$order_consignee["site_id"])->value("parent_id");
				if($upSiteId>1) {
					// 查询分站的上级站长该包裹的利润记录
					$upLogData = SiteBalanceLog::query()->where(array("context_id"=>$package_id,"site_id"=>$upSiteId,"type"=>1,"type_name"=>7))->first();
					if($upLogData) {
						// 防止重复退款
						if(!SiteBalanceLog::query()->where(array("context_id"=>$package_id,"site_id"=>$upSiteId,"type"=>2,"type_name"=>7))->first()) {
							SiteService::siteRefund($upSiteId,$package_id,$upLogData->change_balance,7);
						}
					}
				}
			}
			DB::commit();
			return $this->responseJson();
		}catch (\Exception $e) {
			DB::rollBack();
			Log::error($e->getMessage());
			throw $e;
		}
	}
	/**
	 * 请求快递单号
	 * @author wzz
	 */
	public function syncExpress()
	{
		$req = Base::getRequestJson();
		Base::checkAndDie([
			'token' => Param::stringLength(64) . ERROR_INVALID_TOKEN,
			'package_id' => Param::IS_INT_ID . ERROR_INVALID_ID,
		], $req);
		if ($req['token'] !== config('app.admin_token')) {
			Base::dieWithError(ERROR_INVALID_TOKEN);
		}

		$package_info = Package::getInfo($req['package_id']);
		if ($package_info === null) {
			//Base::dieWithError(ERROR_EXT_INVALID_ORDER_ID);
			CommonUtil::throwException(ErrorEnum::ERROR_EXT_INVALID_PACKAGE_ID);
		}
//		if ($package_info['status'] !== PACKAGE_STATUS_SHIPPED) {
//			//Base::dieWithError(ERROR_INVALID_STATUS);
//			CommonUtil::throwException(ErrorEnum::ERROR_INVALID_STATUS);
//		}
		$bool = 0;
//		switch ($package_info['channel_id']) {
////			case 1:
//////				\XiuPinJieSync::syncSingleExpress($package_info['id'], $package_info['ext_order_sn']);
////				break;
////			case 2:
//////				Base::dieWithError(ERROR_INTERNAL);
////				break;
////			case 3:
////
////				break;
//			case WarehouseChannelEnum::MUHUO:
//				$muhuoWarehouse = new MuhuoWarehouse();
//				$bool = $muhuoWarehouse->saveOrderByQuery($package_info);
//				break;
//		}
		$package_info = (object)$package_info;
		$log = new LoggerFactoryUtil(ChannelSyncController::class);
		$log->info(json_encode($package_info));
		$bool = WarehouseService::getClass($package_info->channel_id)->saveOrderByQuery($package_info);

		if (!$bool){
			//Base::dieWithError(ERROR_INTERNAL);
			CommonUtil::throwException(ErrorEnum::INTERNAL_ERROR);
		}
		return $this->responseJson();
	}

}
