<?php

use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Http\Logic\External\OrderLogic;
use App\Models\DamaijiaWarehouseUserSource;
use App\Models\UserLevelPrice;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use App\Models\UserOrder as UserOrderModel;
use \App\Models\Product;
use \App\Models\OrderConsignee as OrderConsigneeModel;
use App\Models\User as UserModel;
class UserOrder
{
	public static function getList($filter = null, $range = null, $sort = null)
	{
		$bind = [];
		$sql = 'SELECT user_order.*,
					product.alias_name AS product_name, product.ext_id AS product_ext_id,warehouse.alias_name,
					package.package_count
				FROM user_order
				LEFT JOIN product ON user_order.product_id = product.id
				LEFT JOIN warehouse ON user_order.warehouse_id = warehouse.id
				LEFT JOIN (
					SELECT  order_id, COUNT(*) AS package_count
					FROM order_consignee
					GROUP BY order_id
				) package ON user_order.id = package.order_id
				' . DBHelper::getFilterSQLs('WHERE', $filter, $bind) . '
				' . DBHelper::getSortSQLs($sort, $bind) . '
				' . DBHelper::getRangeSQL($range);
		return DB::select($sql, $bind);
	}

	public static function getCount($filter = null)
	{
		$bind = [];
		$sql = 'SELECT COUNT(*) AS total
				FROM user_order
				LEFT JOIN product ON user_order.product_id = product.id
				LEFT JOIN (
					SELECT  order_id, COUNT(*) AS package_count
					FROM order_consignee
					GROUP BY order_id
				) package ON user_order.id = package.order_id
				' . DBHelper::getFilterSQLs('WHERE', $filter, $bind);
		return DB::select($sql, $bind)[0]['total'] ?? 0;
	}


	public static function getInfo($order_id)
	{
		$sql = 'SELECT user_order.*
				FROM user_order
				WHERE user_order.id = ?
				LIMIT 1';
		$bind = [$order_id];
		return DB::select($sql, $bind)[0];
	}

	public static function getInfoLimitByOrderIdAndUserId($order_id, $user_id)
	{
		$sql = 'SELECT * FROM user_order WHERE user_id = ? AND id = ? LIMIT 1';
		$bind = [$user_id, $order_id];
		return DB::select($sql, $bind)[0];
	}

	public static function generateSN($prefix = '')
	{
		$prefix = sprintf("%03d", $prefix);
		return date('YmdHms') . $prefix . rand(10000, 99999);
	}

	public static function addInternal($site_id, $site_order_id, $user_id, $source, $order_sn, $product_id, $product_number, $warehouse_id, $shipping_fee,
									   $price, $channel_id, $remark, $page_number, $total_price,$order_sent_type=3,$order_from=3,
									   $status = USER_ORDER_STATUS_PAYMENT,$pay_time=null)
	{
		$insert = [
			'user_id' => $user_id, 'source' => $source, 'order_sn' => $order_sn, 'product_id' => $product_id,
			'product_number' => $product_number, 'warehouse_id' => $warehouse_id, 'shipping_fee' => $shipping_fee,
			'price' => $price, 'channel_id' => $channel_id, 'remark' => $remark, 'status' => $status,
			'page_number' => $page_number, 'total_price' => $total_price, 'site_order_id' => $site_order_id,
			'site_id' => $site_id,'order_sent_type'=>$order_sent_type,'order_from'=>$order_from,
			'create_time'=>date('Y-m-d H:i:s'),'pay_time'=>$pay_time
			
		];
		$ret = UserOrderModel::userOrderCreate($insert);
		if ($ret === false) {
			return false;
		}
		return $ret->id;
	}

	/**
	 * 0、检测收货人信息是否是正确
	 * 1、获取该用户购物车数据
	 * 2、一条购物车 生成一条订单 一条订单生成 n 条收货人
	 * 3、计算总共需要多少钱、如果不够提示余额不足、如果够，继续生成订单
	 * @param $user_id
	 * @param string $source
	 * @param array $consignees
	 */
	public static function create($order_id, $user)
	{
		$user_id = $user['id'];
//		$balance = $user['balance'];
		DB::transaction(function ()use($order_id,$user_id) {
			$balance = User::getBalanceForLock($user_id);
			$order = UserOrderModel::getById($order_id);
			// 判断仓库是否下架
			$warehouse_info = \App\Models\Warehouse::getById($order->warehouse_id);
			if(!$warehouse_info || $warehouse_info->status!=WARE_HOUSE_STATUS_NORMAL) {
				CommonUtil::throwException(ErrorEnum::ERROR_WAREHOUSE_STATUS);
			}
			// 判断商品是否下架
			$product_info = Product::getById($order->product_id);
			if(!$product_info || $product_info->status!=PRODUCT_STATUS_ONLINE) {
				CommonUtil::throwException(ErrorEnum::ERROR_PRODUCT_STATUS);
			}
			
			
			self::ifTimeOut($order);
			if($order['total_price'] > $balance)
				CommonUtil::throwException(ErrorEnum::ERROR_EXT_BALANCE_NOT_ENOUGH);
			if(!$order)
				CommonUtil::throwException(ErrorEnum::ERROR_EXT_INVALID_ORDER_ID);
//			try {
				// 修改订单状态  订单支付时间
				$res = UserOrderModel::where('id', $order_id)->update(['status' => USER_ORDER_STATUS_PAID,'pay_time'=>date("Y-m-d H:i:s")]);
				if (!$res)
					CommonUtil::throwException(ErrorEnum::ERROR_ORDER_PAYMENT);
				// 修改订单下包裹状态
				$where = ['status' => 'f', 'order_id' => $order['id']];
				$res = OrderConsigneeModel::listWhereOrderConsignee($where, ['status' => USER_ORDER_STATUS_PAID]);
				if (!$res) {
					CommonUtil::throwException(ErrorEnum::ERROR_ORDER_PAYMENT);
				}
				// 扣除余额、记录余额日志
				$ret = User::balanceBuyInternal($user_id, $order['total_price'], $order_id);
				if ($ret === false) {
					CommonUtil::throwException(ErrorEnum::ERROR_EXT_BALANCE_NOT_ENOUGH);
				}
			// 更新用户累计消费
				$userInfo = UserModel::getById($user_id);
				$consume_total = $userInfo->consume_total;
				$consume_total  = $consume_total+$order['total_price'];
				UserModel::updateById($user_id,["consume_total"=>$consume_total]);
				
				return true;
//			}catch (Exception $exception)
//			{
//				CommonUtil::throwException(ErrorEnum::ERROR_ORDER_PAYMENT);
//			}
		});
	}


	/**
	 * 一、生成订单
	 * 二、生成收款人信息
	 * 三、每条收款人信息都要收物流费+商品费
	 * 四、更改余额、写入余额日志表
	 * @param $user_id
	 * @param $site_id
	 * @param $product_id
	 * @param $product_number
	 * @param $warehouse_id
	 * @param $source
	 * @param $consignees
	 */
	public static function createFromApi($site_order_id, $user_id, $site_id, $product_id, $product_number, $warehouse_id, $source,
										 $consignees, $remark = null)
	{
		// 接口参数判断
		$is_product = Product::getById($product_id);
		if (!$is_product)
			CommonUtil::throwException(ErrorEnum::ERROR_EXT_INVALID_PRODUCT_ID);
		// 判断商品是否下线
		if($is_product->status != PRODUCT_STATUS_ONLINE) {
			CommonUtil::throwException(ErrorEnum::ERROR_PRODUCT_STATUS);
		}
		if ($is_product['warehouse_id'] != $warehouse_id)
			CommonUtil::throwException(ErrorEnum::ERROR_EXT_INVALID_WAREHOUSE_ID);
		$warehouse_info = WareHouse::getInfo($warehouse_id);

		// 判断仓库是否下线
		if($warehouse_info["status"] !=WARE_HOUSE_STATUS_NORMAL) {
			CommonUtil::throwException(ErrorEnum::ERROR_WAREHOUSE_STATUS);
		}
		// 判断商品发货来源是否合法
		if(!empty($is_product["user_source"])) {
			if(!in_array($source,explode(",",$is_product["user_source"]))) {
				CommonUtil::throwException(ErrorEnum::ERROR_WAREHOUSE_SOURCE);
			}
		} else {
			// 判断仓库发货来源是否合法
			if(!DamaijiaWarehouseUserSource::getByWhere(["warehouse_id"=>$warehouse_id,"user_source"=>$source,"user_source_status"=>1])) {
				CommonUtil::throwException(ErrorEnum::ERROR_WAREHOUSE_SOURCE);
			}
		}
		
		$site_price = SiteProduct::getSitePriceForLock($site_id, $product_id);
//		DB::transaction(function () use (
//			$user_id, $source, $warehouse_info, $site_price, $product_id, $warehouse_id
//			, $product_number, $remark, $consignees, $site_order_id, $site_id
//		) {
        //开启事务
            DB::beginTransaction();
                //获取API用户对应的运费
                $userLevelPriceInfo = UserLevelPrice::getUserLevelPrice(['user_id'=>$user_id,"warehouse_id"=>$warehouse_id,'level'=>1]);
                if(!empty($userLevelPriceInfo)) {
                    //设置过价格则按设置的来获取
                    $shippingPrice = $userLevelPriceInfo['common_areas_min'];
                }else{
                    //获取仓库价
                    $shippingPrice = $warehouse_info['price'];
                }
                $page_number = count($consignees);
//                $total_price = $page_number * (($product_number * $site_price) + $warehouse_info['price']);
                $total_price = $page_number * (($product_number * $site_price) + $shippingPrice);
                //获取订单编号
                $orderNumber = self::generateSN($user_id);
                try {
                    $order_id = self::addInternal($site_id, $site_order_id, $user_id, $source, $orderNumber, $product_id,
                        $product_number, $warehouse_id, $shippingPrice, $site_price, $warehouse_info['channel_id'],
                        $remark, $page_number, $total_price,3,3,USER_ORDER_STATUS_PAID,date('Y-m-d H:i:s'));
                } catch (Exception $exception) {
                    DB::rollBack();
                    CommonUtil::throwException(ErrorEnum::ERROR_CREATE_ORDER_FAIL);
                }

                if ($order_id === false) {
                    DB::rollBack();
                    CommonUtil::throwException(ErrorEnum::ERROR_EXT_UNKNOWN);
                }
                
                foreach ($consignees as $consignee) {
                    // 记录收件人
                    $ret = OrderConsignee::addInternal($consignee['site_order_consignee_id'], $site_id, $order_id, 
						$consignee['consignee'], $consignee['mobile'], $consignee['province'], $consignee['city'],
						$consignee['district'], str_replace(' ', '', \App\Http\Utils\BaseUtil::getAddress($consignee['address'])),
                        $consignee['platform_order_sn'],USER_ORDER_SYNC_STATUS_PENDING,PACKAGE_STATUS_PENDING);
                    if ($ret === false) {
                        DB::rollBack();
                        CommonUtil::throwException(ErrorEnum::ERROR_EXT_UNKNOWN);
                    }
                }
                // 付款逻辑 扣费
                $ret = User::balanceBuyInternal($user_id, $total_price, $order_id);
                if (!$ret) {
                    DB::rollBack();
                    CommonUtil::throwException(ErrorEnum::ERROR_EXT_UNKNOWN);
                }
			DB::commit();
			$consigneesMsg = OrderLogic::getConsigneesMsg($product_id,$consignees);
			return [
			    "order_id"=>$order_id,
                "order_sn"=>$orderNumber,
				"consignees"=>$consigneesMsg
            ];
//		});
	}
	/**
	 * @param $order_id
	 * @param $user_id
	 * @throws \App\Exceptions\ApiException
	 */
	public static function submitPayment($order_id, $user_id, $site_id)
	{
		$where = ['site_id' => $site_id, 'site_order_id' => $order_id];
		$order = UserOrderModel::getUserOrder($where);
		self::ifTimeOut($order);	
		if (!$order)
			CommonUtil::throwException(ErrorEnum::ERROR_EXT_INVALID_ORDER_ID);
		if ($order['status'] != 'f')
			CommonUtil::throwException(ErrorEnum::ERROR_ORDER_STATUS);
		DB::transaction(function () use ($order_id, $user_id, $order, $where) {
			// 修改订单状态
			$res = UserOrderModel::userOrderUpdate($where, ['status' => USER_ORDER_STATUS_PAID,'pay_time'=>date('Y-m-d H:i:s')]);
			if (!$res)
				CommonUtil::throwException(ErrorEnum::ERROR_ORDER_PAYMENT);
			// 修改订单下包裹状态
			$where = ['status' => 'f', 'order_id' => $order['id']];
			$res = OrderConsigneeModel::listWhereOrderConsignee($where, ['status' => USER_ORDER_STATUS_PAID]);
			if (!$res)
				CommonUtil::throwException(ErrorEnum::ERROR_ORDER_PAYMENT);
			// 付款逻辑 扣费
			$ret = User::balanceBuyInternal($user_id, $order['total_price'], $order['id']);
			if (!$ret)
				CommonUtil::throwException(ErrorEnum::ERROR_EXT_UNKNOWN);
		});
	}
	public static function ifTimeOut($order)
	{
		$create_time = strtotime($order->create_time);
		$pay_time = $create_time + 2*60*60;
		if(time() > $pay_time){
			CommonUtil::throwException(ErrorEnum::ERROR_PAY_TIMEOUT);
		}
	}

	public static function cancelInternal($order_id)
	{
		$sql = 'UPDATE user_order SET status = ? WHERE id = ? LIMIT 1';
		$bind = [USER_ORDER_STATUS_CANCEL, $order_id];
		return DB::update($sql, $bind);
	}

	public static function cancelAndRefund($order_id)
	{
		$filter = [
			Filter::makeDBFilter('user_order.id', $order_id, Filter::TYPE_EQUAL),
		];
		$order_info = UserOrder::getInfo($order_id);
		$order_consignees = OrderConsignee::getList($filter);
		DB::beginTransaction();
		$ret = self::cancelInternal($order_id);
		if ($ret !== 1) {
			return false;
		}
		// 退款
		foreach ($order_consignees as $consignee) {
			$ret = User::refundInternal($order_info['user_id'],
				$order_info['price'] * $order_info['product_number'] + $order_info['shipping_fee'],
				$order_id);
			if ($ret === false) {
				return false;
			}
		}
		DB::commit();
		return true;
	}

	public static function syncPackage($package_id)
	{

	}

	/**
	 * spreadsheet 读取数据是根据列行读取，也就是先读取的是列，再去循环行，读出来的数据是一列列的，与往常的一行行有所区别
	 * 输出格式：
	 *         {
	 *           "consignee": "张三",
	 *           "mobile": "13868556969",
	 *           "province": "浙江省",
	 *           "city": "杭州市",
	 *           "district": "余杭区",
	 *           "address": "梦想小镇110号",
	 *           "platform_order_sn": "第三方平台订单号"
	 *           }
	 * @param Spreadsheet $sheet
	 */
	public static function parseAddress(Spreadsheet $sheet)
	{
		$headers = ['订单编号', '收货人姓名', '收货地址', '联系电话', '联系手机', '修改后的收货地址'];
		$indexes = [];
		$contents = [];
		$output = [];
		$sheet = $sheet->getActiveSheet();
		$col_count = Coordinate::columnIndexFromString($sheet->getHighestColumn());
		$row_count = $sheet->getHighestRow();
		for ($i = 1; $i <= $col_count; $i++) {
			$cell = trim($sheet->getCellByColumnAndRow($i, 1)->getValue());
			if (in_array($cell, $headers) === true) {
				$indexes[$i] = $cell;
			}
		}
		// 循环获取行数据
		for ($i = 2; $i <= $row_count; $i++) {
			$row = [];
			for ($j = 1; $j <= $col_count; $j++) {
				if (in_array($j, array_keys($indexes))) {
					$cell = self::replaceSpecialCharacter(trim($sheet->getCellByColumnAndRow($j, $i)->getValue()));
					$row[$j] = $cell;
				}
			}
			if ($row !== []) {
				$contents[] = $row;
			}
		}
		$indexes_reverse = array_flip($indexes);

	}

	/**
	 * 过滤excel中乱七八糟的null以及‘null这种字符串为空
	 * @param $str
	 */
	public static function replaceSpecialCharacter($str)
	{
		$reg = '/\'?null/';
		return preg_replace($reg, '', $str);
	}
}
