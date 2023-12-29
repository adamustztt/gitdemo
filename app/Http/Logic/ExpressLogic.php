<?php


namespace App\Http\Logic;


use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Models\DamaijiaUserExpressPrice;
use App\Models\ExpressProductModel;
use App\Models\ExpressWarehouseModel;
use App\Models\Site;
use App\Models\User;

class ExpressLogic extends BaseLogic
{
	/**
	 * 获取用户运费
	 * @author ztt
	 * @param $user_id
	 * @param $express_id
	 * @param $warehouse_id
	 * @param $multiple
	 * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
	 * @throws \App\Exceptions\ApiException
	 */
	public static  function getUserExpressPriceLogic($user_id,$express_id)
	{
		$user = User::query()->where("id",$user_id)->first();
		if(empty($user)) {
			CommonUtil::throwException(ErrorEnum::USER_NOT_EXISTS);
		}
		$express = DamaijiaUserExpressPrice::query()->where(["user_id"=>$user_id,"express_id"=>$express_id])->first();
		if($user->site_id == 1) {
			$site_user_id = 0;
		} else {
			$site = Site::query()->where("id",$user->site_id)->first();
			$site_user_id = $site->user_id;
		}
		if($express) {
			$up_express_info = DamaijiaUserExpressPrice::query()->where(["user_id"=>$site_user_id,"express_id"=>$express_id])->first();
			if(empty($up_express_info)) {
				CommonUtil::throwException(ErrorEnum::ERROR_PRODUCT);
			}
			// 该用户的站长成本价
			$express->site_price = $up_express_info->site_price;
			return $express;
		}
		
		$express = DamaijiaUserExpressPrice::query()->where(["user_id"=>$site_user_id,"express_id"=>$express_id])->first();
		if(empty($express)) {
			CommonUtil::throwException(ErrorEnum::ERROR_PRODUCT);
		}
		return $express;
	}

//	/**
//	 * 获取站长运费成本价格
//	 * @author ztt
//	 * @param $productId
//	 * @param $userId 
//	 * @param $warehouseId 
//	 * @return mixed
//	 * @throws \App\Exceptions\ApiException
//	 */
//	public static function getSiteExpress($productId,$userId,$warehouseId)
//	{
//		//1.获取用户信息
//		$user = \App\Models\User::query()->find($userId);
//		//2.获取该商品的发货地ID
//		$expressProduct = ExpressProductModel::query()->where("product_id",$productId)->first();
//		if(!$expressProduct){
//			CommonUtil::throwException(ErrorEnum::ERROR_EXPRESS_SEND);
//		}
//		$express_id = $expressProduct["damaijia_express_id"];
//		return ExpressLogic::recursionGetUserExpressPriceLogic($userId,$express_id,$warehouseId,1);
//	}
	/**
	 * @author ztt
	 * @param $user_id
	 * 获取用户运费列表
	 * @param $express_id
	 * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
	 * @throws \App\Exceptions\ApiException
	 */
	public static  function listUserExpressPriceLogic($user_id)
	{
		$user = User::query()->where("id",$user_id)->first();
		if(empty($user)) {
			CommonUtil::throwException(ErrorEnum::USER_NOT_EXISTS);
		}
		$expressMap = DamaijiaUserExpressPrice::query()->where(["user_id"=>$user_id])->pluck("price","express_id")->toArray();
		if($user->site_id == 1) {
			$site_user_id = 0;
		} else {
			$site_user_id = Site::query()->where("id",$user->site_id)->value("user_id");
		}
		$upExpressMap= DamaijiaUserExpressPrice::query()->where(["user_id"=>$site_user_id])->pluck("price","express_id")->toArray();
		foreach ($upExpressMap  as $k=>$v) {
			if($expressMap[$k]) {
				$upExpressMap[$k] = $expressMap[$k];
			}
		}
		return $upExpressMap;
	}
}
