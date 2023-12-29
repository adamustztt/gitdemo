<?php
/**
 * Created by PhpStorm.
 * User: wzz
 * Date: 2020/9/8
 * Time: 17:08
 */

namespace App\Services\Warehouses;


use App\Enums\WarehouseChannelEnum;
use App\Exceptions\OuterApiException;
use App\Http\Utils\LoggerFactoryUtil;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Tool\ShanTaoTool\QiWeiTool;

/**
 * 小马
 * Class XiaomaWarehouse
 * @package App\Services\Warehouses
 */
class XiaomaWarehouse extends AbstractWarehouse
{
	protected $baseUrl = 'http://www.xmdaifa.com';

	protected $channel = WarehouseChannelEnum::XIAOMA;
	private $token;
	private $demos;
	private $tel;

	public function __construct()
	{
		$this->token = config('warehouse.xiaoma.token');
		$this->demos = config('warehouse.xiaoma.domain');
		$this->tel = config('warehouse.xiaoma.tel');
	}


	public function requestWarehouse()
	{
		$params = [];
		$data = $this->sendRequest('/openapi/openapi/warehouse_list', $params);
		foreach ($data['warehouselist'] as $index => $warehouseData) {
			$list[] = [
				'ext_id' => $warehouseData['warehouseid'],
				'name' => $warehouseData['warehousename'],
				'typename' => '',
				'cost_price' => 0,
				'price' => 0,
				'address' => '',
				'channel_id' => $this->channel,
				'status' => WARE_HOUSE_STATUS_NORMAL,
			];
		}

		return $list;
	}

	public function requestProduct($page = 1, $page_size = 100)
	{
		$page = $page - 1;
		$params = [
			'page' => $page,
			'page_size' => $page_size,
		];
		$data = $this->sendRequest('/openapi/openapi/gift_list', $params);
		$this->hasNextPage = true;
	}


	/**
	 * 发送请求
	 * @param $uri
	 * @param array $params
	 * @return array|mixed|string
	 * @throws GuzzleException
	 * @throws OuterApiException
	 * @author wzz
	 */
	private function sendRequest($uri, array $params = [])
	{
		$method = 'post';
		$client = $this->getHttpClient();
		$baseParams = [
			'tel' => $this->tel,
			'demos' => $this->demos,
		];
		$allParams = array_merge($baseParams, $params);
		$allParams['sign'] = $this->sign($allParams);
		$response = $client->request($method, $uri, [
			'json' => $allParams,
		]);
		return $this->handleResponse($response);
	}

	/**
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
		$instance->info("小码上游返回数据".json_encode([
				'params'=> $this->requestParams,
				'response'=> $contents,
			]));
		if (!isset($contents['code']) || $contents['code'] != 0) {
			QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM")."XiaomaWarehouse仓库".json_encode([
					'params'=> $this->requestParams,
					'response'=> $contents,
				],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
			throw new OuterApiException('XiaomaWarehouse:' . $contents['message'] );
		}
		return $contents['data'] ?? [];

	}

	public function requestOrderQuery($orderConsignee)
	{

	}

	protected function requestCancelOrder($orderConsignee)
	{

	}

	public function requestOrder($product, $userOrder, $orderConsignee)
	{

	}

	public function sign($params)
	{
		ksort($params);
		$str = $this->token;
		foreach ($params as $key => $value) {
			$str .= $key . $value;
		}
		$str .= $this->token;
		$str = md5($str);
		$str = bin2hex($str);
		return $str;
	}
}
