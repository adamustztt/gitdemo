<?php
/**
 * Created by PhpStorm.
 * User: wzz
 * Date: 2020/9/8
 * Time: 17:08
 */

namespace App\Services\Warehouses;


use App\Enums\ErrorEnum;
use App\Enums\WarehouseChannelEnum;
use App\Exceptions\ApiException;
use App\Exceptions\OuterApiException;
use App\Http\Utils\LoggerFactoryUtil;
use App\Models\OrderConsignee;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Warehouse;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;
use Tool\ShanTaoTool\QiWeiTool;

/**
 * 牧火礼品
 * Class MuhuoWarehouse
 * @package App\Services\Warehouses
 */
class MuhuoWarehouse extends AbstractWarehouse
{
	protected $baseUrl = 'http://www.muhuo8.com';

	protected $channel = WarehouseChannelEnum::MUHUO;

	private $username;
	private $password;

	private $orderSourceMap = [
		USER_ORDER_SOURCE_TAOBAO => 1,
		USER_ORDER_SOURCE_TMALL => 2,
	];

	public function __construct()
	{
		$this->username = config('warehouse.muhuo.username');
		$this->password = config('warehouse.muhuo.password');
	}


	public function requestWarehouse()
	{
		$list = [];
		$warehouse = Warehouse::firstByChannelAndExtId($this->channel, 0);
		if (!empty($warehouse)) {
			return [];
		}
		$list[] = [
			'ext_id' => 0,
			'name' => '韵达广州仓',
			'typename' => '韵达快递',
			'cost_price' => 0,
			'price' => 0,
			'address' => '',
			'channel_id' => $this->channel,
			'status' => WARE_HOUSE_STATUS_NORMAL,
		];
		return $list;
	}

	public function requestProduct($page = 1, $page_size = 100)
	{
		$data = $this->sendRequest('post', '/home/api/getGoods');
		$list = [];
		$warehouse = Warehouse::firstByChannelAndExtId($this->channel, 0);
		if (empty($warehouse)) {
			throw new OuterApiException('MuhuoWarehouse:仓库不存在');
		}
		foreach ($data['data'] as $index => $datum) {
			$list[] = [
					'name' => $datum['name'],
					'thumb' => $datum['thumb'],
					'up_cost_price' => $datum['price'] * 100,
					'weight' => $datum['weight'] * 100,
					'ext_id' => $datum['id'],
					'channel_id' => $this->channel,
					'status' => PRODUCT_STATUS_OFFLINE,
					'warehouse_id' => $warehouse->id,
					'stock' => 9999,
					'sales'=> rand(100,9999)
			];
		}
		return $list;
	}

	public function requestOrder($product, $userOrder, $orderConsignee)
	{
		$params = [
			'goodsId' => $product->ext_id,
			'goodsNum' => $userOrder->product_number,
			'name' => $orderConsignee->consignee,
			'mobile' => $orderConsignee->mobile,
			'province' => $orderConsignee->province,
			'city' => $orderConsignee->city,
			'area' => $orderConsignee->district,
			'address' => $orderConsignee->address,
			'type' => $this->orderSourceMap[$userOrder->source], // 1淘宝 2拼多多
		];
		$data = $this->sendRequest('post', '/home/api/createOrder', $params);
		return [
			'ext_order_sn' => $data['Ordernum'],
			'status' => PACKAGE_STATUS_SHIPPED,
			'sync_status' => USER_ORDER_SYNC_STATUS_SUCCESS,
		];
	}

	public function requestOrderQuery($orderConsignee)
	{
        //判断包裹是否存在上游订单号
        if(!$orderConsignee->ext_order_sn){
            throw new ApiException(ErrorEnum::ERROR_INVALID_ORDER);
        }
		$params = [
			'ordernum' => $orderConsignee->ext_order_sn,
		];
		$data = $this->sendRequest('post', '/home/api/orderstatus', $params);
		Log::info('MuhuoWarehouse:requestOrderQuery:',$data);
		if (empty($data['data']['status'])) {
			return [
				'sync_query_status' => USER_ORDER_SYNC_QUERY_STATUS_PENDING,
//				'status' => PACKAGE_STATUS_PENDING,
			];
		}
		return [
			'sync_query_status' => USER_ORDER_SYNC_QUERY_STATUS_SUCCESS,
//			'status' => PACKAGE_STATUS_SHIPPED,
			'express_company_name' => $data['data']['company'],
			'express_no' => $data['data']['courier'],
		];
	}


	/**
	 * 发送请求
	 * @param string $method
	 * @param $uri
	 * @param array $params
	 * @return array|mixed|string
	 * @throws GuzzleException
	 * @throws OuterApiException
	 * @author wzz
	 */
	private function sendRequest(string $method, $uri, array $params = [])
	{
		$client = $this->getHttpClient();
		$baseParams = [
			'username' => $this->username,
			'password' => $this->password,
		];
		$params = array_merge($baseParams, $params);
		if ($uri == '/home/api/createOrder') {
			$params['sin'] = $this->sign($params['goodsId'], $params['goodsNum']);
		}
		$this->requestParams = $params;
		$response = $client->request($method, $uri, [
			'json' => $params
		]);
		return $this->handleResponse($params,$response);
	}

	/**
	 * 处理响应
	 * @param ResponseInterface $response
	 * @return mixed|string
	 * @throws OuterApiException
	 * @author wzz
	 */
	private function handleResponse($params,ResponseInterface $response)
	{
		$contents = $response->getBody()->getContents();
		if (is_string($contents)) {
			$contents = json_decode($contents, true);
		}
		$instance = new LoggerFactoryUtil(CaoshudaifaWarehouse::class);
		$instance->info("牧火上游返回数据".json_encode([
				'params'=> $this->requestParams,
				'response'=> $contents,
			]));
		if (!isset($contents['code']) || $contents['code'] != 1) {

			QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM")."MuHuo仓库".json_encode([
					'url'=> $this->send_url,
					'params'=> $params,
					'response'=> $contents,
					'damaijia_user_id'=>$this->damaijia_user_id
				],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
			throw new OuterApiException('MuhuoWarehouse:' . $contents['msg'].' params:' . json_encode($params));
		}
		return $contents ?? [];
	}

	/**
	 * 签名
	 * @param $goodsId
	 * @param $goodsNum
	 * @return string
	 * @author wzz
	 */
	private function sign($goodsId, $goodsNum)
	{
		return strtolower(md5(md5($this->username . $this->password . $goodsId . $goodsNum)));
	}


	protected function requestCancelOrder($orderConsignee)
	{
		$params = [
			'ordernum' => $orderConsignee->ext_order_sn,
		];
		$data = $this->sendRequest('post', '/home/api/refund', $params);
		if (empty($data['data']['status'])) {
			return false;
		}
		return true;
	}
}
