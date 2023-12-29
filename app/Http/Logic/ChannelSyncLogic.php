<?php


namespace App\Http\Logic;


use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Models\NewsModel;
use App\Models\OrderConsignee;
use App\Models\UserOrder;
use App\Services\OrderConsigneePushDownService;
use App\Services\UserService;
use App\Services\Warehouses\WarehouseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use mysql_xdevapi\Exception;

class ChannelSyncLogic extends BaseLogic
{
	/**
	 * @param $package_id
	 * @param string $cancel_reason
	 * @return bool
	 * @throws \App\Exceptions\ApiException
	 * @throws \Throwable
	 */
	public static function syncCancelPackage($package_id,$cancel_reason = "")
	{
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
				$cancelData["status"] = PACKAGE_STATUS_CANCELED;
				if($cancel_reason) {
					$cancelData["cancel_reason"] = $cancel_reason;
					$cancelData["additional"] = $cancel_reason;
				}
				OrderConsignee::updateById($package_id, $cancelData);
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
			DB::commit();
			return true;
		}catch (Exception $e) {
			DB::rollBack();
			Log::error($e->getMessage());
			throw $e;
		}
	}
}
