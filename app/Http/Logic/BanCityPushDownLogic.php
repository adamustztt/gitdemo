<?php


namespace App\Http\Logic;


use App\Models\BanCityPushDownModel;
use App\Models\User;

class BanCityPushDownLogic extends BaseLogic
{
	public static function addBanCityPush($express_id)
	{
		$user = User::query()->where("is_api", 1)
			->whereNotNull("ban_city_notify_url")->pluck("ban_city_notify_url")->toArray();
		foreach ($user as $k => $v) {
			BanCityPushDownModel::create(["express_id" => $express_id, "push_url" => $v]);
		}
	}
}
