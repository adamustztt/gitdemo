<?php


namespace App\Http\Utils;


use App\Models\AddressCity;
use App\Models\AddressProvince;
use App\Models\BanCityModel;
use App\Models\ExpressProductModel;
use App\Models\OrderConsignee;

class PlatformOrderUtil extends BaseUtil
{
	public static function checkPlatformOrder($data,$product_id)
	{
		foreach ($data as $k=>$v) {
			$message = [];
			$mark = true;
			$c_province  = mb_substr($v["province"],0,2,"utf-8");
			$province = AddressProvince::query()->where("name","like",'%'.$c_province.'%')->first();
			$express_id = ExpressProductModel::query()->where("product_id",$product_id)->value("damaijia_express_id");
			if(!$province) {
				$message["province"] = "省错误";
				$mark = false;
			} else {
				if($province["status"] == 2) {
					$message["province"] = $v["province"]."不支持发货";
					$mark = false;
				} else {
					$ban_address=BanCityModel::getBanAddressExpress($express_id,1,$province->name);
					if($ban_address) {
						$message["province"] = $v["province"]."已设置为禁发地";
						$mark = false;
					}
				}
			}

			if(empty($v["city"])) {
				$message["city"] = "城市不能为空";
				$mark = false;
			} else {
				if($v["city"] != "省直辖县") {
					$c_city = mb_substr($v["city"],0,2,"utf-8");
					$city = AddressCity::query()->where(["provinceCode"=>$province["code"]])->where("name","like","%".$c_city."%")->first();
					if(!$city) {
						$message["city"] = "城市错误";
						$mark = false;
					} else {
						$ban_address=BanCityModel::getBanAddressExpress($express_id,2,$province->name,$city->name);
						if($ban_address) {
							$message["city"] = $v["province"].$v["city"]."已设置为禁发地";
							$mark = false;
						}
					}
				}

			}

			if(!empty($v["district"])) {
				$ban_address=BanCityModel::getBanAddressExpress($express_id,3,$province->name,$v["city"],$v["district"]);
				if($ban_address) {
					$message["district"] = $v["province"].$v["city"].$v["district"]."已设置为禁发地";
					$mark = false;
				}
			}
			$data[$k]["mark"] = $mark;
			$data[$k]["message"] = (object)$message;
			$orderConsigneeInfo = OrderConsignee::query()
				->whereIn("status",["p","s"])
				->where(["ext_platform_order_sn"=>$v["ext_platform_order_sn"]])->first();
			if(!empty($orderConsigneeInfo)) {
				$data[$k]["order_status"] = "已下单";
			} else {
				$data[$k]["order_status"] = "未下单";
			}
			
		}
		return $data;
	}
}
