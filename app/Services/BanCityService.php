<?php


namespace App\Services;


use App\Helper\CommonUtil;
use App\Http\Logic\BanCityPushDownLogic;
use App\Http\Service\BaseService;
use App\Models\AddressCity;
use App\Models\AddressProvince;
use App\Models\AddressTown;
use App\Models\BanAddressExpress;
use App\Models\BanCityModel;
use App\Models\CustomWarehouseExpressModel;
use App\Models\ExpressProductModel;
use App\Models\ExpressWarehouseModel;
use Illuminate\Support\Facades\DB;

class BanCityService extends BaseService
{
	public static function addBanCityPackage($productId, $package)
	{
		$province = $package["province"];
		$city = $package["city"];
		$district = $package["district"];
		$provinceCode = AddressProvince::query()
			->where("name", "like", "%" . $province . "%")
			->value("code");
		$cityCode = AddressCity::query()
			->where("name", "like", "%" . $city . "%")
			->where("provinceCode", $provinceCode)
			->value("code");
		$districtCode = AddressTown::query()
			->where("name", "like", "%" . $district . "%")
			->where("cityCode", $cityCode)
			->value("code");
		if(empty($districtCode)) {
			return false;
		}
		$express_id = ExpressProductModel::query()->where("product_id", $productId)->value("damaijia_express_id");
		$custom_warehouse_id = CustomWarehouseExpressModel::query()->where("express_id", $express_id)->value("custom_warehouse_id");
		$warehouse_name = ExpressWarehouseModel::query()->where("express_id", $express_id)
			->where("user_id",0)
			->pluck("warehouse_name")->toArray();
		$warehouse_name = implode(",", $warehouse_name);


		$temps = [];
		$date = date("Y-m-d H:i:s");
		DB::beginTransaction();
		try {
			$find = BanAddressExpress::query()
				->where("express_id", $express_id)
				->where("code",$districtCode)
				->where("ban_type", 5)->first();
			if ($find) {
				if($find->off_time<$date) {
					$find->open_time = $date;
					$find->off_time =  date("Y-m-d H:i:s", strtotime("+90 day"));
					$find->save();
					BanCityModel::query()->where("id",$find->ban_city_id)->update(["open_time"=>$date,"off_time"=>date("Y-m-d H:i:s", strtotime("+90 day"))]);
					BanCityPushDownLogic::addBanCityPush($express_id);
					DB::commit();
					return true;
				}
				return false;
			} else {
				$data['city_codes'] = json_encode([[$provinceCode, $cityCode, $districtCode]]);
				$data["city_names"] = $province . "/" . $city . "/" . $district;
				$data["warehouse_name"] = $warehouse_name;
				$data["express_id"] = $express_id;
				$data["custom_warehouse_id"] = $custom_warehouse_id;
				$data["remark"] = "系统禁发";
				$data["open_time"] = date("Y-m-d H:i:s");
				$data["off_time"] = date("Y-m-d H:i:s", strtotime("+90 day"));
				$data["ban_type"] = 5;
				$find = BanCityModel::create($data);
			}
			$temp["type"] = 3;
			$temp["express_id"] = $express_id;
			$temp['code'] = $districtCode;
			$temp["name"] = $province . "/" . $city . "/" . $district;;
			$temp["ban_city_id"] = $find->id;
			$temp["open_time"] = date("Y-m-d H:i:s");
			$temp["off_time"] = date("Y-m-d H:i:s", strtotime("+90 day"));
			$temp["ban_type"] = 5;
			$temp["add_type"] = 2;
			BanAddressExpress::query()->create($temp);
			// 如果是完全禁发  添加预推送最新禁发信息
			BanCityPushDownLogic::addBanCityPush($express_id);
			DB::commit();
		} catch (\Exception $e) {
			DB::rollBack();
		}
		return true;
	}
}
