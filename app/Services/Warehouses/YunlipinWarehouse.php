<?php
/**
 * Created by PhpStorm.
 * User: wzz
 * Date: 2020/10/20
 * Time: 10:54
 */

namespace App\Services\Warehouses;


use App\Enums\WarehouseChannelEnum;
use App\Exceptions\OuterApiException;
use App\Helper\CommonUtil;
use App\Http\Utils\LoggerFactoryUtil;
use App\Models\UserOrder;
use App\Models\Warehouse;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;
use Tool\ShanTaoTool\QiWeiTool;
use function GuzzleHttp\Psr7\build_query;

/**
 *
 * Class YunlipinWarehouse
 * @package App\Services\Warehouses
 */
class YunlipinWarehouse extends AbstractWarehouse
{
	protected $baseUrl = 'http://main.yunlipin.com.cn/shop-center/shop/';

	protected $channel = WarehouseChannelEnum::YUNLIPIN;

	protected $requestTimeout = 20;

	/**
	 * @var string
	 */
	private $app_id;

	/**
	 * @var string
	 */
	private $app_key;

	/**
	 * 需要的仓库id组
	 * @var int[]
	 */
	private $widArr = [21, 22, 23, 52, 53, 116, 117];
	private $orderSourceMap = [
		USER_ORDER_SOURCE_TAOBAO => 1,
		USER_ORDER_SOURCE_TMALL => 1,
		USER_ORDER_SOURCE_PDD => 3,
		USER_ORDER_SOURCE_JD => 4,
	];

	public function __construct()
	{
		$this->app_id = config('warehouse.yunlipin.app_id');
		$this->app_key = config('warehouse.yunlipin.app_key');
	}

	/**
	 * @inheritDoc
	 */
	public function requestWarehouse()
	{
		$list = [];
		$expressList = [
			'申通快递',
			'邮政快递包裹',
			'韵达快递',
			'圆通快递',
		];
		foreach ($expressList as $index=>$express) {
			
			$warehouse = Warehouse::firstByChannelAndTypename($this->channel, $express);
			if (!empty($warehouse)){
				continue;
			}

			$list[] = [
				'ext_id' => $index,
				'ext_express_id' => 0,
				'name' => '云礼品-'.$express,
				'typename' => $express,
				'cost_price' => 0,
				'price' => 0,
				'address' => '',
				'channel_id' => $this->channel,
				'status' => WARE_HOUSE_STATUS_NORMAL,
			];
		}
		return $list;
	}


	/**
	 * @inheritDoc
	 */
	public function requestProduct($page = 1, $page_size = 100)
	{
		$response = $this->sendRequest('post', 'product/api/all');
		$productList = $this->handleResponse($response);

		foreach ($productList['list'] as $index => $product) {
			$warehouse = Warehouse::firstByChannelAndTypename($this->channel, $product['delivery']);
			if (empty($warehouse)) {
				throw new OuterApiException('仓库不存在');
			}
			$list[] = [
				'name' => $product['productName'],
				'thumb' => $product['productImg'],
				'up_cost_price' => $product['productPrice'] * 100,
				'weight' => $product['productKg'] * 100,
				'ext_id' => $product['productCode'],
				'channel_id' => $this->channel,
				'status' => PRODUCT_STATUS_OFFLINE,
				'warehouse_id' => $warehouse->id,
				'stock' => 9999,
				'sales'=> rand(100,9999)
			];
		}
		return $list;
	}

	/**
	 * @inheritDoc
	 */
	public function requestOrder($product, $userOrder, $orderConsignee)
	{
		$warehouse = Warehouse::getById($product->warehouse_id);
		$params = [
			'orders' => [
				[
					'address' => $orderConsignee->province.' '.$orderConsignee->city .' '.
						$orderConsignee->district.' '.$orderConsignee->address,
//				'province' => (string)$orderConsignee->province,
//				'city' => (string)$orderConsignee->city,
//				'region' => (string)$orderConsignee->district,
					'name' => (string)$orderConsignee->consignee,
					'phone' => (string)$orderConsignee->mobile,
					'delivery' => (string)$warehouse->typename,
					'orderNo' => (string)$orderConsignee->id,
					'platform' => $this->orderSourceMap[$userOrder->source],
					'products' => [
						[
							'count' => $userOrder->product_number,
							'code' => (string)$product->ext_id,
						],
					],
				],
			]
//			'userOrderVo' => [
//				'order' => [
//					'address' => (string)$orderConsignee->address,
//					'province' => (string)$orderConsignee->province,
//					'city' => (string)$orderConsignee->city,
//					'region' => (string)$orderConsignee->district,
//					'userName' => (string)$orderConsignee->consignee,
//					'phone' => (string)$orderConsignee->mobile,
//					'deliveryTypeName' => (string)$warehouse->typename,
//					'orderCode' => (string)$orderConsignee->id,
//					'type' => $this->orderSourceMap[$userOrder->source],
//				],
//				'orderProducts' => [
//					'orderCode' => (string)$orderConsignee->id,
//					'num' => $userOrder->product_number,
//					'productCode' => (string)$product->ext_id,
//				],
//				'type' => $this->orderSourceMap[$userOrder->source],
//			]
		];
		$response = $this->sendRequest('post', 'userorder/api/orders/batch', $params);
		$data = $this->handleResponse($response);
		return [
			'third_order_sn' => '',
			'ext_order_sn' => '',
			'status' => PACKAGE_STATUS_SHIPPED,
			'sync_status' => USER_ORDER_SYNC_STATUS_SUCCESS,
		];
	}

	/**
	 * @inheritDoc
	 */
	public function requestOrderQuery($orderConsignee)
	{
//		$order = UserOrder::getById($orderConsignee->order_id);
		$params = [
			'userOrderCode' => (string)$orderConsignee->id,
		];
		$response = $this->sendRequest('post', 'userorder/api/orders/search/one', $params);
		$data = $this->handleResponse($response);

		if (empty($data['order']['status']) || $data['order']['status'] == 1) {
			return [
				'sync_query_status' => USER_ORDER_SYNC_QUERY_STATUS_PENDING,
//				'status' => PACKAGE_STATUS_PENDING,
			];
		}elseif($data['order']['status'] == 2){
			return [
				'express_company_name' => '',
				'express_no' => $data['order']['deliverCode'],
				'sync_query_status' => USER_ORDER_SYNC_QUERY_STATUS_SUCCESS,
				'status' => PACKAGE_STATUS_SHIPPED,
			];
		}else{
			return [
				'express_company_name' => '',
				'express_no' => $data['order']['deliverCode'],
				'sync_query_status' => USER_ORDER_SYNC_QUERY_STATUS_SUCCESS,
				'status' => PACKAGE_STATUS_CANCELED,
			];
		}
		
		
	}

	/**
	 * @inheritDoc
	 */
	protected function requestCancelOrder($orderConsignee)
	{
		// 不支持取消
		return false;
	}

	/**
	 * 发送请求
	 * @param string $method
	 * @param $uri
	 * @param array $data
	 * @return array|mixed|string
	 * @throws GuzzleException
	 * @author wzz
	 */
	private function sendRequest(string $method, $uri, array $data = [])
	{
		$client = $this->getHttpClient();
		$params = [
			'token' => [
				'appId' => $this->app_id,
				'appKey' => $this->app_key,
			]
		];
		$params = array_merge($params, $data);

		$this->requestParams = $params;
		$this->requestUrl = $this->baseUrl.$uri;
		return $client->request($method, $uri, [
			'json' => $params
		]);
	}
	

	/**
	 * 处理响应
	 * @param ResponseInterface $response
	 * @return mixed|string
	 * @throws OuterApiException
	 * @author wzz
	 */
	private function handleResponse(ResponseInterface $response)
	{
		$contents = $response->getBody()->getContents();
		$instance = new LoggerFactoryUtil(CaoshudaifaWarehouse::class);
		$instance->info("云礼品上游返回数据".json_encode([
				'params'=> $this->requestParams,
				'response'=> $contents,
			]));
		if (is_string($contents)) {
			$contents = json_decode($contents, true);
		}
		if (!isset($contents['code']) || $contents['code'] !== 0) {
			$class_name = CommonUtil::getClassName(get_class($this));
			$function = "";
			switch ($this->requestUrl){
				case $this->baseUrl . "product/api/all": $function="获取商品"; break;
				case $this->baseUrl . "userorder/api/orders/batch": $function="请求下单"; break;
				case $this->baseUrl . "userorder/api/orders/search/one": $function="获取订单"; break;
			}
			QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM")."云礼品仓库".json_encode([
					"功能"=>$function,
					'请求链接' => $this->requestUrl,
					'请求参数'=> $this->requestParams,
					'响应结果'=> $contents,
					'信息时间'=>date("Y-m-d H:i:s"),
					'damaijia_user_id'=>$this->damaijia_user_id,
					'商品id'=> $this->baseProductId,
					'仓库id'=>$this->baseExpressId,
					'仓源id'=>$this->baseWarehouseId,
				],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
			
			Log::debug("$class_name:",[
				'url' => $this->requestUrl,
				'params' => $this->requestParams,
				'response' => $contents,
			]);
			throw new OuterApiException(sprintf('%s error:%s', $class_name, json_encode($contents)));
		}
		return $contents;
	}
}
