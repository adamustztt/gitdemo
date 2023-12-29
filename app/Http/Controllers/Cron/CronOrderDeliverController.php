<?php


namespace App\Http\Controllers\Cron;


use App\Http\Controllers\BaseController;
use App\Http\Logic\Cron\CronOrderDeliverControllerLogic;
use App\Models\OrderConsignee;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class CronOrderDeliverController extends BaseController
{
	/**
	 * 定时任务请求拼多多打单更新物流信息
	 */
	public function cronOrderDeliver()
	{
		/**
		 * @var \Redis $redis
		 */
		$redis = app("redis");
		$flag = $redis->set("cronOrderDeliver",1,60);
		if($flag){
			$client = new Client();
			$domain = env("DAMAIJIA_DOMAIN");
			OrderConsignee::query()
				->where("is_deliver",1)
				->where("deliver_status",0)
				->where("status","s")
				->where("express_no","!=","")
				->chunk(100, function ($orderConsigneeArr)use ($client,$domain){
					foreach ($orderConsigneeArr as $k=>$v) {
						$promise = $client->requestAsync('GET', $domain.'/vv/cronOrderDeliverByPackageId',[
							"body"=>json_encode(["package_id"=>$v["id"]]),
							"headers"=>[
								"Content-Type"=>"application/json"
							]
						])->then(function ($response) {
							echo 'I completed! ' . $response->getBody();
						});
					}
					$promise->wait();
				});
			echo "success";
			$redis->del("cronOrderDeliver");
		}
		
	}
	/*
	 * 通知发货
	 */
	public function cronOrderDeliverByPackageId(Request $request)
	{
		$this->validate($request, [
			'package_id' => 'required',
		]);
		$params = app("request")->all();
		$package_id = $params["package_id"];
		CronOrderDeliverControllerLogic::orderDeliver($package_id); 
	}
	/*
	 * 拉取pdd订单
	 */
	public function cronRequestPddShopOrder()
	{
		return CronOrderDeliverControllerLogic::cronRequestPddShopOrder();
	}
}
