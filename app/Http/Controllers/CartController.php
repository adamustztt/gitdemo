<?php
namespace App\Http\Controllers;

use App\Enums\OrderFromEnum;
use App\Enums\OrderSentTypeEnum;
use App\Helper\CommonUtil;
use App\Http\Logic\BaseLogic;
use App\Http\Logic\BlackListLogic;
use App\Http\Logic\CartLogic;
use App\Http\Logic\ExpressLogic;
use App\Http\Logic\ProductLogic;
use App\Http\Utils\BaseUtil;
use App\Http\Utils\LoggerFactoryUtil;
use App\Models\AddressCity;
use App\Models\AddressProvince;
use App\Models\AddressTown;
use App\Models\BanCityModel;
use App\Models\DamaijiaUserExpressPrice;
use App\Models\DamaijiaWarehouseUserSource;
use App\Models\ExpressModel;
use App\Models\ExpressProductModel;
use App\Models\ExpressWarehouseModel;
use App\Models\Site;
use App\Models\SitePrice;
use App\Models\SiteProduct;
use App\Models\UserLevelModel;
use App\Models\UserLevelPrice;
use App\Models\UserProductProfit;
use App\Models\UserShopModel;
use Base;
use Illuminate\Http\Request;
use Param;
use Cart;
use App\Enums\ErrorEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use OrderConsignee;
use Product;
use Filter;
use Tool\ShanTaoTool\QiWeiTool;
use WareHouse;
use User;
use UserOrder;
use App\Models\UserOrder as UserOrderModel;
class CartController extends BaseController
{
	public function getList()
	{
		$list = Cart::getNormalList($this->_user_info['id'], $this->_user_info['site_id']);
		// 过滤下输出字段
		foreach ($list as &$item) {
			unset($item['channel_id']);
		}
		Base::dieWithResponse([
			'list' => $list
		]);
	}
	
	public function add()
	{
		$req = Base::getRequestJson();
		Base::checkAndDie([
			'product_id' => Param::IS_INT_ID . ERROR_INVALID_PRODUCT_ID,
			'warehouse_id' => Param::IS_INT_ID . ERROR_INVALID_WAREHOUSE_ID,
			'numbers' => Param::IS_INT_ID . ERROR_INVALID_PRODUCT_NUMBER
		], $req);
		// hack一下 不管前端传入多少数量 这里都会变成 1，需求决定代码
		$req['numbers'] = 1;
		$site_id = $this->_user_info['site_id'];
		$ret = Cart::addInternal($site_id, $this->_user_info['id'], $req['product_id'], $req['warehouse_id'],
			$req['numbers']);
		if ($ret === false) {
			//Base::dieWithError(ERROR_INTERNAL);
			CommonUtil::throwException(ErrorEnum::INTERNAL_ERROR);
		}
		Base::dieWithResponse();
	}
	
	public function delete()
	{
		$req = Base::getRequestJson();
		Base::checkAndDie([
			'cart_id' => Param::IS_INT_ID . ERROR_INVALID_ID,
		], $req);
		
		$ret = Cart::deleteInternal($req['cart_id'], $this->_user_info['id']);
		if ($ret !== 1) {
			//Base::dieWithError(ERROR_INTERNAL);
			CommonUtil::throwException(ErrorEnum::INTERNAL_ERROR);
		}
		Base::dieWithResponse();
	}
	
	public function clear()
	{
		$ret = Cart::clearInternal($this->_user_info['id']);
		if ($ret === 0) {
			//Base::dieWithError(ERROR_INTERNAL);
			CommonUtil::throwException(ErrorEnum::INTERNAL_ERROR);
		}
		Base::dieWithResponse();
	}

	/**
	 * 计算选中购物车的总价格
	 */
	public function computeAmount(Request $request)
	{
		$data = $this->validate($request, [
			'items' => 'required|array',
			'source' => 'required|string|min:1|max:10',
			'consignees' => 'required|array',
			'remark' => 'string|max:255',
			'order_sent_type'=>'required|int',
		]);
		// 订单来源  site_id=1  是主站
		$order_from = ($this->_user_info['site_id'] == 1) ? OrderFromEnum::MASTER_STATION : OrderFromEnum::SUBSTATION;
		$order_id = self::cartPaymentCreateOrder($this->_user_info['id'], $this->_user_info['site_id'],
			$data['items'], $data['source'], $data['consignees'],$data['remark'] ?? "",$data['order_sent_type'],$order_from);
		$count = count($data['consignees']);
		$arr = Cart::computeAmount($data['items'], $count, $this->_user_info['site_id'],$this->_user_info['id']);
		$arr['order_id'] = $order_id;
		Base::dieWithResponse($arr);
	}

    /**
     * 新版计算价格
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\ApiException
     */
    public function computeAmountV1(Request $request)
    {
        $data = $this->validate($request, [
            'items' => 'required|array',
            'source' => 'required|string|min:1|max:10',
            'consignees' => 'required|array',
            'remark' => 'string|max:255',
            'order_sent_type'=>'required|int',
			"is_deliver"=>"",
			"shop_id"=>""
        ]);
		$is_deliver = empty($data["is_deliver"]) ? 0 : $data["is_deliver"];
		$shop_id = empty($data["shop_id"]) ? 0 : $data["shop_id"];
        // 订单来源  site_id=1  是主站
        $order_from = ($this->_user_info['site_id'] == 1) ? OrderFromEnum::MASTER_STATION : OrderFromEnum::SUBSTATION;
//        $order_id = self::cartPaymentCreateOrderV1($this->_user_info['id'], $this->_user_info['site_id'],
			$order_id = self::cartPaymentCreateOrderV2($this->_user_info['id'], $this->_user_info['site_id'],
            $data['items'], $data['source'], $data['consignees'],$data['remark'] ?? "",
			$data['order_sent_type'],$order_from,$shop_id,$is_deliver);
        $count = count($data['consignees']);
        $arr = Cart::computeAmountV2($data['items'], $count, $this->_user_info['site_id'],$this->_user_info['id']);
        $arr['order_id'] = $order_id;
        return $this->responseJson($arr);
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
     * 获取运费 升级版
     * @param $site_id 站点ID
     * @param $productId 商品ID
     * @param $user_id 用户ID
     * @return mixed
     */
    public static function getWarehousePriceV1($productId,$userId,$warehouseId) {
        //1.获取用户信息
        $user = \App\Models\User::query()->find($userId);
        //2.获取该商品的发货地ID
        $expressProduct = ExpressProductModel::query()->where("product_id",$productId)->first();
        if(!$expressProduct){
            CommonUtil::throwException(ErrorEnum::ERROR_EXPRESS_SEND);
        }
        //3.获取发货地仓库的基本价
        $baseExpressWarehouse = ExpressWarehouseModel::query()->where("user_id",0)
            ->where("warehouse_id",$warehouseId)->where("express_id",$expressProduct["damaijia_express_id"])
            ->first();
        if(!$baseExpressWarehouse){
            CommonUtil::throwException(ErrorEnum::ERROR_BASE_EXPRESS_SEND);
        }
        $expressWarehouse = "";
        //4.判断该站点是否是主站
        if($user["site_id"]==1){
            //主站
            //判断该用户是否单独设置价格
            $expressWarehouse = ExpressWarehouseModel::query()->where("user_id",$userId)
                ->where("warehouse_id",$warehouseId)->where("express_id",$expressProduct["damaijia_express_id"])
                ->first();
        }else{
            //分站
            //获取站长
            $site = Site::query()->find($user["site_id"]);
            if(!$site){
                CommonUtil::throwException(ErrorEnum::ERROR_SITE_USER);
            }
            //获取站长的用户在主站的用户信息
            $userSite = \App\Models\User::query()->find($site["user_id"]);
            if(!$userSite){
                CommonUtil::throwException(ErrorEnum::ERROR_SITE_USER);
            }
            //判断该站长是否单独设置价 格
//            $expressWarehouse = ExpressWarehouseModel::query()->where("user_id",$userSite["id"])
//                ->where("warehouse_id",$warehouseId)->where("express_id",$expressProduct["damaijia_express_id"])
//                ->first();
			$expressWarehouse = ExpressLogic::getUserExpressPriceLogic($userId,$expressProduct["damaijia_express_id"],$warehouseId);
        }
        $baseExpressWarehouse = $expressWarehouse?$expressWarehouse:$baseExpressWarehouse;
        //5.判断用户VIP类型(暂时只有一个等级的VIP)
        return $baseExpressWarehouse["price"];
    }
	public static function getWarehousePriceV2($productId,$userId,$warehouseId) {
		//1.获取用户信息
		$user = \App\Models\User::query()->find($userId);
		//2.获取该商品的发货地ID
		$expressProduct = ExpressProductModel::query()->where("product_id",$productId)->first();
		if(!$expressProduct){
			CommonUtil::throwException(ErrorEnum::ERROR_EXPRESS_SEND);
		}
		//3.获取发货地仓库的基本价
		$baseExpressWarehouse = DamaijiaUserExpressPrice::query()->where("user_id",0)->where("express_id",$expressProduct["damaijia_express_id"])
			->first();
		if(!$baseExpressWarehouse){
			CommonUtil::throwException(ErrorEnum::ERROR_BASE_EXPRESS_SEND);
		}
		$log = new LoggerFactoryUtil(CartController::class);
		$log->info("商品ID->".$productId."---用户ID->".$userId."仓库ID->".$warehouseId."发货地Id->".$expressProduct["damaijia_express_id"]);
		return ExpressLogic::getUserExpressPriceLogic($userId,$expressProduct["damaijia_express_id"]);
	}
	public static function cartPaymentCreateOrder($user_id, $site_id, $items, $source, $consignees = [], $remark = null,$order_sent_type=3,$order_from=OrderSentTypeEnum::API)
	{
		if ($consignees === []) {
			CommonUtil::throwException(ErrorEnum::ERROR_EXT_INVALID_CONSIGNEE);
		}
		if (OrderConsignee::check($consignees) === false) {
			CommonUtil::throwException(ErrorEnum::ERROR_EXT_INVALID_CONSIGNEE);
		}
	
		$product_list = Product::getList([
			Filter::makeDBFilter('product.id', array_column($items, 'id'), Filter::TYPE_SET),
			Filter::makeDBFilter('site_product.site_id', $site_id, Filter::TYPE_EQUAL),
		]);
		$num_items = Arr::pluck($items,'num','id');
		$car_id_items = Arr::pluck($items,'cart_id','id');
		// 购物车为空直接返回
		if ($product_list === []) {
			CommonUtil::throwException(ErrorEnum::ERROR_SHOPPING_CART_NULL);
		}
		DB::beginTransaction();
		foreach ($product_list as $product_info) {
			$warehouse_info = WareHouse::getInfo($product_info['warehouse_id']);

			// 判断商品是否下线
			if($product_info["status"] != PRODUCT_STATUS_ONLINE) {
				CommonUtil::throwException(ErrorEnum::ERROR_PRODUCT_STATUS);
			}
			// 判断仓库是否下线
			if($warehouse_info["status"] !=WARE_HOUSE_STATUS_NORMAL) {
				CommonUtil::throwException(ErrorEnum::ERROR_WAREHOUSE_STATUS);
			}
			// 判断商品发货来源是否合法
			if(!empty($product_info["user_source"])) {
				if(!in_array($source,explode(",",$product_info["user_source"]))) {
					CommonUtil::throwException(ErrorEnum::ERROR_WAREHOUSE_SOURCE);
				}
			} else {
				// 判断仓库发货来源是否合法
				if(!DamaijiaWarehouseUserSource::getByWhere(["warehouse_id"=>$product_info['warehouse_id'],"user_source"=>$source,"user_source_status"=>1])) {
					CommonUtil::throwException(ErrorEnum::ERROR_WAREHOUSE_SOURCE);
				}
			}
			
			// 运费
			//$userLevelPriceInfo = UserLevelPrice::getUserLevelPrice(['user_id'=>$user_id,"warehouse_id"=>$product_info['warehouse_id'],'level'=>1]);
			//$shipping_fee = empty($userLevelPriceInfo) ?  $warehouse_info['price'] : $userLevelPriceInfo['common_areas_min'];

			$shipping_fee = self::getWarehousePrice($site_id,$product_info['warehouse_id'],$user_id);
			
			$site_price = $product_info['site_price'];
			$product_number = $num_items[$product_info['product_id']];
			$page_number = count($consignees);
			$total_price = $page_number * (($product_number * $site_price)+$shipping_fee);
			$order_id = UserOrder::addInternal($site_id,null,$user_id, $source, UserOrder::generateSN($user_id), $product_info['product_id'],
				$product_number, $product_info['warehouse_id'], $shipping_fee, $site_price, 
				$warehouse_info['channel_id'], $remark,$page_number,$total_price,$order_sent_type,$order_from);
			if ($order_id === false) {
				return false;
			}
			foreach ($consignees as $consignee) {
				if(empty($consignee['district'])) {
					$consignee['district']="其他区";
				}
				$disable_province=["新疆","西藏","香港","澳门","台湾"];
				foreach($disable_province as $key){
					if(strstr($consignee['province'],$key)){
						CommonUtil::throwException([180,"禁发地址：新疆，西藏，香港，澳门，台湾"]);
					}
				}
				//如果是礼速通（id=8）的 顺丰（id=6）  有一些省份不支持发货
				if(($warehouse_info['channel_id'] == 8) && ($warehouse_info['ext_express_id'] == 6) ){
					$disable_province=["辽宁", "吉林", "黑龙江", "青海", "甘肃", "宁夏", "内蒙古"];
					foreach($disable_province as $key){
						if(strstr($consignee['province'],$key)){
							CommonUtil::throwException([180,"(顺丰)禁发地址：辽宁，吉林，黑龙江，青海，甘肃，宁夏，内蒙，新疆，西藏，香港，澳门，台湾"]);
						}
					}
				}
				// 记录收件人
				$ret = OrderConsignee::addInternal(null,$site_id,$order_id, $consignee['consignee'], $consignee['mobile'],
					$consignee['province'], $consignee['city'], $consignee['district'], str_replace(' ', '', $consignee['address']),
					$consignee['platform_order_sn']);
				if ($ret === false) {
					return false;
				}
			}
			if (!empty($car_id_items[$product_info['product_id']])) {
				// 删除该条购物车
				Cart::deleteInternal($car_id_items[$product_info['product_id']], $user_id);
			}
		}
		DB::commit();
		return $order_id;
	}
    /**
     * 创建订单
     * @param $user_id
     * @param $site_id
     * @param $items
     * @param $source
     * @param array $consignees
     * @param null $remark
     * @param int $order_sent_type
     * @param int $order_from
     * @return bool
     * @throws \App\Exceptions\ApiException
     */
//    public static function cartPaymentCreateOrderV1($user_id, $site_id, $items, $source, $consignees = [], $remark = null,$order_sent_type=3,$order_from=OrderSentTypeEnum::API)
//    {
//        if ($consignees === []) {
//            CommonUtil::throwException(ErrorEnum::ERROR_EXT_INVALID_CONSIGNEE);
//        }
//        if (OrderConsignee::check($consignees) === false) {
//            CommonUtil::throwException(ErrorEnum::ERROR_EXT_INVALID_CONSIGNEE);
//        }
//
//        $product_list = Product::getList([
//            Filter::makeDBFilter('product.id', array_column($items, 'id'), Filter::TYPE_SET),
//            Filter::makeDBFilter('site_product.site_id', $site_id, Filter::TYPE_EQUAL),
//        ]);
//        $num_items = Arr::pluck($items,'num','id');
//        $car_id_items = Arr::pluck($items,'cart_id','id');
//        // 购物车为空直接返回
//        if ($product_list === []) {
//            CommonUtil::throwException(ErrorEnum::ERROR_SHOPPING_CART_NULL);
//        }
//        DB::beginTransaction();
//        foreach ($product_list as $product_info) {
//            $warehouse_info = WareHouse::getInfo($product_info['warehouse_id']);
//
//            // 判断商品是否下线
//            if($product_info["status"] != PRODUCT_STATUS_ONLINE) {
//                CommonUtil::throwException(ErrorEnum::ERROR_PRODUCT_STATUS);
//            }
//            // 判断仓库是否下线
//            if($warehouse_info["status"] !=WARE_HOUSE_STATUS_NORMAL) {
//                CommonUtil::throwException(ErrorEnum::ERROR_WAREHOUSE_STATUS);
//            }
//            // 判断商品发货来源是否合法
//            if(!empty($product_info["user_source"])) {
//                if(!in_array($source,explode(",",$product_info["user_source"]))) {
//                    CommonUtil::throwException(ErrorEnum::ERROR_WAREHOUSE_SOURCE);
//                }
//            } else {
//                // 判断仓库发货来源是否合法
//                if(!DamaijiaWarehouseUserSource::getByWhere(["warehouse_id"=>$product_info['warehouse_id'],"user_source"=>$source,"user_source_status"=>1])) {
//                    CommonUtil::throwException(ErrorEnum::ERROR_WAREHOUSE_SOURCE);
//                }
//            }
//            // 运费
//            $shipping_fee = self::getWarehousePrice($site_id,$product_info['warehouse_id'],$user_id);
//			$site_price = $product_info['site_price'];
//            $product_number = $num_items[$product_info['product_id']];
//            $page_number = count($consignees);
//            $total_price = $page_number * (($product_number * $site_price)+$shipping_fee);
//            $order_id = UserOrder::addInternal($site_id,null,$user_id, $source, UserOrder::generateSN($user_id), $product_info['product_id'],
//                $product_number, $product_info['warehouse_id'], $shipping_fee, $site_price,
//                $warehouse_info['channel_id'], $remark,$page_number,$total_price,$order_sent_type,$order_from);
//            if ($order_id === false) {
//                return false;
//            }
//            foreach ($consignees as $consignee) {
//                if(empty($consignee['district'])) {
//                    $consignee['district']="其他区";
//                }
//                $disable_province=["新疆","西藏","香港","澳门","台湾"];
//                foreach($disable_province as $key){
//                    if(strstr($consignee['province'],$key)){
//                        CommonUtil::throwException([180,"禁发地址：新疆，西藏，香港，澳门，台湾"]);
//                    }
//                }
//                //如果是礼速通（id=8）的 顺丰（id=6）  有一些省份不支持发货
//                if(($warehouse_info['channel_id'] == 8) && ($warehouse_info['ext_express_id'] == 6) ){
//                    $disable_province=["辽宁", "吉林", "黑龙江", "青海", "甘肃", "宁夏", "内蒙古"];
//                    foreach($disable_province as $key){
//                        if(strstr($consignee['province'],$key)){
//                            CommonUtil::throwException([180,"(顺丰)禁发地址：辽宁，吉林，黑龙江，青海，甘肃，宁夏，内蒙，新疆，西藏，香港，澳门，台湾"]);
//                        }
//                    }
//                }
//				$express_id = ExpressProductModel::query()->where("product_id",$product_info["product_id"])->value("damaijia_express_id");
//                $ban_address = BanCityModel::getBanAddressExpress($express_id,1,$consignee["province"]);
//                if(!$ban_address) {
//					$ban_address = BanCityModel::getBanAddressExpress($express_id,2,$consignee["city"]);
//				}
//				if(!$ban_address) {
//					$ban_address = BanCityModel::getBanAddressExpress($express_id,3,$consignee["district"],$consignee["city"]);
//				}
//				if($ban_address) {
//					CommonUtil::throwException(ErrorEnum::ERROR_BAN_CITY);
//				}
//                // 记录收件人
//                $ret = OrderConsignee::addInternal(null,$site_id,$order_id, $consignee['consignee'], $consignee['mobile'],
//                    $consignee['province'], $consignee['city'], $consignee['district'], str_replace(' ', '', $consignee['address']),
//                    $consignee['platform_order_sn']);
//                if ($ret === false) {
//                    return false;
//                }
//            }
//            if (!empty($car_id_items[$product_info['product_id']])) {
//                // 删除该条购物车
//                Cart::deleteInternal($car_id_items[$product_info['product_id']], $user_id);
//            }
//        }
//        DB::commit();
//        return $order_id;
//    }
	public static function cartPaymentCreateOrderV2($user_id, $site_id, $items, $source, $consignees = [], $remark = null,$order_sent_type=3,$order_from=OrderSentTypeEnum::API,$shop_id,$is_deliver)
	{
		$userInfo = \App\Models\User::getById($user_id);
		$siteInfo = Site::query()->where("id",$site_id)->first();
		$site_user_id = $siteInfo->user_id;
		if ($consignees === []) {
			CommonUtil::throwException(ErrorEnum::ERROR_EXT_INVALID_CONSIGNEE);
		}
		if (OrderConsignee::check($consignees) === false) {
			CommonUtil::throwException(ErrorEnum::ERROR_EXT_INVALID_CONSIGNEE);
		}
		$product_list = Product::getList([
			Filter::makeDBFilter('product.id', array_column($items, 'id'), Filter::TYPE_SET),
			Filter::makeDBFilter('site_product.site_id', $site_id, Filter::TYPE_EQUAL),
		]);
		$num_items = Arr::pluck($items,'num','id');
		$car_id_items = Arr::pluck($items,'cart_id','id');
		// 购物车为空直接返回
		if ($product_list === []) {
			CommonUtil::throwException(ErrorEnum::ERROR_SHOPPING_CART_NULL);
		}
		DB::beginTransaction();
		foreach ($product_list as $product_info) {
			$warehouse_info = WareHouse::getInfo($product_info['warehouse_id']);

			// 判断商品是否下线
			if($product_info["status"] != PRODUCT_STATUS_ONLINE) {
				CommonUtil::throwException(ErrorEnum::ERROR_PRODUCT_STATUS);
			}
			// 判断仓库是否下线
			if($warehouse_info["status"] !=WARE_HOUSE_STATUS_NORMAL) {
				CommonUtil::throwException(ErrorEnum::ERROR_WAREHOUSE_STATUS);
			}
			// 判断商品发货来源是否合法
			if(!empty($product_info["user_source"])) {
				if(!in_array($source,explode(",",$product_info["user_source"]))) {
					CommonUtil::throwException(ErrorEnum::ERROR_WAREHOUSE_SOURCE);
				}
			} else {
				// 判断仓库发货来源是否合法
				if(!DamaijiaWarehouseUserSource::getByWhere(["warehouse_id"=>$product_info['warehouse_id'],"user_source"=>$source,"user_source_status"=>1])) {
					CommonUtil::throwException(ErrorEnum::ERROR_WAREHOUSE_SOURCE);
				}
			}
			$express_info = self::getWarehousePriceV2($product_info["product_id"],$user_id,$product_info['warehouse_id']);
			$log = new LoggerFactoryUtil(CartController::class);
			$log->info(json_encode($express_info));
			// 运费
			$shipping_fee = $express_info->price;
			// 会员优惠金额
			$preferential_amount = UserLevelModel::query()->where(["id"=>$userInfo->level_id,"status"=>1])->value("preferential_amount");
			if($preferential_amount) {
				$shipping_fee = $shipping_fee-$preferential_amount;
				// 防止亏钱  保险一点
				$warehouse_cost_price = \App\Models\Warehouse::query()->where("id",$product_info['warehouse_id'])->value("cost_price");
				if($shipping_fee<$warehouse_cost_price) {
					$policy_msg["沧源ID"]=$product_info['warehouse_id'];
					$policy_msg["沧源价"]=$warehouse_cost_price;
					$policy_msg["运费"]=$shipping_fee;
					QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM")."运费小于成本价下单失败".json_encode($policy_msg,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),env("CHANNEL_MONEY_POLICY"));
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
			$site_price = $user_profit+$product_cost_price;// 商品价格 = 当前用户商品利润+当前站长成本价;
//			if($site_id == 1) {
//				$site_price = $user_profit+$product_cost_price;// 商品价格 = 当前用户商品利润+当前站长成本价;
//			} else {
//				$site_price = $user_profit+$product_cost_price;// 商品价格 = 当前用户商品利润+当前站长成本价;
//			}
			$site_freight_profit = 0; //站长的运费利润
			$up_site_freight_profit = 0; // 上级站长的运费利润
			$site_product_profit = 0; //站长商品利润
			$up_site_product_profit = 0; //上级站长商品利润
			if($site_id !=1) {
				$site_freight_profit = $shipping_fee-$express_info->site_price; //站长运费-站长成本价
				$site_product_profit = $user_profit;
				if($siteInfo->parent_id != 1) {//当前站长为二级站长
					$user_profit = UserProductProfit::query()->where("user_id",$site_user_id)->value("user_profit");
					$up_site_user_id = Site::query()->where("id",$siteInfo->parent_id)->value("user_id");
					if($user_profit) {
						$up_site_product_profit = $user_profit;
					} else {
						$up_site_product_profit = SiteProduct::query()->where("user_id",$up_site_user_id)->value("site_profit"); // 上级站长利润
					}
					$up_site_freight_profit = CartLogic::computeSiteProfit($site_id,$siteInfo->parent_id,$product_info["product_id"],$product_info['warehouse_id']);
				} 
			}
			$product_number = $num_items[$product_info['product_id']];
			$page_number = count($consignees);
			$total_price = $page_number * (($product_number * $site_price)+$shipping_fee);
			
		
			$insert = [
				'user_id' => $user_id, 'source' => $source, 'order_sn' => UserOrder::generateSN($user_id), 'product_id' => $product_info['product_id'],
				'product_number' => $product_number, 'warehouse_id' => $product_info['warehouse_id'], 'shipping_fee' => $shipping_fee,
				'price' => $site_price, 'channel_id' => $warehouse_info['channel_id'], 'remark' => $remark, 'status' => USER_ORDER_STATUS_PAYMENT,
				'page_number' => $page_number, 'total_price' => $total_price, 'site_order_id' => null,
				'site_id' => $site_id,'order_sent_type'=>$order_sent_type,'order_from'=>$order_from,
				'create_time'=>date('Y-m-d H:i:s'),'pay_time'=>null,
			];
			// 订单标记
			if($shop_id) {
				$userShopInfo = UserShopModel::query()->where(["shop_id"=>$shop_id,"user_id"=>$user_id,"is_delete"=>0])->first();
				$tag_match_type = ($userShopInfo["is_tag"] == 0) ? 0 : $userShopInfo["match_type"];
				$tag_color = $userShopInfo["tag_color"];
				$tag_remark = $userShopInfo["tag_remark"];
				$insert["tag_color"]=$tag_color;
				$insert["tag_remark"]=$tag_remark;
				$insert["tag_match_type"]=$tag_match_type;
			}
			$ret = UserOrderModel::query()->create($insert);
			if ($ret === false) {
				return false;
			}
			$order_id = $ret->id;
			
			foreach ($consignees as $consignee) {
				if(empty($consignee['district'])) {
					$consignee['district']="其他区";
				}
				$disable_province=["新疆","西藏","香港","澳门","台湾"];
				foreach($disable_province as $key){
					if(strstr($consignee['province'],$key)){
						CommonUtil::throwException([180,"禁发地址：新疆，西藏，香港，澳门，台湾"]);
					}
				}
				//如果是礼速通（id=8）的 顺丰（id=6）  有一些省份不支持发货
				if(($warehouse_info['channel_id'] == 8) && ($warehouse_info['ext_express_id'] == 6) ){
					$disable_province=["辽宁", "吉林", "黑龙江", "青海", "甘肃", "宁夏", "内蒙古"];
					foreach($disable_province as $key){
						if(strstr($consignee['province'],$key)){
							CommonUtil::throwException([180,"(顺丰)禁发地址：辽宁，吉林，黑龙江，青海，甘肃，宁夏，内蒙，新疆，西藏，香港，澳门，台湾"]);
						}
					}
				}
				$express_id = ExpressProductModel::query()->where("product_id",$product_info["product_id"])->value("damaijia_express_id");
				$ban_address = BanCityModel::getBanAddressExpress($express_id,1,$consignee["province"]);
				if(!$ban_address) {
					$ban_address = BanCityModel::getBanAddressExpress($express_id,2,$consignee["province"],$consignee["city"]);
				}
				if(!$ban_address) {
					$ban_address = BanCityModel::getBanAddressExpress($express_id,3,$consignee["province"],$consignee["city"],$consignee["district"]);
				}
				if($ban_address) {
					CommonUtil::throwException(ErrorEnum::ERROR_BAN_CITY);
				}
				
				$insert = [
					'order_id'=>$order_id,'consignee'=>$consignee['consignee'],'mobile'=>$consignee['mobile'],'province'=>$consignee['province'],
					'city'=>$consignee['city'],'district'=>$consignee['district'],'address'=>BaseUtil::getAddress(str_replace(' ', '', $consignee['address'])),
					'ext_platform_order_sn'=>$consignee['platform_order_sn'] ?? BaseUtil::randOrderNumber(),
					'sync_status'=>USER_ORDER_SYNC_STATUS_PENDING,'status'=>PACKAGE_STATUS_PAYMENT,'site_id'=>$site_id,
					'site_order_consignee_id'=>null,"site_freight_profit"=>$site_freight_profit,"up_site_freight_profit"=>$up_site_freight_profit,
					"site_product_profit"=>$site_product_profit,"up_site_product_profit"=>$up_site_product_profit,
					"shop_id"=>$shop_id,"is_deliver"=>$is_deliver,
					"tag_color"=>$tag_color ?? "","tag_remark"=>$tag_remark ?? "","tag_match_type"=>$tag_match_type ?? 0,
				];
				$ret = \App\Models\OrderConsignee::create($insert);
				if ($ret === false) {
					return false;
				}
			}
			if (!empty($car_id_items[$product_info['product_id']])) {
				// 删除该条购物车
				Cart::deleteInternal($car_id_items[$product_info['product_id']], $user_id);
			}
		}
		DB::commit();
		return $order_id;
	}
	/**
	 * 校验订单包裹收货人信息
	 * @author ztt
	 * 
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function verifyConsignee(Request $request)
	{
		$params = $this->validate($request, [
			'consignees' => 'required|array',
			'product_id'=>"required|integer",
			"is_deliver"=>""
		]);
		$is_deliver = $params["is_deliver"];
		$consignees = $params["consignees"];
		if(count($consignees)>500) {
			CommonUtil::throwException([1007,"最多支持500条"]);
		}
		foreach ($consignees as $k=>$v) {
			$message = [];
			$mark = true;
			if(empty(trim($v['address']))) {
				$message["address"] = "详细地址不能为空";
				$mark = false;
			}
			$consignees[$k]["platform_order_sn"] = BaseUtil::randOrderNumber();
//			if(empty(trim($v['platform_order_sn']))) {
//				$message["platform_order_sn"] = "订单号不能为空";
//				$mark = false;
//			}
//			$str=str_replace('-','0',trim($v['platform_order_sn']));
//			if(!is_numeric($str)) {
//				$message["platform_order_sn"] = "订单号错误";
//				$mark = false;
//			}
			if(empty(trim($v['consignee']))) {
				$message["consignee"] = "姓名不能为空";
				$mark = false;
			}
			if(strpos(trim($v['mobile']),'-') !== false){
				$mobile =  explode("-",trim($v['mobile']))[1];
			}else{
				$mobile = $v["mobile"];
			}
			if(strlen($mobile) != 11 || !is_numeric($mobile)) {
				$message["mobile"] = "手机号错误";
				$mark = false;
			}
			if(BlackListLogic::checkPhoneIsBlack($v['mobile'],$params["product_id"])) {
				$message["mobile"] = "手机号错误,请更换收获手机号";
				$mark = false;
			}
			if(BlackListLogic::checkPhoneIsBlackByUserId($this->_user_info['id'],$params["product_id"])) {
				$message["mobile"] = "手机号错误,请更换下单手机号";
				$mark = false;
			}
			$consignees[$k]["mobile"] = $mobile;
			
			$c_province  = mb_substr($v["province"],0,2,"utf-8");
			$province = AddressProvince::query()->where("name","like",'%'.$c_province.'%')->first();
			$express_id = ExpressProductModel::query()->where("product_id",$params["product_id"])->value("damaijia_express_id");
			if(!$province) {
				$message["province"] = "省错误";
				$mark = false;
			} else {
				if($province["status"] == 2) {
					$message["province"] = $v["province"]."不支持发货";
					$mark = false;
				} else {
					
					 $ban_address=BanCityModel::getBanAddressExpress($express_id,1,$province->name);
					if($ban_address) {
						$message["province"] = $v["province"]."已设置为禁发地";
						$mark = false;
					}
				}
			}
			
			if(empty($v["city"])) {
				$message["city"] = "城市不能为空";
				$mark = false;
			} else {
				if($v["city"] != "省直辖县") {
					$c_city = mb_substr($v["city"],0,2,"utf-8");
					$city = AddressCity::query()->where(["provinceCode"=>$province["code"]])->where("name","like","%".$c_city."%")->first();
					if(!$city) {
						$message["city"] = "城市错误";
						$mark = false;
					} else {
						$ban_address=BanCityModel::getBanAddressExpress($express_id,2,$province->name,$city->name);
						if($ban_address) {
							$message["city"] = $v["province"].$v["city"]."已设置为禁发地";
							$mark = false;
						}
					}
				}
				
			}
			
			if(!empty($v["district"])) {
				$ban_address=BanCityModel::getBanAddressExpress($express_id,3,$province->name,$v["city"],$v["district"]);
				if($ban_address) {
					$message["district"] = $v["province"].$v["city"].$v["district"]."已设置为禁发地";
					$mark = false;
				}
			}
//			$town= AddressTown::getByWhere(["name"=>$v["district"],"cityCode"=>$city["code"]]);
//			if(!$town && ($v["district"] != "其他区")) {
//				$message["district"] = "地区错误";
//				$mark = false;
//			}
			$consignees[$k]["mark"] = $mark;
			$consignees[$k]["message"] = (object)$message;
		}
		return $this->responseJson(["consignees"=>$consignees]);
	}
	public function verifyConsigneeV1(Request $request)
	{
		$params = $this->validate($request, [
			'consignees' => 'required|array',
			'product_id'=>"required|integer",
			"user_shop_id"=>"",
		]);
		$user_shop = UserShopModel::getById($params["user_shop_id"]);
		$shop_id = $user_shop["shop_id"];
		$shop_type = $user_shop["shop_type"];
		$consignees = $params["consignees"];
		if(count($consignees)>500) {
			CommonUtil::throwException([1007,"最多支持500条"]);
		}
		foreach ($consignees as $k=>$v) {
			$message = [];
			$mark = true;
			if(empty(trim($v['address']))) {
				$message["address"] = "详细地址不能为空";
				$mark = false;
			}
			if(empty(trim($v['platform_order_sn']))) {
				$message["platform_order_sn"] = "订单号不能为空";
				$mark = false;
			}
			$str=str_replace('-','0',trim($v['platform_order_sn']));
			if(!is_numeric($str)) {
				$message["platform_order_sn"] = "订单号错误";
				$mark = false;
			}
			if(empty(trim($v['consignee']))) {
				$message["consignee"] = "姓名不能为空";
				$mark = false;
			}
			if(strpos(trim($v['mobile']),'-') !== false){
				$mobile =  explode("-",trim($v['mobile']))[1];
			}else{
				$mobile = $v["mobile"];
			}
			if(strlen($mobile) != 11 || !is_numeric($mobile)) {
				$message["mobile"] = "手机号错误";
				$mark = false;
			}
			$consignees[$k]["mobile"] = $mobile;

			$c_province  = mb_substr($v["province"],0,2,"utf-8");
			$province = AddressProvince::query()->where("name","like",'%'.$c_province.'%')->first();
			$express_id = ExpressProductModel::query()->where("product_id",$params["product_id"])->value("damaijia_express_id");
			if(!$province) {
				$message["province"] = "省错误";
				$mark = false;
			} else {
				if($province["status"] == 2) {
					$message["province"] = $v["province"]."不支持发货";
					$mark = false;
				} else {
					$ban_address=BanCityModel::getBanAddressExpress($express_id,1,$province->name);
					if($ban_address) {
						$message["province"] = $v["province"]."已设置为禁发地";
						$mark = false;
					}
				}
			}

			if(empty($v["city"])) {
				$message["city"] = "城市不能为空";
				$mark = false;
			} else {
				if($v["city"] != "省直辖县") {
					$c_city = mb_substr($v["city"],0,2,"utf-8");
					$city = AddressCity::query()->where(["provinceCode"=>$province["code"]])->where("name","like","%".$c_city."%")->first();
					if(!$city) {
						$message["city"] = "城市错误";
						$mark = false;
					} else {
						$ban_address=BanCityModel::getBanAddressExpress($express_id,2,$province->name,$city->name);
						if($ban_address) {
							$message["city"] = $v["province"].$v["city"]."已设置为禁发地";
							$mark = false;
						}
					}
				}

			}

			if(!empty($v["district"])) {
				$ban_address=BanCityModel::getBanAddressExpress($express_id,3,$province->name,$v["city"],$v["district"]);
				if($ban_address) {
					$message["district"] = $v["province"].$v["city"].$v["district"]."已设置为禁发地";
					$mark = false;
				}
			}
//			$town= AddressTown::getByWhere(["name"=>$v["district"],"cityCode"=>$city["code"]]);
//			if(!$town && ($v["district"] != "其他区")) {
//				$message["district"] = "地区错误";
//				$mark = false;
//			}
			switch ($shop_type){
				case "pdd":
					$is_match = CartLogic::checkPlatformOrderSnIsSupportPddShopDeliver($v["platform_order_sn"],$shop_id);
						$consignees[$k]["is_match"] = $is_match;
					if($is_match == false) {
						$message["match"] = "匹配失败";
						$mark = false;
					}
					break;
				case "tb":
					$is_match = CartLogic::checkPlatformOrderSnIsSupportTbShopDeliver($v["platform_order_sn"],$shop_id);
					$consignees[$k]["is_match"] = $is_match;
					if($is_match == false) {
						$message["match"] = "匹配失败";
						$mark = false;
					}
					break;
				case "ks":
					$is_match = CartLogic::checkPlatformOrderSnIsSupportKsShopDeliver($v["platform_order_sn"],$shop_id);
					$consignees[$k]["is_match"] = $is_match;
					if($is_match == false) {
						$message["match"] = "匹配失败";
						$mark = false;
					}
					break;
				case "dy":
					$is_match = CartLogic::checkPlatformOrderSnIsSupportDyShopDeliver($v["platform_order_sn"],$shop_id);
					$consignees[$k]["is_match"] = $is_match["status"];
					if($is_match["status"] == false) {
						$message["match"] = $is_match["msg"];
						$mark = false;
					}
					break;
					
			}
			$consignees[$k]["mark"] = $mark;
			$consignees[$k]["message"] = (object)$message;
		}
		return $this->responseJson(["consignees"=>$consignees]);
	}
}
