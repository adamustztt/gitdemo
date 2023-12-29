<?php


namespace App\Http\Controllers\Cron;


use App\Http\Controllers\BaseController;
use App\Models\UserShopModel;
use Tool\ShanTaoTool\HttpCurl;

class PddErpController extends BaseController
{
	public function getPddUserShop()
	{
		$params = app("request")->all();
		$create_time = date("Y-m-d");
		if(isset($params["create_time"])) {
			$create_time = $params["create_time"];
		}
		$url = env("PDD_GET_USER_SHOP");
		$uid = env("AT_VTOOL_PROJECT_USER_ID");
		$project_id = env("PROJECT_ID");
		$params=[
			"project_id"=>$project_id,
			"uid"=>md5($uid),
			"create_time"=>$create_time
		];
		$req= HttpCurl::postCurl($url,$params);
		if(isset($req["data"]) && !empty($req["data"])) {
			$data = $req["data"];
			foreach ($data as $k=>$v) {
				$map["user_id"] = $v["child_id"];
				$map["shop_id"] = $v["shop_id"];
				$map["shop_type"] = "pdd";
				$find = UserShopModel::query()->where($map)->first();
				if(!$find) {
					UserShopModel::create($map);
				}
			}
		}
		return $this->responseJson($req);
		
	}
}
