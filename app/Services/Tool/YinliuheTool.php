<?php


namespace App\Services\Tool;


use App\Enums\ErrorEnum;
use App\Exceptions\OuterApiException;
use App\Helper\CommonUtil;
use App\Http\Utils\LoggerFactoryUtil;
use App\Services\ToolService;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Tool\ShanTaoTool\HttpCurl;
use Tool\ShanTaoTool\QiWeiTool;

class YinliuheTool
{
	protected $baseUrl;
	protected $client_id;
	protected $client_secret;
	protected $requestTimeout = 10;
	protected $requestParams;
	protected $requestUrl;
	public function __construct()
	{
		$this->baseUrl = config("yinliuhe.domain");
		$this->client_id = config("yinliuhe.client_id");
		$this->client_secret = config("yinliuhe.client_secret");
	}
	private function login()
	{
		
		$params["client_id"] = $this->client_id;
		$params["client_secret"] = $this->client_secret;
		$data = HttpCurl::postCurl($this->baseUrl."/api/v1/outer/get_access_token",$params);
		if($data["code"] === 0) {
			return $data["data"] ?? [];
		}
		CommonUtil::throwException([100000,"请求错误"]);
	}
	public function requestTraffic($url,$method,$params)
	{
		$token = $this->login()["token"];
		$header =  ["Authorization" => "Bearer ".$token];
		if($method == "post") {
			$data = HttpCurl::postCurl($url,$params,$header);
		} else {
			$data = HttpCurl::getCurl($url,$params,$header);
		}
		dd($data);
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
		$instance->info($url."苍源返回数据" . json_encode([
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
		$headers = [
			"Content-Type"=>"application/json",
			"Accept"=>"application/json",
			"Authorization" => "Bearer ".$this->login()["token"]
		];
		//加入链路追踪参数
        if(isset($_GET["traceId"])){
            $headers["traceId"] = $_GET["traceId"];
        }
		$this->requestParams = $params;
		$this->requestUrl = $uri;
		$this->requestHeader = $headers;
		return $client->request($method, $uri, [
//			"form_params" => $params,
			"json"=>$params,
			"headers" => $headers,
		]);
	}

	private function handleResponse(ResponseInterface $response)
	{
		$contents = $response->getBody()->getContents();
		if (is_string($contents)) {
			$contents = json_decode($contents, true);
		}
		if($contents['code'] !== 0) {
			$policy['请求参数'] = $this->requestParams;
			$policy['请求地址'] = $this->requestUrl;
			$policy['返回结果'] = $contents;
			$policy = env("POLICE_FROM")." 用户使用工具生成订单失败".json_encode($policy,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
			QiWeiTool::sendMessageToBaoJing($policy);
			if($contents['code'] == 422) {
				CommonUtil::throwException([$contents['code'],$contents['msg']]);
			}
			CommonUtil::throwException(ErrorEnum::ERROR_EXT_UNKNOWN);
		}
		return $contents["data"];
		
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
