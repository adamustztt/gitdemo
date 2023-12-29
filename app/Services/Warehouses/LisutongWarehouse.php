<?php


namespace App\Services\Warehouses;


use App\Enums\ErrorEnum;
use App\Enums\WarehouseChannelEnum;
use App\Exceptions\ApiException;
use App\Exceptions\OuterApiException;
use App\Helper\CommonUtil;
use App\Http\Utils\LoggerFactoryUtil;
use App\Models\ExpressModel;
use App\Models\ExpressProductModel;
use App\Models\OrderConsignee;
use App\Models\Product;
use App\Models\UserOrder;
use App\Models\Warehouse;
use App\Services\util\LisutongWarehouseSign;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;
use Tool\ShanTaoTool\QiWeiTool;
use function GuzzleHttp\Psr7\build_query;

class LisutongWarehouse extends AbstractWarehouse
{

	protected $baseUrl = 'http://118.190.149.61:8089';


	protected $channel = WarehouseChannelEnum::LISUTONG;

	protected $requestTimeout = 20;

	/**
	 * @var string
	 */
	private $app_id;

	/**
	 * @var string
	 */
	private $secret;

	private $orderSourceMap = [
		USER_ORDER_SOURCE_TAOBAO => "0",
		USER_ORDER_SOURCE_TMALL => "4",
		USER_ORDER_SOURCE_PDD => "2",
		USER_ORDER_SOURCE_JD => "1",
		USER_ORDER_SOURCE_OTHER => "3",
	];
	private $product_list = [
		"AP20201111329095804",
		"AP20201111329095805",
		"AP20201111329095806",
		"AP20201111329095807",
		"AP20201111329095808",
		"AP20201111329095809",
		"AP20201111329095810",
		"AP20201111329095811",
		"AP20201111329095812",
		"AP20201111329095813",
		"AP20201111329095814",
		"AP20201111329095815",
		"AP20201111329095816",
		"AP20201111329095817",
		"AP20201111329095818",
		"AP20201111329095819",
		"AP20201111329095820",
		"AP20201111329095821",
		"AP20201111329095822",
		"AP20201111329095823",
		"AP20201111329095824",
		"AP20201111329095825",
		"AP20201111329095826",
	];
	public function __construct()
	{
		$this->app_id = config('warehouse.lisutong.appkey');
		$this->secret = config('warehouse.lisutong.appSecret');
		$this->baseUrl = config('warehouse.lisutong.domain');
	}

	public function requestWarehouse()
	{
		// 请求参数不能为空 随便传才可以
		$params = [
			"aa" => "11"
		];
		$response = $this->sendRequest('post', '/lst/api/getAccountPrime', $params);
		$expressList = $this->handleResponse($response);
		$list = [];
		foreach ($expressList as $express) {
			$ext_id = $express['wId'];
			foreach ($express['priceList'] as $v) {
				// logistisId=1 圆通  6 顺丰
				if (($v['logistisId'] == 1) || ($v['logistisId'] == 6)) {
					$cost_price = '';
					foreach ($v['apiPrimePriceVoList'] as $vv) {
						foreach ($vv['primePriceFirstVoList'] as $vvv) {
							if($vvv['startWeight'] == 0 && $vvv['price']<=4.3) {
								$cost_price = $vvv['price'] * 100;
							}
						}
					}
					$list[] = [
						'ext_id' => $ext_id,
						'ext_express_id' => $v['logistisId'],
						'name' => $express['warehouseName'] . "-" . $v['logisticsName'],
						'typename' => $v['logisticsName'],
						'cost_price' => $cost_price,
						'price' =>$cost_price+10,
						'address' => '',
						'channel_id' => $this->channel,
						'status' => WARE_HOUSE_STATUS_NORMAL,
					];
				}
				
//				if (($v['logistisId'] == 1) || ($v['logistisId'] == 6)) {
//					if($v['logistisId'] == 6) {
//						$cost_price = $v['apiPrimePriceVoList'][1]['primePriceFirstVoList'][0]['price'] * 100;
//					} else {
//						$cost_price = $v['apiPrimePriceVoList'][0]['primePriceFirstVoList'][0]['price'] * 100;
//					}
//					$list[] = [
//						'ext_id' => $ext_id,
//						'ext_express_id' => $v['logistisId'],
//						'name' => $express['warehouseName'] . "-" . $v['logisticsName'],
//						'typename' => $v['logisticsName'],
//						'cost_price' => $cost_price,
//						'price' =>$cost_price+10,
//						'address' => '',
//						'channel_id' => $this->channel,
//						'status' => WARE_HOUSE_STATUS_NORMAL,
//					];
//				}
			}
		}
		return $list;

	}

	/**
	 * @author ztt
	 * @param int $page
	 * @param int $page_size
	 * @return array
	 * @throws OuterApiException
	 */
	public function requestProduct($page = 1, $page_size = 100)
	{
		$data = $this->product_list;
		foreach ($data as $v) {
			$params = [
				"productId" => $v
			];
			$response = $this->sendRequest('post', '/lst/api/getProductStockInfo', $params);
			$productList[] = $this->handleResponse($response);
		}
	
		$list = [];
		foreach ($productList as $k => $v) {
			foreach ($v['list'] as $kk=>$vv) {
				$warehouse = Warehouse::firstByChannelAndExtId($this->channel, $v['wId'],$vv['lcId']);
				if (empty($warehouse)) {
					throw new OuterApiException('仓库不存在');
				}
				//productStatus商品授权状态 1 已授权 0 取消授权
				if($v["productStatus"] == 1) {
					$up_status = 1;
				} else {
					$up_status = 2;
				}
				$list[] = [
					'name' => $v['title'],
					'thumb' => $v['pic'],
					'up_cost_price' => $v['salePrice'] * 100,
					'weight' => $v['weight'] * 1000,
					'ext_id' => $v['apId'],
					'channel_id' => $this->channel,
					'status' => PRODUCT_STATUS_OFFLINE,
					'warehouse_id' => $warehouse->id,
					'stock' => 9999,
					'sales'=> rand(100,9999),
					'up_status'=>$up_status
				];
			}
		}
		return $list;
	}

	/**
	 * @author ztt
	 * @param $product
	 * @param $userOrder
	 * @param $orderConsignee
	 * @return array|mixed
	 * @throws OuterApiException
	 * @throws \App\Exceptions\ApiException
	 */
	public function requestOrder($product, $userOrder, $orderConsignee)
	{
		CommonUtil::throwException(["2000","礼速通仓库下单已改成手动下单"]);
		return false;
		// 如果报警次数超过三次 不在报警
		if($orderConsignee->policy_count >= 3) {
			CommonUtil::throwException(["2000","礼速通仓库下单已改成手动下单"]);
		}
		$policyMap["包裹id"] = $orderConsignee->id;
		$express_id = ExpressProductModel::query()->where(["product_id"=>$product->id])->value("damaijia_express_id");
		$policyMap["仓库名称"] = ExpressModel::query()->where("id",$express_id)->value("express_name");
		$policyMap["仓库ID"] = $express_id;
		$policyMap["damaijia_user_id"]=$this->damaijia_user_id;
		// 报警次数加一
		OrderConsignee::query()->where("id",$orderConsignee->id)->increment("policy_count");
		$policy = env("POLICE_FROM").date('Y-m-d H:i:s')."礼速通仓库下单请人工操作".json_encode($policyMap,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
		QiWeiTool::sendMessageToBaoJing($policy,"123");
		
		CommonUtil::throwException(["2000","礼速通仓库下单已改成手动下单"]);
		return false;
		$data[] = [  //下单列表Min： 1 Max：10
			'thirdOrderNo' => $orderConsignee->id . "", //用户自己的任务 id (唯一, 不能重复下单)
			'address' => [
				'address' => $orderConsignee->address,
				'city' => $orderConsignee->city,//收货人城市(全部为中文)
				'name' => $orderConsignee->consignee,//收货人姓名（不能为空）
				'province' => $orderConsignee->province,//收货人省份(全部为中文)
				'telephone' => $orderConsignee->mobile,//收货人电话号码（不能为空）
			],
			'productList' => [//产品信息 （至少存在一个商品）
				'productId' => $product->ext_id,//商品 id（多个商品必须处于统一仓库， 不支持多仓库下单）
				'num' => '1',//商品数量（必须大于 0）
			],
			'shopType' => $this->orderSourceMap[$userOrder->source], // String 店铺类型("0","淘宝"),("1","京东"),("2","拼多多"),("3","其他"),("4","天猫")
			'logisticsType' => $warehouse->ext_express_id,
		];
		$params = ['orderList' => $data];
		$response = $this->sendRequest('post', 'lst/api/submitOrderTask', $params);
		$data = $this->handleResponse($response);
		if ($data['fail'] !=0 ) {
			$error = [$data['failList'][0]['errorCode'], $data['failList'][0]['message']];
			Log::error($data['failList'][0]);
			throw new OuterApiException('LisutongWrehouse:' . json_encode($error).' params:' . json_encode($data));
//			CommonUtil::throwException($error);
		}
		return [
			'third_order_sn' => $data['successList'][0]['orderId'],
			'ext_order_sn' => '',
			'status' => PACKAGE_STATUS_SHIPPED,
			'sync_status' => USER_ORDER_SYNC_STATUS_SUCCESS,
		];

	}

	/**
	 * @author ztt
	 * @param $orderConsignee
	 * @return array
	 * @throws OuterApiException
	 * @throws \App\Exceptions\ApiException
	 */
	public function requestOrderQuery($orderConsignee)
	{
        //判断包裹是否存在上游订单号
        if(!$orderConsignee->third_order_sn){
            throw new ApiException(ErrorEnum::ERROR_INVALID_ORDER);
        }
		$params = [
			"orderId" => $orderConsignee->third_order_sn
		];
		$response = $this->sendRequest('post', '/lst/api/getOrderInfo', $params);
		$data = $this->handleResponse($response);
		// orderStatus 0待发货， 1-已发货 2-已退款
		if(in_array($data['orderStatus'],[1,2,0])) {
			return [
				'express_company_name' => $data['appointLogisticsName'],
				'express_no' => $data['logisticsId'],
				'sync_query_status' => USER_ORDER_SYNC_QUERY_STATUS_SUCCESS,
			];
		} else {
			return [
				'sync_query_status' => USER_ORDER_SYNC_QUERY_STATUS_PENDING,
			];
		}
	}

	protected function requestCancelOrder($orderConsignee)
	{
		// 不支持取消
		return false;
	}

	private function sendRequest(string $method, $uri, $data = [])
	{
		$client = $this->getHttpClient();
		$url = $this->baseUrl . $uri;
		$params = $this->signData($data, $url);
		$this->requestParams = $params;
		return $client->request($method, $uri, [
			'json' => json_decode($params, true)
		]);
	}

	private function handleResponse(ResponseInterface $response)
	{
		$contents = $response->getBody()->getContents();
		
		if (is_string($contents)) {
			$contents = json_decode($contents, true);
		}
		if ($contents['code'] != 200) {
			$class_name = CommonUtil::getClassName(get_class($this));
			$instance = new LoggerFactoryUtil(CaoshudaifaWarehouse::class);
			$instance->info("礼速通苍源返回数据".json_encode([
					'params'=> $this->requestParams,
					'response'=> $contents,
				]));
			QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM")."礼速通仓库".json_encode([
					'class_name'=> $class_name,
					'params'=> $this->requestParams,
					'response'=> $contents,
				],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
			throw new OuterApiException(sprintf('%s error:%s', $class_name, json_encode($contents)));
		}
		return $contents['data'] ?? [];
	}

	/**
	 * @param $content
	 * @return mixed
	 * 获取签名
	 * @author ztt
	 */
	private function signData($content, $url)
	{
		$ret = LisutongWarehouseSign::http_post_param($url, json_encode($content), $this->app_id, $this->secret);
		return $ret;
	}

	/**
	 * 	
	 * 一个奇葩的的需求 当你看到这里 我想你已经猜到需求是谁提的了 
	 * 
	 *  需求描述：8点半（3个订单以上预警出来），10点半（3个订单以上预警出来），3点半（3个订单以上预警出来），4点半（3个订单以上预警出来），5点（3个订单以上预警出来），5点半（有订单就要预警出来），5:40（有订单就要预警出来））。
	 *	6点半预警（3个订单以上预警出来）
	 * 
	 * 
	 * 之前是统一处理 礼速通上游之前的包裹下单就会预警 改成上面需求描述
	 */
	public static function policyLisutongOrder($min_count=0)
	{
		$packages = OrderConsignee::query()
				->where("status","p")
				->select("id","order_id")
				->get();
		$packageIds = [];
		$orderIds = [];
		foreach ($packages as $package) {
			$orderIds[] = $package["order_id"];
		}
		$lip_orderIds = UserOrder::query()
			->whereIn("id",$orderIds)
			->where("channel_id",WarehouseChannelEnum::LISUTONG)
			->pluck("id")
			->toArray();
		foreach ($packages as $package) {
			if(in_array($package["order_id"],$lip_orderIds)) {
				$packageIds[] = $package["id"];
			}
		}
		if(empty($packageIds)) {
			return "暂无包裹";
		}
		if(count($packageIds)>$min_count) {
			$packages = OrderConsignee::query()
				->where("status","p")
				->whereIn("id",$packageIds)
				->get();
			$policyMaps=[];
			foreach ($packages as $orderConsignee) {
				$userOrder = UserOrder::getById($orderConsignee->order_id);
				$product = Product::getById($userOrder->product_id);
				$policyMap["包裹id"] = $orderConsignee->id;
				$express_id = ExpressProductModel::query()->where(["product_id"=>$product->id])->value("damaijia_express_id");
				$policyMap["仓库名称"] = ExpressModel::query()->where("id",$express_id)->value("express_name");
				$policyMap["仓库ID"] = $express_id;
				$policyMap["damaijia_user_id"]=$userOrder->user_id;
				$policyMaps[] = $policyMap;
			}
			$policy=[];
			// 就显示前3个包裹id 和前3个用户id
			if(count($policyMaps) > 3) {
				$policy[0] = $policyMaps[0];
				$policy[1] = $policyMaps[1];
				$policy[2] = $policyMaps[3];
			} else {
				$policy = $policyMaps;
			}
			$policy = env("POLICE_FROM").date('Y-m-d H:i:s')."礼速通仓库下单请人工操作".json_encode($policy,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
			QiWeiTool::sendMessageToBaoJing($policy,"123");
			
		}
	}

}
