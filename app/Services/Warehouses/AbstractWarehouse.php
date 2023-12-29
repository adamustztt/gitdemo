<?php
/**
 * Created by PhpStorm.
 * User: wzz
 * Date: 2020/9/8
 * Time: 16:35
 */

namespace App\Services\Warehouses;


use App\Enums\ErrorEnum;
use App\Enums\WarehouseChannelEnum;
use App\Exceptions\ApiException;
use App\Exceptions\OuterApiException;
use App\Http\Logic\OrderConsigneeLogic;
use App\Http\Utils\LoggerFactoryUtil;
use App\Models\DamaijiaUserExpressPrice;
use App\Models\DamaijiaWarehouseUserSource;
use App\Models\ExpressProductModel;
use App\Models\ExpressWarehouseModel;
use App\Models\NewsModel;
use App\Models\OrderConsignee;
use App\Models\OrderConsigneePushDown;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductWarehouse;
use App\Models\Site;
use App\Models\SiteProduct;
use App\Models\UserOrder;
use App\Models\Warehouse;
use App\Services\OrderConsigneePushDownService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\DB;
use Tool\ShanTaoTool\QiWeiTool;

/**
 * 抽象仓库
 * Class AbstractWarehouse
 * @package App\Services\Warehouses
 */
abstract class AbstractWarehouse
{
	protected $channel = null;

	/**
	 * @var string 基础Url
	 */
	protected $baseUrl = '';
	/**
	 * @var float 请求超时时间
	 */
	protected $requestTimeout = 10;

	/**
	 * 是否有下一页
	 * @var bool
	 */
	protected $hasNextPage = false;


	/**
	 * @var array
	 */
	protected $requestParams;
	/**
	 * @var string
	 */
	protected $requestToken;
	/**
	 * @var string
	 */
	protected $requestUrl;
	protected $apiResponse;
	protected $damaijia_user_id;
	protected $baseProductId;
	protected $baseWarehouseId;
	protected $baseExpressId;
	/**
	 * 获取http客户端
	 * @param array $config
	 * @return Client
	 * @author wzz
	 */
	public function getHttpClient(array $config = [])
	{
		$arr = [
			'base_uri' => $this->baseUrl,
			'http_errors' => false, // 禁用HTTP协议抛出的异常(如 4xx 和 5xx 响应)
			'timeout' => $this->requestTimeout, // 请求超时的秒数。使用 0 无限期的等待(默认行为)。
		];
		return new Client(array_merge($arr, $config));
	}

	/**
	 * 请求仓库数据
	 * @return mixed
	 * @return array
	 * @throws GuzzleException
	 * @throws OuterApiException
	 * @author wzz
	 */
	abstract protected function requestWarehouse();


	/**
	 * 保存仓库
	 * @return mixed
	 * @throws GuzzleException
	 * @throws OuterApiException
	 * @author wzz
	 */
	public function saveWarehouse()
	{
		$list = $this->requestWarehouse();
		dd($list);
		if (empty($list)) {
			return true;
		}
		$source_arr = ["taobao","tmall","jd","pdd","other"];
		DB::transaction(function () use ($list,$source_arr) {
			$ext_id_arr=[];
			$policy_data=[];
			foreach ($list as $index => $item) {
				$ext_id_arr[] = $item['ext_id'];
				$warehouse = Warehouse::firstByChannelAndExtId($item['channel_id'], $item['ext_id'],$item['ext_express_id']??0);
				if($item['ext_id'] == 5) {
					$Log = new LoggerFactoryUtil(AbstractWarehouse::class);
					$Log->info(json_encode($warehouse));
					$Log->info(json_encode($item));
				}
				if (!$warehouse){
					$warehouseInfo = Warehouse::create($item);
					if($warehouseInfo) {
						foreach ($source_arr as $vv) {
							$source_data["warehouse_id"] = $warehouseInfo->id;
							$source_data["user_source"] = $vv;
							DamaijiaWarehouseUserSource::create($source_data);
						}
					}
					
				}else{
					// 如果更新价格变了 需要报警
					if($item['cost_price'] != $warehouse->cost_price) {
						$policy_data[$index]["仓源id"] = $warehouse->id;
						$policy_data[$index]["仓源别名"] = $warehouse->alias_name;
						$policy_data[$index]["原仓源成本价"] = $warehouse->cost_price;
						$policy_data[$index]["新仓源成本价"] = $item['cost_price'];
						$price_difference = $item['cost_price']-$warehouse->cost_price;
						$this->updateExpressWarehousePrice($warehouse->id,$price_difference,$item['cost_price']); // 更新仓库价格
					}
					unset($item['price']);
//					unset($item['cost_price']);
					unset($item['status']);
					$warehouse->update($item);
					
				}
			}
			// 价格变了报警
			if(!empty($policy_data)) {
				$policy = env("POLICE_FROM").date('Y-m-d H:i:s')."仓库成本价已修改的仓库".json_encode($policy_data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
				QiWeiTool::sendMessageToBaoJing($policy,env("CHANNEL_MONEY_POLICY"));
			}
			// 大卖家已上线仓库但是上游不返了   仓库下架 报警
			if(!empty($ext_id_arr)) {
				$warehouse_up_data = Warehouse::query()
					->where("channel_id",$this->channel)
					->where("status",WARE_HOUSE_STATUS_NORMAL)
					->whereNotIn("ext_id",$ext_id_arr)->select("id","name","alias_name")->get();
				if($warehouse_up_data->count()>0) {
					$updates = Warehouse::query()->where("channel_id",$this->channel)
						->where("status",WARE_HOUSE_STATUS_NORMAL)
						->whereNotIn("ext_id",$ext_id_arr)->update(["status"=>WARE_HOUSE_STATUS_FROZEN]);
					if(!$updates) {
						$policy = env("POLICE_FROM").date('Y-m-d H:i:s')."已下架失败".json_encode($warehouse_up_data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
						QiWeiTool::sendMessageToBaoJing($policy);
					}
					$policy = env("POLICE_FROM").date('Y-m-d H:i:s')."已下架仓库".json_encode($warehouse_up_data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
					QiWeiTool::sendMessageToBaoJing($policy,env("POLICE_CODE"));
				}
			}
		});
		return true;

	}

	/**
	 * 更新发货仓库价格
	 * @author ztt
	 * @param $warehouse_id
	 * @param $price_difference
	 */
	public function updateExpressWarehousePrice($warehouse_id,$price_difference,$cost_price)
	{
		$express_id = ExpressWarehouseModel::query()->where(["warehouse_id"=>$warehouse_id])->value("express_id");
		if($express_id) {
			if($price_difference >= 1) {
//				ExpressWarehouseModel::query()->where(["warehouse_id"=>$warehouse_id])->update(["cost_price"=>$cost_price]);//更改成本价
				DamaijiaUserExpressPrice::query()->where(["express_id"=>$express_id])->increment("price",$price_difference);
				DamaijiaUserExpressPrice::query()->where(["express_id"=>$express_id])->increment("vip_price",$price_difference);
				DamaijiaUserExpressPrice::query()->where(["express_id"=>$express_id])->increment("api_price",$price_difference);
				DamaijiaUserExpressPrice::query()->where(["express_id"=>$express_id])->increment("site_price",$price_difference);
				DamaijiaUserExpressPrice::query()->where(["express_id"=>$express_id])->increment("site_site_price",$price_difference);
			} else if($price_difference<= -1) {  // 价格变小了 只更改仓源仓库成本价
//				ExpressWarehouseModel::query()->where(["warehouse_id"=>$warehouse_id])->update(["cost_price"=>$cost_price]);//更改成本价
			}
		}
	}
	/**
	 * 请求产品数据
	 * @param $page
	 * @param $page_size
	 * @return array
	 * @author wzz
	 */
	abstract protected function requestProduct($page = 1, $page_size = 100);

	/**
	 * 保存产品
	 * @return mixed
	 * @author wzz
	 */
	public function saveProduct()
	{
		
		$list = $this->requestProduct(1);
		if (empty($list)) {
			return true;
		}
		DB::transaction(function () use ($list) {
			$policy_data = []; //报警数据
			$ext_id_arr = [];
			foreach ($list as $index => $item) {
				$ext_id_arr[] = $item['ext_id'];
//				$productInfo = Product::firstByWarehouseAndExtId($item['warehouse_id'], $item['ext_id']);
				$productInfoMap = Product::query()->where('ext_id', $item['ext_id'])
					->where('warehouse_id', $item['warehouse_id'])
					->where("ext_id", $item['ext_id'])->get();
				if($productInfoMap->count()) {
					foreach ($productInfoMap as $productInfo) {
						$update = [];
						$update['name'] = $item['name'];
						$update['thumb'] = $item['thumb'];
						$update['up_cost_price'] = $item['up_cost_price'];
//					$update['weight'] = $item['weight'];
						$update['stock'] = $item['stock'];
						// 如果不是快递云仓库(快递云商品和运费在一个价格里)  上游成本价变化  我们成本价也变化
						if($productInfo->channel_id != 10) {
							// 如果（自定义）成本价 < 采集价格（从上游获取的价格）  更新自定义成本价
							if($productInfo->cost_price <= $item['up_cost_price']) {
								$update['cost_price'] = $item['up_cost_price']+1;
							}
						}

						if(isset($item["up_status"]) && ($item["up_status"] == 2)) {  // 判断上游是否有商品状态且为下架状态
							$update["status"] = PRODUCT_STATUS_OFFLINE; // 更新商品状态下架
							$update["up_status"] = 2; //更新上游商品状态下架
						} else {
							$update["up_status"] = 1; //更新上游商品状态正常
						}
						// 如果更新价格变了 需要报警
						if(($item['up_cost_price'] - $productInfo->up_cost_price) >=1) {
							$policy_data[$index]["id"] = $productInfo->id;
							$policy_data[$index]["name"] = $productInfo->name;
							$policy_data[$index]["up_cost_price"] = $productInfo->up_cost_price;
							$policy_data[$index]["new_up_cost_price"] = $item['up_cost_price'];
						}
//					echo "更新的商品ID：".$productInfo->id.json_encode($update);
						$productInfo->update($update);
					}
					continue;
				}
				if(isset($item["up_status"])) { // 判断上游是否有商品状态
					if($item["up_status"] == 1){ //上游商品上架 创建
						unset($item["up_status"]);
						$item["cost_price"] =  $item['up_cost_price']+1; // 默认成本价
						$item["alias_name"] =  $item['name']; // 默认名称
						$productInfo = Product::create($item);
						$siteIds = Site::query()->select("id","user_id","parent_id")->get(); // 以后站长多了这里要优化
						foreach ($siteIds as $siteKey=>$siteValue) {
							$siteProductMap["user_id"] = $siteValue["user_id"];
							$siteProductMap["product_id"] = $productInfo->id;
							$siteProductMap["site_id"] = $siteValue["id"];
							$siteProductMap["price"] = $item['up_cost_price']+1; // 站长成本价默认
							$siteProductMap["selling_price"] = $item['up_cost_price']+3;// 这个字段目前版本废弃了
							$siteProductMap["profit"] =1;
							$siteProductMap["api_profit"] =1;
							$siteProductMap["site_profit"] =1;
							$siteProductMap["site_cost_profit"] =1;
							SiteProduct::create($siteProductMap);
						}
						
					} 
					// 不正常不创建
				} else {
					// 上游商品无状态直接创建商品
					$item["cost_price"] =  $item['up_cost_price']+1; // 默认成本价
					$item["alias_name"] =  $item['name']; // 默认名称
					$productInfo = Product::create($item);
					$siteIds = Site::query()->select("id","user_id","parent_id")->get(); // 以后站长多了这里要优化
					foreach ($siteIds as $siteKey=>$siteValue) {
						$siteProductMap["user_id"] = $siteValue["user_id"];
						$siteProductMap["product_id"] = $productInfo->id;
						$siteProductMap["site_id"] = $siteValue["id"];
						$siteProductMap["price"] = $item['up_cost_price']+1; // 利润默认是1
						$siteProductMap["profit"] =1;
						$siteProductMap["api_profit"] =1;
						$siteProductMap["site_profit"] =1;
						$siteProductMap["site_cost_profit"] =1;
						$siteProductMap["selling_price"] = $item['up_cost_price']+3;// 这个字段目前版本废弃了
						SiteProduct::create($siteProductMap);
					}
				}
				
			}
			// 价格变了报警
			if(!empty($policy_data)) {
				$policy = env("POLICE_FROM").date('Y-m-d H:i:s')."礼品价格已更改的商品".json_encode($policy_data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
				QiWeiTool::sendMessageToBaoJing($policy,env("POLICE_CODE"));
			}
			// 大卖家已上线商品但是上游不返了   商品下架 报警
			if(!empty($ext_id_arr)) {
				$product_up_data = Product::query()
					->where("channel_id",$this->channel)
					->where("status",PRODUCT_STATUS_ONLINE)
					->whereNotIn("ext_id",$ext_id_arr)->select("id","name")->get();
				if($product_up_data->count()>0) {
					$updates = Product::query()->where("channel_id",$this->channel)
						->where("status",PRODUCT_STATUS_ONLINE)
						->whereNotIn("ext_id",$ext_id_arr)->update(["status"=>PRODUCT_STATUS_OFFLINE,"up_status"=>2]);
					if(!$updates) {
						$policy = env("POLICE_FROM").date('Y-m-d H:i:s')."已下架失败".json_encode($product_up_data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
						QiWeiTool::sendMessageToBaoJing($policy);
					}
					$policy = env("POLICE_FROM").date('Y-m-d H:i:s')."已下架商品".json_encode($product_up_data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
					QiWeiTool::sendMessageToBaoJing($policy,env("POLICE_CODE"));
				}
			}
			
		});
		return true;
	}

	/**
	 * 请求下单数据
	 * @param $product
	 * @param $userOrder
	 * @param $orderConsignee
	 * @return mixed
	 * @author wzz
	 */
	abstract protected function requestOrder($product, $userOrder, $orderConsignee);

	/**
	 * 下单
	 * @param $orderConsignee
	 * @return mixed
	 * @author wzz
	 */
	public function createOrder($orderConsignee)
	{
		if($orderConsignee->is_decrypt == 1) {
			throw new ApiException(ErrorEnum::ERROR_ORDER_DECRYPT);
		}
		$userOrder = UserOrder::getById($orderConsignee->order_id);
		$product = Product::getById($userOrder->product_id);
		if($orderConsignee->is_encryption !=1) {
			$consigns_name = WarehouseService::strFilter($orderConsignee->consignee);
			if(empty($consigns_name)) {
				$consigns_name = "匿名";
			}
			$orderConsignee->consignee = $consigns_name;
			$orderConsignee->address = WarehouseService::strFilter($orderConsignee->address);
			$orderConsignee->district = WarehouseService::strFilter($orderConsignee->district);
			$orderConsignee->province = WarehouseService::strFilter($orderConsignee->province);
			$orderConsignee->city = WarehouseService::strFilter($orderConsignee->city);
			$orderConsignee->mobile = WarehouseService::strFilter($orderConsignee->mobile);
		}
		$this->damaijia_user_id = $userOrder->user_id;
		$this->baseProductId = $product->id;
		$this->baseWarehouseId = $product->warehouse_id;
		$this->baseExpressId = ExpressProductModel::query()->where("product_id",$product->id)->value("damaijia_express_id");
		$data = $this->requestOrder($product, $userOrder, $orderConsignee);
        $instance = new LoggerFactoryUtil(AbstractWarehouse::class);
        $instance->info("渠道ID:".$userOrder->channel_id);
        $instance->info("商品ID:".$userOrder->product_id);
        $instance->info("上游返回结果:".json_encode($data));
		// 判断单号是否重复报警
		if(!empty($data["express_no"])) {
			OrderConsigneeLogic::checkExpressNo($data["express_no"],$orderConsignee->id);
		}
		
		$bool =  OrderConsignee::updateById($orderConsignee->id, $data);
		if($bool) {
			$order = UserOrder::getById($orderConsignee->order_id);
			// `order_from` int(11) DEFAULT NULL COMMENT '1主站 2分站 3api',
//			if($order->order_from ==3) {
//				$push_data["site_id"] = $order->site_id;
//				$push_data["site_order_consignee_id"] = $orderConsignee->site_order_consignee_id;
//				$push_data["push_status"] = 1;
//				$push_data["push_type"] = 1;
//				$push_data["api"] = env("VTOOL_API"); // 目前只有一个api用户  后期添加api用户需要修改此处
//				$params_data["site_order_consignee_id"]=$orderConsignee->site_order_consignee_id;
//				$params_data["status"]="s"; //已发货
//				$push_data["params"] = json_encode($params_data);
//				OrderConsigneePushDown::create($push_data);
//			}
			$add_push = OrderConsigneePushDownService::addPush($orderConsignee->id,4); //推送订单
		}
		return $bool;
	}

	/**
	 * 查询请求查询订单
	 * @param $orderConsignee
	 * @return array
	 * @throws GuzzleException
	 * @throws OuterApiException
	 * @author wzz
	 */
	abstract protected function requestOrderQuery($orderConsignee);

	/**
	 * 查询订单
	 * @param $orderConsignee
	 * @return int
	 * @throws GuzzleException
	 * @throws OuterApiException
	 * @author wzz
	 */
	public function saveOrderByQuery($orderConsignee)
	{
		$data = $this->requestOrderQuery($orderConsignee);
		$instance = new LoggerFactoryUtil(AbstractWarehouse::class);
		$instance->info("订单数据ID:".$orderConsignee->id." 苍源仓库数据:".json_encode($data));
		if (empty($data['sync_query_status'])) {
			return 0;
		}
		$update = OrderConsignee::updateById($orderConsignee->id, $data);
		if(!empty($data['express_no'])) { // 推送
			$add_push = OrderConsigneePushDownService::addPush($orderConsignee->id,2); // 推送
			OrderConsigneeLogic::checkExpressNo($data["express_no"],$orderConsignee->id); // 检查单号是否重复报警
		}
		return $update;
	}

	/**
	 * 取消包裹
	 * @param $orderConsignee
	 * @return bool|int
	 * @author wzz
	 */
	public function cancelOrder($orderConsignee)
	{
		$bool = $this->requestCancelOrder($orderConsignee);
		if ($bool) {
//			return UserOrder::updateById($orderConsignee->order_id, ['status' => USER_ORDER_STATUS_CANCEL]);
			$cancel = OrderConsignee::updateById($orderConsignee->id, ['status' => PACKAGE_STATUS_CANCELED]);
			$add_push = OrderConsigneePushDownService::addPush($orderConsignee->id,3); //取消订单 添加推送信息
			$new["user_id"] = $orderConsignee->userOrder->user_id;
			$new["remark"] = "取消包裹";
			$new["type"] = 1;
			$new["order_id"] = $orderConsignee->userOrder->id;
			$new["package_id"] = $orderConsignee->id;
			NewsModel::create($new); //创建通知
			return $cancel;
		}
		return false;
	}

	/**
	 * 请求取消订单接口
	 * @param $orderConsignee
	 * @return mixed
	 * @author wzz
	 */
	abstract protected function requestCancelOrder($orderConsignee);
	/*
	 * 获取毫秒时间戳
	 */
	public function getMsec()
	{
		list($msec, $sec) = explode(' ', microtime());
		return intval(((float)$msec + (float)$sec) * 1000);
	}
	/*
	 * 报警
	 */
	public function policy($req,$channelName)
	{
		$policy_msg = [
			'功能' => "请求下单",
			'请求链接' => $this->requestUrl,
			'请求参数' => $this->requestParams,
			'响应结果' => $req,
			'信息时间' => date("Y-m-d H:i:s")
		];
		QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM") . $channelName . json_encode($policy_msg, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), env("POLICE_CODE"));
	}
}
