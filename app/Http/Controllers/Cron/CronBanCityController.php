<?php


namespace App\Http\Controllers\Cron;


use App\Http\Controllers\BaseController;
use App\Http\Utils\LoggerFactoryUtil;
use App\Models\BanCityModel;
use App\Models\BanCityPushDownModel;
use App\Models\ExpressModel;
use App\Models\OrderConsigneePushDown;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Tool\ShanTaoTool\HttpCurl;

class CronBanCityController extends BaseController
{

	/**
	 * 定时任务发送禁发地区
	 */
	public function cronBanCity()
	{
		/**
		 * @var \Redis $redis
		 */
		$redis = app("redis");
		$flag = $redis->set("cronBanCity", 1, 60);
		if ($flag) {
			$client = new Client();
			$domain = env("DAMAIJIA_DOMAIN");
			BanCityPushDownModel::query()
				->whereIn("push_status", [0, 2])
				->where("push_count", "<", 3)
				->chunk(100, function ($push_data) use ($client, $domain) {
					foreach ($push_data as $k => $v) {
						$promise = $client->requestAsync('POST', $domain . '/vv/cronBanCityByPushId', [
							"body" => json_encode(["push_id" => $v["id"]]),
							"headers" => [
								"Content-Type" => "application/json"
							]
						])->then(function ($response) {
							echo 'I completed! ' . $response->getBody();
						});
					}
					$promise->wait();
				});
			echo "success";
			$redis->del("cronBanCity");
		}

	}

	/*
	 * 发送推送
	 */
	public function cronBanCityByPushId(Request $request)
	{
		$this->validate($request, [
			'push_id' => 'required',
		]);
		$params = app("request")->all();
		$push_id = $params["push_id"];

		$pushData = BanCityPushDownModel::getById($push_id);
		$push_url = $pushData["push_url"];
		$banCity = BanCityModel::query()->where(["ban_type" => 3, "express_id" => $pushData->express_id])->first();
		if (empty($banCity)) {
			$pushParam [] = [];
		} else {
			$express_name = ExpressModel::query()
				->where("id", $banCity["express_id"])
				->value("express_name");
			$pushParam["express_id"] = $banCity["express_id"];
			$pushParam["express_name"] = $express_name;
			$pushParam["city_names"] = $banCity["city_names"];
			$pushParam["open_time"] = $banCity["open_time"];
			$pushParam["off_time"] = $banCity["off_time"];
			$pushParam["remark"] = $banCity["remark"];
		}
		$response = HttpCurl::postCurl($push_url, $pushParam, [], false);
		if ($response && isset($response["code"]) && $response["code"] == 0) {
			BanCityPushDownModel::updateById($push_id, ["push_status" => 1]);
		} else {
			BanCityPushDownModel::updateById($push_id, ["push_status" => 2, "push_count" => $pushData["push_count"] + 1]); //推送失败
		}
		$log = new LoggerFactoryUtil(CronBanCityController::class);
		$log->info("推送返回结果" . json_encode($response));
		return $this->responseJson($response);
	}
}
