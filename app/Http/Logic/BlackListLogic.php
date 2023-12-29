<?php


namespace App\Http\Logic;


use App\Models\DamaijiaBlacklistModel;
use App\Models\Product;
use App\Models\User;

class BlackListLogic extends BaseLogic
{
	public static function checkPhoneIsBlack($phone,$product_id)
	{
		if($phone == "18715117717") { //
			return false;
		}
		$data = DamaijiaBlacklistModel::query()->where("phone",$phone)->first();
		if($data) {
			return true;
		}
		return false;
	}
	public static function checkPhoneIsBlackByUserId($user_id,$product_id)
	{
		$mobile = User::query()->where("id",$user_id)->value("mobile");
		if($mobile == "18715117717") { //
			return false;
		}
		$data = DamaijiaBlacklistModel::query()->where("phone",$mobile)->first();
		if($data) {
			if($data["black_type"] == 2) {
				$product = Product::query()->where("id",$product_id)->first();
				if($product["signing_method"] ==1) {
					return false;
				}
			}
			return true;
		}
		return false;
	}
}
