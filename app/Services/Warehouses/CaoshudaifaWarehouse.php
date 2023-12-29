<?php
/**
 * Created by PhpStorm.
 * User: wzz
 * Date: 2020/10/20
 * Time: 10:54
 */

namespace App\Services\Warehouses;


use App\Enums\ErrorEnum;
use App\Enums\WarehouseChannelEnum;
use App\Exceptions\ApiException;
use App\Exceptions\OuterApiException;
use App\Helper\CommonUtil;
use App\Http\Utils\LoggerFactoryUtil;
use App\Models\Warehouse;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;
use Tool\ShanTaoTool\QiWeiTool;
use function GuzzleHttp\Psr7\build_query;

/**
 * 
 * Class CaoshudaifaWarehouse
 * @package App\Services\Warehouses
 */
class CaoshudaifaWarehouse extends AbstractWarehouse
{
	protected $baseUrl = 'http://love.jswoniu.com/open/';

	protected $channel = WarehouseChannelEnum::CAOSHUDAIFA;
	
	protected $requestTimeout = 20;
	
	/**
	 * @var string
	 */
	private $app_id;
	
	/**
	 * @var string
	 */
	private $secret;

	/**
	 * 需要的仓库id组
	 * @var int[] 
	 */
	private $widArr = [
		80,// 邮政
		145,// 圆通 浙江九
		146,// 圆通 浙江九
		147,// 圆通 广州
		148,// 圆通 泉州
	];
	private $widArr1 = [
//		21, // 义乌申通仓 拼多多
//		22, // 广州圆通礼品主仓  淘宝
//		23, // 广州圆通礼品主仓  拼多多
		46, // 圆通淘宝礼品 福建发出
//		47, // 圆通拼多多礼品 福建发出
		52, // 邮政淘宝礼品 梅州
//		53, // 邮政拼多多礼品 梅州
		116, // 泉州自提件仓 淘宝 圆通
//		117, // 泉州自提件仓 拼多多 圆通
//		118, // 杭州自提件仓 拼多多 圆通
//		120, // 杭州自提件仓 淘宝 圆通
//		125, // 杭州韵达仓 淘宝 韵达
//		126, // 杭州韵达仓 拼多多 韵达
//		134, // 韵达淘宝礼品仓 杭州
//		135, // 韵达拼多多礼品仓 杭州
	];
	private $orderSourceMap = [
		USER_ORDER_SOURCE_TAOBAO => 0,
		USER_ORDER_SOURCE_TMALL => 0,
		USER_ORDER_SOURCE_PDD => 1,
		USER_ORDER_SOURCE_JD => 2,
	];
	/**
	 * @var array
	 */
	protected $requestParams;
	/**
	 * @var string
	 */
	protected $send_url;
	

	public function __construct()
	{
		$this->app_id = config('warehouse.caoshudaifa.app_id');
		$this->secret = config('warehouse.caoshudaifa.secret');
	}

	/**
     * @inheritDoc
     */
    public function requestWarehouse()
    {
		$list = [];
		$response = $this->sendRequest('post', 'getExpressList');
		$expressList = $this->handleResponse($response);
		foreach ($expressList as $express) {
			if (!in_array($express['wid'], $this->widArr)){
				continue;
			}
			$ext_id = $express['wid'];
//			$warehouse = Warehouse::firstByChannelAndExtId($this->channel, $ext_id);
			
			$list[] = [
				'ext_id' => $ext_id,
				'ext_express_id' => $express['id'],
				'name' => $express['name'],
				'typename' => $express['name'],
				'cost_price' => $express['price'] * 100,
				'price' => $express['price'] * 100 + 10,
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
		$response = $this->sendRequest('post', 'getGoodsList');
		$productList = $this->handleResponse($response);
		$list = [];
		foreach ($productList as $index => $datum) {
			if (!in_array($datum['wid'], $this->widArr)){
				continue;
			}
			$warehouseMap = Warehouse::query()->where(["channel_id"=>$this->channel,"ext_id"=>$datum['wid']])->get();
			if (!$warehouseMap->count()) {
				throw new OuterApiException('仓库不存在');
			}
			foreach ($warehouseMap as $key=>$warehouse) {
				$list[] = [
					'name' => $datum['name'],
					'thumb' => $datum['img'],
					'up_cost_price' => $datum['price'] * 100,
					'weight' => 0,
					'ext_id' => $datum['id'],
					'channel_id' => $this->channel,
					'status' => PRODUCT_STATUS_OFFLINE,
					'warehouse_id' => $warehouse->id,
					'stock' => 9999,
					'sales'=> rand(100,9999)
				];
			}
		}
		return $list;
    }

    /**
     * @inheritDoc
     */
    public function requestOrder($product, $userOrder, $orderConsignee)
    {
		$warehouse = Warehouse::getById($product->warehouse_id);
		$params = [];
		$params[] = [
			'platform_type' => $this->orderSourceMap[$userOrder->source],
			'platform_no' =>$userOrder->order_sn."_".$orderConsignee->id,
			'goods_id' => $product->ext_id,
			'num' => $userOrder->product_number,
			'express_id' => $warehouse->ext_express_id,
			'ship_type' => 0,
			'custom_weight' => 0.5,
			'shipper_name' => '发货人',
			'shipper_mobile' => '15888888888',
			'consignee' => $orderConsignee->consignee,
			'mobile' => $orderConsignee->mobile,
			'province' => $orderConsignee->province,
			'city' => $orderConsignee->city,
			'district' => $orderConsignee->district,
			'address' => $orderConsignee->address,
		];
		$response = $this->sendRequest('post', 'uniSubmitOrder', $params);
		$data = $this->handleResponse($response);
		return [
			'third_order_sn' => $data['batch_no'],
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
        //判断包裹是否存在上游订单号
        if(!$orderConsignee->third_order_sn){
            throw new ApiException(ErrorEnum::ERROR_INVALID_ORDER);
        }
		$params = [
			'batch_no' => $orderConsignee->third_order_sn,
		];
		$response = $this->sendRequest('post', 'searchOrderViaBatchNo', $params);
		$data = $this->handleResponse($response);

		if (empty($data[0]) || empty($data[0]['express_no'])) {
			return [
				'sync_query_status' => USER_ORDER_SYNC_QUERY_STATUS_PENDING,
//				'status' => PACKAGE_STATUS_PENDING,
			];
		}
		return [
			'express_company_name' => '',
			'express_no' => $data[0]['express_no'],
			'sync_query_status' => USER_ORDER_SYNC_QUERY_STATUS_SUCCESS,
//			'status' => PACKAGE_STATUS_SHIPPED,
		];
    }

    /**
     * @inheritDoc
	 * 根据老板要求查询订单是取消（最终才可以取消） 1先查询订单 2在取消订单 3在查询订单
     */
    protected function requestCancelOrder($orderConsignee)
    {
		$log = new LoggerFactoryUtil(CaoshudaifaWarehouse::class);
		$log->info("开始取消");
    	//1先查询上游订单状态是否已经取消
    	$up_order_status = $this->cancelQueryOrder($orderConsignee); // "refund_status": 0 # 退款状态：0 未退款； 1 退款中；2 退款成功； 3 拒绝退款
    	if($up_order_status == 2) {
			return true;
		}
    	if(empty( $orderConsignee->ext_order_sn)) {
			throw new ApiException(ErrorEnum::ERROR_CANCEL_CAOSUDAIFA_ORDER);
		}
    	//2 如果未取消 再掉上游取消接口
		$params = [
			'express_no' => $orderConsignee->ext_order_sn,
		];
		$response = $this->sendRequest('post', 'orderRefundForDev', $params);
		$contents = json_decode($response->getBody()->getContents(),true);
		$data = $this->handleResponse($response);
		// 3 在查询订单是否取消
		$up_order_status = $this->cancelQueryOrder($orderConsignee); // "refund_status": 0 # 退款状态：0 未退款； 1 退款中；2 退款成功； 3 拒绝退款
		if($up_order_status == 2) {
			return true;
		}
		// 查询订单状态不对  就不能取消
		return false;
		
    }

	/**
	 * @author ztt
	 * @param $orderConsignee
	 * 取消订单查询
	 * @return array|mixed|string
	 * @throws ApiException
	 * @throws GuzzleException
	 * @throws OuterApiException
	 */
	private function cancelQueryOrder($orderConsignee) {
		//判断包裹是否存在上游订单号
		if(!$orderConsignee->third_order_sn){
			throw new ApiException(ErrorEnum::ERROR_INVALID_ORDER);
		}
		$params = [
			'batch_no' => $orderConsignee->third_order_sn,
		];
		$response = $this->sendRequest('post', 'searchOrderViaBatchNo', $params);
		$data = $this->handleResponse($response);
		$log = new LoggerFactoryUtil(CaoshudaifaWarehouse::class);
		$log->info("查询结果",json_encode($data));
		return $data[0]['refund_status']; // "refund_status": 0 # 退款状态：0 未退款； 1 退款中；2 退款成功； 3 拒绝退款
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
			'timestamps' => time(),
			'nocestr' => CommonUtil::getRandStr(12),
			'appid' => $this->app_id,
		];
		if (!empty($data)){
			$params['data'] = $data;
		}
		$params['sign'] = $this->sign($params);

		$this->send_url = $this->baseUrl.$uri;
		$this->requestParams = $params;
		return $client->request($method, $uri, [
			'json' => $params
		]);
	}

	/**
	 * 签名
	 * @author wzz
	 * @param array $params
	 * @return string
	 */
	private function sign(array $params)
	{
		if (isset($params['data'])){
			$params['data'] = json_encode($params['data']);
		}
		ksort($params);
		$str = build_query($params) . $this->secret;
		return strtoupper(md5($str));
		
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
		if (is_string($contents)) {
			$contents = json_decode($contents, true);
		}
		$instance = new LoggerFactoryUtil(CaoshudaifaWarehouse::class);
		$instance->info("超速代发上游返回数据".json_encode([
				'url'=> $this->send_url,
				'params'=> $this->requestParams,
				'response'=> $contents,
			]));
		if ($contents['code'] != 2000) {
			QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM")."caosudaifa仓库".json_encode([
					'url'=> $this->send_url,
					'params'=> $this->requestParams,
					'response'=> $contents,
					"damaijia_user_id"=>$this->damaijia_user_id
				], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
			CommonUtil::throwException(ErrorEnum::INTERNAL_ERROR);
			
			
			$class_name = CommonUtil::getClassName(get_class($this));
			
			throw new OuterApiException(sprintf('%s error:%s',$class_name,json_encode([
				'url'=> $this->send_url,
				'params'=> $this->requestParams,
				'response'=> $contents,
			])));
		}
		return $contents['data'] ?? [];
	}
}
