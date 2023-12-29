<?php

use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Http\Logic\ProductLogic;
use App\Models\Site;
use App\Models\SitePrice;
use App\Models\UserLevelModel;
use App\Models\UserLevelPrice;
use App\Models\UserProductProfit;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class Cart
{
	public static function getList($filter = null, $range = null, $sort = null)
	{
		$bind = [];
		$sql = 'SELECT cart.*, product.name AS product_name, warehouse.price AS shipping_fee,
					product.channel_id,product.othumb,product.weight,
					product.stock,warehouse.alias_name
				FROM cart
				INNER JOIN product ON cart.product_id = product.id
				INNER JOIN site_product ON product.id = site_product.product_id
				INNER JOIN warehouse ON product.warehouse_id = warehouse.id
				' . DBHelper::getFilterSQLs('WHERE', $filter, $bind) . '
				' . DBHelper::getSortSQLs($sort, $bind) . '
				' . DBHelper::getRangeSQL($range);
		return DB::select($sql, $bind);
	}

	public static function getListByUid($uid)
	{
		$filter = [Filter::makeDBFilter('cart.user_id', $uid, Filter::TYPE_EQUAL)];
		return self::getList($filter, [0, 100]);
	}

	public static function getNormalList($uid, $site_id)
	{
		$filters = [
			Filter::makeDBFilter('site_product.site_id', $site_id, Filter::TYPE_EQUAL),
			Filter::makeDBFilter('cart.user_id', $uid, Filter::TYPE_EQUAL),
			Filter::makeDBFilter('cart.status', CART_STATUS_NORMAL, Filter::TYPE_EQUAL)
		];
		return self::getList($filters, [0, 100]);
	}

	/**
	 * 1、根据 site_id 和 product_id 确定零售价
	 * 2、lock site_product 一行
	 * 3、插入cart表
	 * @param $site_id
	 * @param $user_id
	 * @param $product_id
	 * @param int $numbers
	 */
	public static function addInternal($site_id, $user_id, $product_id, $warehouse_id, int $numbers)
	{
		$price = SiteProduct::getSitePriceForLock($site_id, $product_id);
		$sql = 'INSERT INTO cart (site_id, user_id, product_id, warehouse_id, product_number, price, last_op_time, status)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
		$bind = [$site_id, $user_id, $product_id, $warehouse_id, $numbers, $price, date('Y-m-d H:m:s'),
			CART_STATUS_NORMAL];
		$ret = DB::insert($sql, $bind);
		return $ret;
	}

	/**
	 * 删除购物车中某一项
	 * @param $cart_id
	 * @param $user_id
	 * @return int
	 */
	public static function deleteInternal($cart_id, $user_id)
	{
		$sql = 'UPDATE cart SET status = ? WHERE id = ? AND user_id = ? LIMIT 1';
		$bind = [CART_STATUS_DELETED, $cart_id, $user_id];
		return DB::update($sql, $bind);
	}

	/**
	 * 清空某用户购物车
	 * @param $user_id
	 * @return int
	 */
	public static function clearInternal($user_id)
	{
		$sql = 'UPDATE cart SET status = ? WHERE user_id = ? AND status = ?';
		$bind = [CART_STATUS_DELETED, $user_id, CART_STATUS_NORMAL];
		return DB::update($sql, $bind);
	}

	/**
	 * @author ztt
	 * 获取运费
	 * @param $site_id
	 * @param $warehouse_id
	 * @param $user_id
	 * @return mixed
	 */
	public static function getWarehousePrice($site_id,$warehouse_id,$user_id) {
		$userLevelPriceInfo = UserLevelPrice::getUserLevelPrice(['user_id'=>$user_id,"warehouse_id"=>$warehouse_id,'level'=>1]);
		if(!empty($userLevelPriceInfo)) {
			return $userLevelPriceInfo['common_areas_min'];
		}
		$warehouse_info = WareHouse::getInfo($warehouse_id);
		if($site_id == 1) {
			// 主站
			return $warehouse_info["price"];
		} else {
			//分站
			$site_price = SitePrice::getSitePrice(["site_id"=>$site_id,"warehouse_id"=>$warehouse_id]);
			return empty($site_price) ? $warehouse_info["price"] : $site_price["common_areas_min"];
		}
		
	}
	/**
	 * 计算购物车价格
	 * @param $items
	 * @param $consignee_count
	 * @param $site_id
	 * @return array
	 */
	public static function computeAmount($items, $consignee_count, $site_id,$user_id)
	{
//		$cart_list = Cart::getList([
//			Filter::makeDBFilter('cart.id', $items, Filter::TYPE_SET),
//			Filter::makeDBFilter('site_product.site_id', $site_id, Filter::TYPE_EQUAL),
//		]);
		$product_list = Product::getList([
			Filter::makeDBFilter('product.id', array_column($items, 'id'), Filter::TYPE_SET),
			Filter::makeDBFilter('site_product.site_id', $site_id, Filter::TYPE_EQUAL),
		]);
		$num_items = Arr::pluck($items,'num','id');
		$list = [];
		$total_count = 0;
		$total_product_price = 0;
		$total_freight_price = 0;
		foreach ($product_list as $product_info) {
			// 判断商品是否下线
			if($product_info["status"] != PRODUCT_STATUS_ONLINE) {
				CommonUtil::throwException(ErrorEnum::ERROR_PRODUCT_STATUS);
			}
			$warehouse_info = WareHouse::getInfo($product_info['warehouse_id']);
			// 判断仓库是否下线
			if($warehouse_info["status"] !=WARE_HOUSE_STATUS_NORMAL) {
				CommonUtil::throwException(ErrorEnum::ERROR_WAREHOUSE_STATUS);
			}
			// 运费
			//$userLevelPriceInfo = UserLevelPrice::getUserLevelPrice(['user_id'=>$user_id,"warehouse_id"=>$product_info['warehouse_id'],'level'=>1]);
			//$warehouse_price = empty($userLevelPriceInfo) ?  $warehouse_info['price'] : $userLevelPriceInfo['common_areas_min'];
			$warehouse_price = self::getWarehousePrice($site_id,$product_info['warehouse_id'],$user_id);
			$product_price = $product_info['site_price'];
			$product_number = $num_items[$product_info['product_id']];
			$total_price = ($product_price * $product_number) * $consignee_count;
			$freight_price = $warehouse_price * $consignee_count;
			$item = [
				'product_price' => $product_price, // 产品单价
				'product_name' => $product_info['name'], // 产品名
				'product_weight' => $product_info['weight'], // 产品重量
				'product_number' => $product_number * $consignee_count, // 产品数量
				'total_price' => $total_price, // 小计
			];
			$total_count += $item['product_number'];
			$list[] = $item;
			$total_product_price += $total_price;
			$total_freight_price += $freight_price;
		}
		$total_model = count($list);
		return [
			'list' => $list,
			'total_product_price' => $total_product_price, // 产品总价
			'total_model' => $total_model, // 款数
			'total_count' => $total_count, // 总数
			'total_freight_price' => $total_freight_price, // 总快递费
			'total_freight_number' => $total_model * $consignee_count, // 总快递数量
			'total_price' => $total_product_price + $total_freight_price, // 总价
		];
	}

    /**
     * 新版计算购物车价格
     * @param $items
     * @param $consignee_count
     * @param $site_id
     * @return array
     */
    public static function computeAmountV1($items, $consignee_count, $site_id,$user_id)
    {
//		$cart_list = Cart::getList([
//			Filter::makeDBFilter('cart.id', $items, Filter::TYPE_SET),
//			Filter::makeDBFilter('site_product.site_id', $site_id, Filter::TYPE_EQUAL),
//		]);
        $product_list = Product::getList([
            Filter::makeDBFilter('product.id', array_column($items, 'id'), Filter::TYPE_SET),
            Filter::makeDBFilter('site_product.site_id', $site_id, Filter::TYPE_EQUAL),
        ]);
        $num_items = Arr::pluck($items,'num','id');
        $list = [];
        $total_count = 0;
        $total_product_price = 0;
        $total_freight_price = 0;
        foreach ($product_list as $product_info) {
            // 判断商品是否下线
            if($product_info["status"] != PRODUCT_STATUS_ONLINE) {
                CommonUtil::throwException(ErrorEnum::ERROR_PRODUCT_STATUS);
            }
            $warehouse_info = WareHouse::getInfo($product_info['warehouse_id']);
            // 判断仓库是否下线
            if($warehouse_info["status"] !=WARE_HOUSE_STATUS_NORMAL) {
                CommonUtil::throwException(ErrorEnum::ERROR_WAREHOUSE_STATUS);
            }
            // 运费
            //$userLevelPriceInfo = UserLevelPrice::getUserLevelPrice(['user_id'=>$user_id,"warehouse_id"=>$product_info['warehouse_id'],'level'=>1]);
            //$warehouse_price = empty($userLevelPriceInfo) ?  $warehouse_info['price'] : $userLevelPriceInfo['common_areas_min'];
//            $warehouse_price = self::getWarehousePrice($site_id,$product_info['warehouse_id'],$user_id);
            $warehouse_price = \App\Http\Controllers\CartController::getWarehousePriceV1($product_info["product_id"],$user_id,$product_info["warehouse_id"]);
            $product_price = $product_info['site_price'];
            $product_number = $num_items[$product_info['product_id']];
            $total_price = ($product_price * $product_number) * $consignee_count;
            $freight_price = $warehouse_price * $consignee_count;
            $item = [
                'product_price' => $product_price, // 产品单价
                'product_name' => $product_info['name'], // 产品名
                'product_weight' => $product_info['weight'], // 产品重量
                'product_number' => $product_number * $consignee_count, // 产品数量
                'total_price' => $total_price, // 小计
            ];
            $total_count += $item['product_number'];
            $list[] = $item;
            $total_product_price += $total_price;
            $total_freight_price += $freight_price;
        }
        $total_model = count($list);
        return [
            'list' => $list,
            'total_product_price' => $total_product_price, // 产品总价
            'total_model' => $total_model, // 款数
            'total_count' => $total_count, // 总数
            'total_freight_price' => $total_freight_price, // 总快递费
            'total_freight_number' => $total_model * $consignee_count, // 总快递数量
            'total_price' => $total_product_price + $total_freight_price, // 总价
        ];
    }
	/**
	 * 新版计算购物车价格
	 * @param $items
	 * @param $consignee_count
	 * @param $site_id
	 * @return array
	 */
	public static function computeAmountV2($items, $consignee_count, $site_id,$user_id)
	{
		$userInfo = \App\Models\User::getById($user_id);
		$siteInfo = Site::query()->where("id",$site_id)->first();
		$site_user_id = $siteInfo->user_id;
		$product_list = Product::getList([
			Filter::makeDBFilter('product.id', array_column($items, 'id'), Filter::TYPE_SET),
			Filter::makeDBFilter('site_product.site_id', $site_id, Filter::TYPE_EQUAL),
		]);
		$num_items = Arr::pluck($items,'num','id');
		$list = [];
		$total_count = 0;
		$total_product_price = 0;
		$total_freight_price = 0;
		foreach ($product_list as $product_info) {
			// 判断商品是否下线
			if($product_info["status"] != PRODUCT_STATUS_ONLINE) {
				CommonUtil::throwException(ErrorEnum::ERROR_PRODUCT_STATUS);
			}
			$warehouse_info = WareHouse::getInfo($product_info['warehouse_id']);
			// 判断仓库是否下线
			if($warehouse_info["status"] !=WARE_HOUSE_STATUS_NORMAL) {
				CommonUtil::throwException(ErrorEnum::ERROR_WAREHOUSE_STATUS);
			}
			$express_info = \App\Http\Controllers\CartController::getWarehousePriceV2($product_info["product_id"],$user_id,$product_info['warehouse_id']);
			// 运费
			$shipping_fee = $express_info->price;
			// 会员优惠金额
			$preferential_amount = UserLevelModel::query()->where(["id"=>$userInfo->level_id,"status"=>1])->value("preferential_amount");
			if($preferential_amount) {
				$shipping_fee = $shipping_fee-$preferential_amount;
				// 防止亏钱  保险一点
				$warehouse_cost_price = \App\Models\Warehouse::query()->where("id",$product_info['warehouse_id'])->value("cost_price");
				if($shipping_fee<$warehouse_cost_price) {
					CommonUtil::throwException(ErrorEnum::ERROR_PRODUCT);
				}
			}
			if($site_id == 1) {
				$product_cost_price = $product_info["price"];
			} else {
				// 查找站长成本价

				$product_cost_price = ProductLogic::getSiteProductCostPrice($product_info["product_id"],$site_user_id);
			}

			// 判断是否给该用户单独设置过商品利润
			$user_profit = UserProductProfit::query()->where("user_id",$user_id)->value("user_profit");
			if(empty($user_profit)) {
				$user_profit = $product_info['profit'];
			}
			$site_price = $product_cost_price+$user_profit;// 商品价格 = 当前用户商品利润+当前站长成本价;
//			if($site_id == 1) {
//				$site_price = $product_cost_price+$user_profit;// 商品价格 = 当前用户商品利润+当前站长成本价;
//			} else {
//				$site_price = $user_profit+$product_cost_price;// 商品价格 = 当前用户商品利润+当前站长成本价;
//			}

			// 运费
			$warehouse_price = $shipping_fee;
			$product_price = $site_price;
			$product_number = $num_items[$product_info['product_id']];
			$total_price = ($product_price * $product_number) * $consignee_count;
			$freight_price = $warehouse_price * $consignee_count;
			$item = [
				'product_price' => $product_price, // 产品单价
				'product_name' => $product_info['name'], // 产品名
				'product_weight' => $product_info['weight'], // 产品重量
				'product_number' => $product_number * $consignee_count, // 产品数量
				'total_price' => $total_price, // 小计
			];
			$total_count += $item['product_number'];
			$list[] = $item;
			$total_product_price += $total_price;
			$total_freight_price += $freight_price;
		}
		$total_model = count($list);
		return [
			'list' => $list,
			'total_product_price' => $total_product_price, // 产品总价
			'total_model' => $total_model, // 款数
			'total_count' => $total_count, // 总数
			'total_freight_price' => $total_freight_price, // 总快递费
			'total_freight_number' => $total_model * $consignee_count, // 总快递数量
			'total_price' => $total_product_price + $total_freight_price, // 总价
		];
	}
}
