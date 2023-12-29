<?php


namespace App\Http\Validate;


use Illuminate\Support\Facades\Validator;

class DamaijiaUserDeliverShopControllerValidate extends BaseValidate
{
	/**
	 * 店铺保存数据校验
	 * @param $data
	 */
	public function setUserShop($data)
	{
		$validate = Validator::make($data,
			[
				"id"=>"required|int",
				"shop_status"=>"required|int",
				"is_tag"=>"required|int",
				"tag_color"=>"",
				"tag_remark"=>""
			]
		);
		if($validate->fails()){
			//验证错误
			$this->setError($validate->errors()->first());
			return false;
		}
		return true;
	}
	public function authorizationShop($data)
	{
		$validate = Validator::make($data,
			[
				"id"=>"required|int",
			]
		);
		if($validate->fails()){
			//验证错误
			$this->setError($validate->errors()->first());
			return false;
		}
		return true;
	}
	public function getUserShopByProductId($data)
	{
		$validate = Validator::make($data,
			[
				"product_id"=>"required|int",
			]
		);
		if($validate->fails()){
			//验证错误
			$this->setError($validate->errors()->first());
			return false;
		}
		return true;
	}
}
