<?php


namespace App\Http\Logic;


use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Models\CustomWarehouseExpressModel;
use App\Models\CustomWarehouseModel;
use App\Models\DamaijiaUserExpressPrice;
use App\Models\ExpressModel;
use App\Models\ExpressWarehouseModel;
use App\Models\Site;
use App\Models\UserLevelModel;

class UserLevelLogic extends BaseLogic
{
	public static function listUserLevel($site_id,$user_id)
	{
		$data = UserLevelModel::query()
			->where(["site_id"=>$site_id,"status"=>1])
			->orderBy("preferential_amount","asc")
			->get();
		$list = [];
		$express_express = DamaijiaUserExpressPrice::query()->where("user_id",$user_id)->orderBy("price")->value("price");
		if(!$express_express) {
			$user_id = 0;
			if($site_id!=1) {
				$user_id = Site::query()->where("id",$site_id)->value("user_id");
			}
			$express_express = DamaijiaUserExpressPrice::query()->where("user_id",$user_id)->orderBy("price")->value("price");
		}
		if(empty($express_express)) {
			CommonUtil::throwException(ErrorEnum::ERROR_SITE_USER); 
		}
		$imgMap=["https://pic.rmb.bdstatic.com/bjh/dcce8f4027f19f83d17493bfd6444c03.png",
			"https://pic.rmb.bdstatic.com/bjh/dcce8f4027f19f83d17493bfd6444c03.png",
			"https://pic.rmb.bdstatic.com/bjh/dcce8f4027f19f83d17493bfd6444c03.png",
			"https://pic.rmb.bdstatic.com/bjh/dcce8f4027f19f83d17493bfd6444c03.png",
			"https://pic.rmb.bdstatic.com/bjh/dcce8f4027f19f83d17493bfd6444c03.png",
			"https://pic.rmb.bdstatic.com/bjh/dcce8f4027f19f83d17493bfd6444c03.png",
			"https://pic.rmb.bdstatic.com/bjh/dcce8f4027f19f83d17493bfd6444c03.png"];
		$toolNameMap = ["默认价格","享受优惠","享受较大优惠","享受更大优惠","享受超级优惠","享受最多优惠"];
		foreach ($data as $k=>$v)
		{
			$list[$k]["img"] = $v["img"];
			$list[$k]["level_name"] = $v["level_name"];
			$list[$k]["level_condition"] = [$v["invite_count"],$v["single_recharge"]/100];
			$list[$k]["preferential_amount"] = $express_express-$v["preferential_amount"];
			$list[$k]["tool_preferential_img"] = $v["img"];
			$list[$k]["tool_preferential_name"] = $toolNameMap[$k];
			$list[$k]["customer_service"] = true;
			$list[$k]["tutor_service"] = ($k >0 ? true : false);
			$list[$k]["complaint_service"] = ($k > 0 ? true : false);
			$list[$k]["new_product_service"] = ($k > 1 ? true : false);
		}
		return $list;
	}
	public static function listUserLevelExpress($site_id,$user_id)
	{
		$data = UserLevelModel::query()
			->where(["site_id"=>$site_id,"status"=>1])
			->orderBy("preferential_amount","asc")
			->get();
		$expressMap = self::listUserExpress($site_id,$user_id);
		$resultMap = [];
		$lineOne1 = ["快递","仓库"];
		$lineOne2 = [];
		$lineTwo2 = [];
		foreach ($data as $k=>$v) {
			$lineOne2[] = $v["level_name"];
			if($v["invite_count"] == 0 && $v["single_recharge"] == 0) {
				$lineTwo2[] = "注册用户";
			} else {
				$lineTwo2[] = "直属粉丝>=".$v["invite_count"]."人</br>"."单次充值>=".$v["single_recharge"]/100 . "元";
			}
		}
		$lineOne = array_merge($lineOne1,$lineOne2);
		$lineTwo1 = ["等级要求",""];
		$lineTwo = array_merge($lineTwo1,$lineTwo2);
		$lineMap = [];
		$resultMap[]=$lineOne;
		$resultMap[]=$lineTwo;
		foreach ($expressMap as $k=>$map) {
			foreach ($map["expressMap"] as $kk=>$express) {
				$lineMapTemp = [$map["custom_warehouse_name"],$express["express_name"]];
				foreach ($data as $dataK=>$dataV) {
					$lineMapTemp[] = ($express["express_price"] - $dataV["preferential_amount"])/100 . "元";
				}
				$resultMap[] = $lineMapTemp;
			}
		}
		return $resultMap;
	}
	public static function listUserExpress($site_id,$user_id)
	{
		$datas = CustomWarehouseModel::query()->where("status",1)->get()->toArray();
		$listExpress = ExpressModel::query()->pluck("express_name","id")->toArray();
		$userExpressMap = DamaijiaUserExpressPrice::query()->where("user_id",$user_id)->pluck("price","express_id")->toArray();
		$siteUserId = 0;
		if($site_id!=1) {
			$siteUserId = Site::query()->where("id",$site_id)->value("user_id");
		}
		
		$siteExpressMap = DamaijiaUserExpressPrice::query()->where("user_id",$siteUserId)->pluck("price","express_id")->toArray();
		$CustomWarehouseExpressRelations = CustomWarehouseExpressModel::query()->pluck("custom_warehouse_id","express_id")->toArray();
		$expressSendIdsMap = [];
		foreach ($CustomWarehouseExpressRelations as $express_id=>$custom_warehouse_id){
			$expressSendIdsMap[$custom_warehouse_id][] = $express_id;
		}
		foreach ($datas as $k=>$v) {
			$expressMap=[];
			$expresIdsMap = $expressSendIdsMap[$v["id"]];
			foreach ($expresIdsMap as $kk=>$vv) {
				$expressMap[$kk]["id"] = $vv;
				$expressMap[$kk]["express_name"] = $listExpress[$vv];
				$expressMap[$kk]["express_price"] = isset($userExpressMap[$vv]) ? $userExpressMap[$vv] : $siteExpressMap[$vv];
			}
			$datas[$k]["expressMap"] = $expressMap;
		}
		return $datas;
	}
}
