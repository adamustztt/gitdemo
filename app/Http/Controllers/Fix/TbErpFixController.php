<?php


namespace App\Http\Controllers\Fix;


use App\Models\SettingApiModel;
use App\Models\SettingApiUserModel;

class TbErpFixController
{
	public static function fixTbErp()
	{
		$apis = SettingApiModel::query()->where("type", "tberp")->get();
		$uids = SettingApiUserModel::query()->pluck("user_id")->toArray();
		foreach ($uids as $uid) {
			foreach ($apis as $api) {
				$f = SettingApiUserModel::query()->where("user_id",$uid)->where("code",$api["code"])->first();
				if(!$f) {
					SettingApiUserModel::query()->insert([
						"user_id"=>$uid,
						"code"=>$api["code"],
						"service"=>$api["service"],
						"cost_point"=>$api["cost_point"],
						"profit"=>$api["profit"],
						"api_profit"=>$api["api_profit"],
						"site_profit"=>$api["site_profit"],
						"status"=>$api["status"],
						"type"=>$api["type"],
						"create_time"=>date("Y-m-d H:i:s"),
						"update_time"=>date("Y-m-d H:i:s"),
					]);
				}
			}
		}
	}
}
