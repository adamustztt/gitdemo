<?php


namespace App\Http\Logic;


use App\Models\UserShopModel;

class UserShopCallbackLogic extends BaseLogic
{
	public static function getJdAuthLink($user_id)
	{
		$url="https://oauth.jd.com/oauth/authorize?response_type=code&client_id=0C099E6CA84DC8A68E5CEDF894B0ABD1&redirect_uri=http%3A%2F%2Fwww.xedenstore.com%2Fjd%2Fcallback&scope=read&state=";
		$state = [
			"token"=>$user_id,
			"call_back"=>env("DAMAIJIA_DOMAIN")."/jdCallbackShopInfo",
			"redirect"=>env("DAMAIJIA_DOMAIN")
		];
		$state = base64_encode(json_encode($state));
		return $url.$state;
			
	}
	// 京东店铺授权回掉
	public static function jdCallbackShopInfo()
	{
		$params = app("request")->all();
		$map["access_token"] = $params["accessToken"];
		$map["expiration_time"] = date("Y-m-d H:i:s",$params["expiresIn"]);
		$map["user_id"] = $params["state"];
		$map["shop_id"] = $params["venderId"];
		$map["shop_name"] = $params["shopName"];
		
		$map["shop_type"] = "jd";
		$map["authorization_time"] = date("Y-m-d H:i:s");
		$map["site_id"] = 1;
		$map["callback_params"] = json_encode($params);
		$where["shop_type"] = "jd";
		$where["user_id"] = $params["state"];
		$where["shop_id"] = $params["venderId"];
		$where["is_delete"] = 0;
		$find = UserShopModel::query()->where($where)->first();
		if($find) {
			UserShopModel::query()->where($where)->update($map);
		} else {
			UserShopModel::query()->create($map);
		}
		return "SUCCESS";
	}
}
