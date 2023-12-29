<?php


namespace App\Http\Logic\External;


use App\Enums\ErrorEnum;
use App\Enums\ExpressEnum;
use App\Helper\CommonUtil;
use App\Http\Logic\BaseLogic;
use App\Http\Utils\BaseUtil;
use App\Http\Utils\LoggerFactoryUtil;
use App\Models\BanCityModel;
use App\Models\CustomWarehouseExpressModel;
use App\Models\CustomWarehouseModel;
use App\Models\DamaijiaUserExpressPrice;
use App\Models\ExpressModel;
use App\Models\ExpressWarehouseModel;
use App\Models\Site;
use App\Models\User;
use App\Models\Warehouse;

class CustomWarehouseLogic extends BaseLogic
{
    /**
     * 获取自定义仓库
     */
    public static function getCustomeWarehouseLogic()
    {
        $datas = [];
        $customWarehouses = CustomWarehouseModel::query()->select(["id","custom_warehouse_name"])->get();
        if($customWarehouses){
            $customWarehouses = $customWarehouses->toArray();
            //获取所有发货地
            $expresses = ExpressModel::query()->pluck("express_name","id");
            //获取发货地别名
            $expressesAliasName = ExpressModel::query()->pluck("express_alias_name","id");
            $expresses = $expresses?$expresses->toArray():[];
            //获取所有快递关联发货地
            $customeWarehouseExpresses = CustomWarehouseExpressModel::query()->select(["custom_warehouse_id","express_id"])->get();
            $customeWarehouseExpresses = $customeWarehouseExpresses?$customeWarehouseExpresses->toArray():[];
            //将快递ID=>发货地信息
            //获取平台设置的上游仓库价格
            $baseWarehousePriceMap = ExpressWarehouseModel::query()->where("user_id",0)->pluck("api_price","express_id");
            $baseWarehousePriceMap = $baseWarehousePriceMap?$baseWarehousePriceMap->toArray():[];

            //获取用户设置的价格
            $userWarehousePriceMap = ExpressWarehouseModel::query()->where("user_id",app("request")->user_id)->pluck("api_price","express_id");
            $userWarehousePriceMap = $userWarehousePriceMap?$userWarehousePriceMap->toArray():[];

            //获取发货地对应的禁发地
            $forbidSends = BanCityModel::query()->where("is_delete",1)->where("open_time","<",date("Y-m-d H:i:s"))
                ->where("off_time",">=",date("Y-m-d H:i:s"))->get();
            $forbidSends = $forbidSends?$forbidSends->toArray():[];
            $tmpForbidSends = [];
            foreach ($forbidSends as $forbidSend){
                //判断是否在禁发时间内
                $tmpForbidSends[$forbidSend["express_id"]] = [
                    "district"=>explode(",",$forbidSend["city_names"]),
                    "startTime"=>$forbidSend["open_time"],
                    "endTime"=>$forbidSend["off_time"],
                ];
            }

            $tmpCustomExpress = [];
            foreach ($customeWarehouseExpresses as $customeWarehouseExpress){
                $tmpCustomExpress[$customeWarehouseExpress["custom_warehouse_id"]][] = [
                    "id"=>$customeWarehouseExpress["express_id"],
                    "warehouseName"=>isset($expresses[$customeWarehouseExpress["express_id"]])?$expresses[$customeWarehouseExpress["express_id"]]:"",
                    "warehouseAliasName"=>isset($expressesAliasName[$customeWarehouseExpress["express_id"]])?$expressesAliasName[$customeWarehouseExpress["express_id"]]:"",
                    "warehousePrice"=>isset($userWarehousePriceMap[$customeWarehouseExpress["express_id"]])?$userWarehousePriceMap[$customeWarehouseExpress["express_id"]]:$baseWarehousePriceMap[$customeWarehouseExpress["express_id"]],//仓库快递价格
                    "forbidSend"=>isset($tmpForbidSends[$customeWarehouseExpress["express_id"]])?$tmpForbidSends[$customeWarehouseExpress["express_id"]]:[]
                ];
            }
            //快递对应发货地
            foreach ($customWarehouses as $customWarehouse){
                $datas[] = [
                    "id"=>$customWarehouse["id"],
                    "expressName"=>$customWarehouse["custom_warehouse_name"],//快递名称
                    "expressImgUrl"=>isset(ExpressEnum::EXPRESS_MAP[$customWarehouse["custom_warehouse_name"]])?ExpressEnum::EXPRESS_MAP[$customWarehouse["custom_warehouse_name"]]:"",
                    "warehouseSend"=>isset($tmpCustomExpress[$customWarehouse["id"]])?$tmpCustomExpress[$customWarehouse["id"]]:[]
                ];
            }
        }
        return $datas;
    }
	/**
	 * 获取自定义仓库
	 */
	public static function getCustomerWarehouseLogicV1()
	{
		$datas = [];
		$customWarehouses = CustomWarehouseModel::query()->select(["id","custom_warehouse_name"])->where("status",1)->get();
		$logs = new LoggerFactoryUtil(CustomWarehouseLogic::class);
		if($customWarehouses){
			$customWarehouses = $customWarehouses->toArray();
			//获取所有发货地
			$expresses = ExpressModel::query()->pluck("express_name","id");
			$expresses_encry = ExpressModel::query()->pluck("is_support_encry","id")->toArray();
			//获取发货地别名
			$expressesAliasName = ExpressModel::query()->pluck("express_alias_name","id");
			$expresses = $expresses?$expresses->toArray():[];
			//获取所有快递关联发货地
			$customeWarehouseExpresses = CustomWarehouseExpressModel::query()->select(["custom_warehouse_id","express_id"])->get();
			$customeWarehouseExpresses = $customeWarehouseExpresses?$customeWarehouseExpresses->toArray():[];
			//将快递ID=>发货地信息
			//获取平台设置的上游仓库价格
			$site_id = app("request")->site_id;
			if($site_id>1) {
				$siteInfo = Site::query()->where("id",$site_id)->first();
				if(empty($siteInfo)) {
					CommonUtil::throwException(ErrorEnum::ERROR_EXT_AUTH_FAILED);
				}
				$user_site_id = $siteInfo->user_id;
			} else {
				$user_site_id = 0;
				
			}
			
			$baseWarehousePriceMap = DamaijiaUserExpressPrice::query()->where("user_id",$user_site_id)->pluck("api_price","express_id");
			$baseWarehousePriceMap = $baseWarehousePriceMap?$baseWarehousePriceMap->toArray():[];
			if(empty($baseWarehousePriceMap)) {
				CommonUtil::throwException(ErrorEnum::ERROR_PRODUCT_EXPRESS);
			}
			//获取用户设置的价格
			$userWarehousePriceMap = DamaijiaUserExpressPrice::query()->where("user_id",app("request")->user_id)->pluck("api_price","express_id");
			$userWarehousePriceMap = $userWarehousePriceMap?$userWarehousePriceMap->toArray():[];
			$logs->info("查询到的用户价格信息：".json_encode($userWarehousePriceMap));
			//获取发货地对应的禁发地
			$forbidSends = BanCityModel::query()->where("is_delete",1)->where("open_time","<",date("Y-m-d H:i:s"))
				->where("off_time",">=",date("Y-m-d H:i:s"))->get();
			$forbidSends = $forbidSends?$forbidSends->toArray():[];
			$tmpForbidSends = [];
			foreach ($forbidSends as $forbidSend){
				//判断是否在禁发时间内
				$tmpForbidSends[$forbidSend["express_id"]] = [
					"district"=>explode(",",$forbidSend["city_names"]),
					"startTime"=>$forbidSend["open_time"],
					"endTime"=>$forbidSend["off_time"],
				];
			}

			$tmpCustomExpress = [];
			foreach ($customeWarehouseExpresses as $customeWarehouseExpress){
				$tmpCustomExpress[$customeWarehouseExpress["custom_warehouse_id"]][] = [
					"id"=>$customeWarehouseExpress["express_id"],
					"warehouseName"=>isset($expresses[$customeWarehouseExpress["express_id"]])?$expresses[$customeWarehouseExpress["express_id"]]:"",
					"is_support_encry"=>isset($expresses_encry[$customeWarehouseExpress["express_id"]])?$expresses_encry[$customeWarehouseExpress["express_id"]]:"",
					"warehouseAliasName"=>isset($expressesAliasName[$customeWarehouseExpress["express_id"]])?$expressesAliasName[$customeWarehouseExpress["express_id"]]:"",
					"warehousePrice"=>isset($userWarehousePriceMap[$customeWarehouseExpress["express_id"]])?$userWarehousePriceMap[$customeWarehouseExpress["express_id"]]:$baseWarehousePriceMap[$customeWarehouseExpress["express_id"]],//仓库快递价格
					"forbidSend"=>isset($tmpForbidSends[$customeWarehouseExpress["express_id"]])?$tmpForbidSends[$customeWarehouseExpress["express_id"]]:[]
				];
			}
			$logs->info("查询到的用户价格信息：".json_encode($tmpCustomExpress));
			//快递对应发货地
			foreach ($customWarehouses as $customWarehouse){
				$datas[] = [
					"id"=>$customWarehouse["id"],
					"expressName"=>$customWarehouse["custom_warehouse_name"],//快递名称
					"expressImgUrl"=>isset(ExpressEnum::EXPRESS_MAP[$customWarehouse["custom_warehouse_name"]])?ExpressEnum::EXPRESS_MAP[$customWarehouse["custom_warehouse_name"]]:"",
					"warehouseSend"=>isset($tmpCustomExpress[$customWarehouse["id"]])?$tmpCustomExpress[$customWarehouse["id"]]:[]
				];
			}
		}
		return $datas;
	}
}
