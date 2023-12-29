<?php


namespace App\Services;


use App\Enums\ErrorEnum;
use App\Exceptions\OuterApiException;
use App\Helper\CommonUtil;
use App\Http\Utils\LoggerFactoryUtil;
use App\Services\Warehouses\FabWarehouse;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class ToolService
{
	protected $baseUrl = null;
	protected $account = null;
	protected $accessKey = null;
	protected  $access_token = null;
	/**
	 * @var float 请求超时时间
	 */
	protected $requestTimeout = 10;
	public function __construct()
	{
		$this->baseUrl = config("tool.baseUrl");
		$this->account = config("tool.account");
		$this->accessKey = config("tool.accessKey");
		$this->access_token = $this->login();
		
	}
	/**
	 * @author ztt
	 * 登录
	 * @return array|mixed
	 * @throws OuterApiException
	 */
	public function login()
	{
		return env("TOOL_TOKEN");
		$params = [
			"grant_type"=>"client_credentials",
			"client_id" => $this->account,
			"client_secret" => $this->accessKey,
		];
		$response = $this->sendRequest("post", "/oauth/token", $params);
		$result = $this->handleResponse($response);
		return $result["access_token"];
	}

	/**
	 * @author ztt
	 * 请求工具接口
	 * @param $method
	 * @param $params
	 * @param $url
	 * @return mixed
	 * @throws \App\Exceptions\ApiException
	 */
	public function requestTool($method,$params,$url)
	{
		$response = $this->sendRequest($method, $url, $params);
		$result = $this->handleResponse($response);
		$instance = new LoggerFactoryUtil(ToolService::class);
		$instance->info($url."上游返回数据" . json_encode([
				"params" => $params,
				"url"=>$url,
				"response" => $result,
			]));
		return $result;
	}
	private function sendRequest(string $method, $uri, array $data = [])
	{
		$client = $this->getHttpClient();
		$params = $data;
		$uri = $this->baseUrl . $uri;
		$this->requestParams = $params;
		return $client->request($method, $uri, [
			"form_params" => $params,
			"json"=>$params,
			"headers" => [
				"Authorization" => "Bearer ".$this->access_token
			]
		]);
	}

	private function handleResponse(ResponseInterface $response)
	{
		$contents = $response->getBody()->getContents();
		if (is_string($contents)) {
			$contents = json_decode($contents, true);
		}
		return $contents;
	}
	public function getHttpClient(array $config = [])
	{
		$arr = [
			'base_uri' => $this->baseUrl,
			'http_errors' => false, // 禁用HTTP协议抛出的异常(如 4xx 和 5xx 响应)
			'timeout' => $this->requestTimeout, // 请求超时的秒数。使用 0 无限期的等待(默认行为)。
		];
		return new Client(array_merge($arr, $config));
	}
}
