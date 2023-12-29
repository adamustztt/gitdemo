<?php

use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use Illuminate\Support\Facades\DB;
use App\Models\OrderConsignee as OrderConsigneeModels;

class OrderConsignee
{

	/**
	 * 获取列表
	 * @param array $filter
	 * @param array $range
	 * 
	 * @param array $sort
	 * @return array
	 */
	public static function getList($filter = null, $range = null, $sort = null)
	{
		$bind = [];
		$sql = 'SELECT order_consignee.*, user_order.order_sn, user_order.price, user_order.shipping_fee,
					user_order.product_number, user_order.create_time, user_order.channel_id, user_order.user_id,
					product.weight AS product_weight, product.name AS product_name, product.ext_id AS product_ext_id,
					warehouse.ext_id AS warehouse_ext_id
				FROM order_consignee
				INNER JOIN user_order ON user_order.id = order_consignee.order_id
				LEFT JOIN user ON user_order.user_id = user.id
				LEFT JOIN product ON user_order.product_id = product.id
				LEFT JOIN product_warehouse ON product.id = product_warehouse.product_id
				LEFT JOIN warehouse ON product_warehouse.warehouse_id = warehouse.id
				' . DBHelper::getFilterSQLs('WHERE', $filter, $bind) . '
				' . DBHelper::getSortSQLs($sort, $bind) . '
				' . DBHelper::getRangeSQL($range);
		return DB::select($sql, $bind);
	}
	
	public static function getInfo($id)
	{
		return self::getList([
			Filter::makeDBFilter('order_consignee.id', $id, Filter::TYPE_EQUAL)
		], [ 0, 1 ])[0];
	}

	public static function getCount($filter = null)
	{
		$bind = [];
		$sql = 'SELECT COUNT(*) AS total
				FROM user_order
				LEFT JOIN warehouse ON warehouse.id = user_order.warehouse_id
				LEFT JOIN product ON user_order.product_id = product.id
				LEFT JOIN user ON user_order.user_id = user.id
				LEFT JOIN order_consignee ON user_order.id = order_consignee.order_id
				LEFT JOIN channel ON user_order.channel_id = channel.id
				' . DBHelper::getFilterSQLs('WHERE', $filter, $bind);
		return DB::select($sql, $bind)[0]['total'] ?? 0;
	}
	
	
	public static function addInternal($site_order_consignee_id,$site_id,$order_id, $consignee, $mobile,
										$province, $city, $district,
										$address, $ext_platform_order_sn,
									   $sync_status = USER_ORDER_SYNC_STATUS_PENDING,$status = PACKAGE_STATUS_PAYMENT)
	{
		$insert = [
			'order_id'=>$order_id,'consignee'=>$consignee,'mobile'=>$mobile,'province'=>$province,
			'city'=>$city,'district'=>$district,'address'=>$address,'ext_platform_order_sn'=>$ext_platform_order_sn,
			'sync_status'=>$sync_status,'status'=>$status,'site_id'=>$site_id,
			'site_order_consignee_id'=>$site_order_consignee_id
		];
		$res = OrderConsigneeModels::orderConsigneeCreate($insert);
		if(!$res){
			return false;
		}
		return $res;
//		$sql = 'INSERT INTO order_consignee (order_id, consignee, mobile,
//					province, city, district, address, ext_platform_order_sn, sync_status, status)
//				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
//		$bind = [ $order_id, $consignee, $mobile, $province, $city, $district, $address,
//			$ext_platform_order_sn,$sync_status, PACKAGE_STATUS_PENDING ];
//		return DB::insert($sql, $bind);
	}

	/**
	 * 订单编号,姓名,电话,地址
	 * @param $consignee
	 */
	public static function extract($consignee)
	{
		$c =  explode(',', $consignee);
		return [
			'ext_platform_order_sn' => $c[0],
			'name' => $c[1],
			'mobile' => $c[2],
			'address' => $c[3],
		];
	}

	/**
	 * 检测收货人信息是否正确
	 * @param array $consignees
	 */
	public static function check($consignees = [])
	{
		foreach ($consignees as $consignee) {
			if (empty($consignee['province']) || empty($consignee['city'])) {
				CommonUtil::throwException([422, '收货人 省或市不能为空']);
			}
			if (Util::checkRealName($consignee['consignee']) === false) {
				return false;
			}
//			if (Util::checkMobile($consignee['mobile']) === false) {
//				CommonUtil::throwException([422, '收货人手机号不正确']);
//			}
//			if (empty($consignee['platform_order_sn']) || empty($consignee['address'])) {
//				return false;
//			}
		}
		return true;
	}

	/**
	 * 发货成功
	 */
	public static function shipSuccessInternal($package_id)
	{
		$sql = 'UPDATE order_consignee SET status = ? WHERE id = ? LIMIT 1';
		$bind = [ PACKAGE_STATUS_SHIPPED, $package_id ];
		return DB::update($sql, $bind);
	}
	
	public static function shipCancelAndRefund($package_info)
	{
		$package_info->status = PACKAGE_STATUS_CANCELED;
		$ret = $package_info->save();
		if (!$ret) {
			CommonUtil::throwException(ErrorEnum::ERROR_EXT_CANCEL_PACKAGE_FAILED);
		}
		$user_order = \App\Models\UserOrder::getUserOrder(['id'=>$package_info->order_id]);
		$ret = User::refundInternal($user_order->user_id,
			$user_order->price * $user_order->product_number + $user_order->shipping_fee, $user_order->id);
		if ($ret === false) {
			CommonUtil::throwException(ErrorEnum::ERROR_EXT_CANCEL_PACKAGE_FAILED);
		}
		return true;
	}
	/**
	 * 同步订单成功
	 * @param $id
	 * @param $ext_order_sn
	 */
	public static function syncSuccessInternal($id, $ext_order_sn = null)
	{
		$sql = 'UPDATE order_consignee SET ext_order_sn = ?, sync_status = ? WHERE id = ? LIMIT 1';
		$bind = [$ext_order_sn, USER_ORDER_SYNC_STATUS_SUCCESS, $id];
		return DB::update($sql, $bind);
	}

	/**
	 * 同步订单失败
	 * @param $id
	 */
	public static function syncFailInternal($id)
	{
		$sql = 'UPDATE order_consignee SET sync_status = ? WHERE id = ? LIMIT 1';
		$bind = [ USER_ORDER_SYNC_STATUS_FAILED, $id ];
		return DB::update($sql, $bind);
	}

	public static function syncExpressInternal($package_id, $express)
	{
		$sql = 'UPDATE order_consignee SET express_no = ? WHERE id = ? LIMIT 1';
		$bind = [ $express, $package_id ];
		return DB::update($sql, $bind);
	}
	
	
	public static function changeAdditionalInternal($package_id, $additional)
	{
		$sql = 'UPDATE order_consignee SET additional = CONCAT(IFNULL(additional, \'\'), ?) WHERE id = ? LIMIT 1';
		$bind = [$additional, $package_id];
		return DB::update($sql, $bind);
	}
}
