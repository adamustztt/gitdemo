<?php


namespace App\Http\Logic;


use App\Enums\ExpressEnum;
use App\Models\CustomWarehouseExpressModel;
use App\Models\CustomWarehouseModel;
use App\Models\ExpressModel;
use App\Models\ExpressWarehouseModel;

class CustomWarehouseLogic extends BaseLogic
{
	/**
	 * 获取自定义仓库
	 */
	public static function getCustomeWarehouseLogic()
	{
		$datas = [];
		$customWarehouses = CustomWarehouseModel::query()->where(["status"=>1])->select(["id", "custom_warehouse_name","warehouse_img_url"])->get();
		if ($customWarehouses) {
			$customWarehouses = $customWarehouses->toArray();
			//获取所有发货地
			$expresses = ExpressModel::query()->pluck("express_name", "id")->toArray();
			//获取所有快递关联发货地
			$customeWarehouseExpresses = CustomWarehouseExpressModel::query()
				->rightJoin("damaijia_express","damaijia_express.id","=","damaijia_custom_warehouse_express.express_id")
				->orderBy("damaijia_express.sort","asc")
				->orderBy("express_id","desc")->select(["custom_warehouse_id", "express_id"])->get()->toArray();
			//将快递ID=>发货地信息
			$tmpCustomExpress = [];
			foreach ($customeWarehouseExpresses as $customeWarehouseExpress) {
				$tmpCustomExpress[$customeWarehouseExpress["custom_warehouse_id"]][] = [
					"id" => $customeWarehouseExpress["express_id"],
					"expressSendName" => isset($expresses[$customeWarehouseExpress["express_id"]]) ? $expresses[$customeWarehouseExpress["express_id"]] : "",
				];
			}
			//快递对应发货地
			foreach ($customWarehouses as $customWarehouse) {
				$datas[] = [
					"id" => $customWarehouse["id"],
					"warehouseName" => $customWarehouse["custom_warehouse_name"],
//					"warehouseImgUrl" => isset(ExpressEnum::EXPRESS_MAP[$customWarehouse["custom_warehouse_name"]]) ? ExpressEnum::EXPRESS_MAP[$customWarehouse["custom_warehouse_name"]] : "",
					"warehouseImgUrl" => $customWarehouse["warehouse_img_url"],
					"expressSend" => isset($tmpCustomExpress[$customWarehouse["id"]]) ? $tmpCustomExpress[$customWarehouse["id"]] : []
				];
			}
		}
		return $datas;
	}

	public static function getExpressPriceLogic($user_id)
	{

		$datas = [];
		$customWarehouses = CustomWarehouseModel::query()->select(["id", "custom_warehouse_name"])->get();
		if ($customWarehouses) {
			$customWarehouses = $customWarehouses->toArray();
			//获取所有发货地
			$expresses = ExpressModel::query()->pluck("express_name", "id")->toArray();
			//获取所有快递关联发货地
			$customeWarehouseExpresses = CustomWarehouseExpressModel::query()->select(["custom_warehouse_id", "express_id"])->get()->toArray();

			$ExpressWarehouseModel = ExpressWarehouseModel::query()
				->select(["api_price", "price", "vip_price", "site_price", "express_id"])->where("user_id", $user_id)->get()->toArray();
			$tmpExpressWarehouse = [];
			foreach ($ExpressWarehouseModel as $v) {
				$tmpExpressWarehouse[$v["express_id"]][] = [
					"api_price" => $v["api_price"],
					"price" => $v["price"],
					"vip_price" => $v["vip_price"],
					"site_price" => $v["site_price"],
				];
			}
			$_ExpressWarehouseModel = ExpressWarehouseModel::query()
				->select(["api_price", "price", "vip_price", "site_price", "express_id"])->where("user_id", 0)->get()->toArray();
			$_tmpExpressWarehouse = [];
			foreach ($_ExpressWarehouseModel as $v) {
				$_tmpExpressWarehouse[$v["express_id"]][] = [
					"api_price" => $v["api_price"],
					"price" => $v["price"],
					"vip_price" => $v["vip_price"],
					"site_price" => $v["site_price"],
				];
			}
//			dd($tmpExpressWarehouse);
			//将快递ID=>发货地信息
			$tmpCustomExpress = [];
			foreach ($customeWarehouseExpresses as $customeWarehouseExpress) {
				$tmpCustomExpress[$customeWarehouseExpress["custom_warehouse_id"]][] = [
					"api_price" => isset($tmpExpressWarehouse[$customeWarehouseExpress["express_id"]]) ?
						$tmpExpressWarehouse[$customeWarehouseExpress["express_id"]][0]["api_price"] : $_tmpExpressWarehouse[$customeWarehouseExpress["express_id"]][0]["api_price"],
					"price" => isset($tmpExpressWarehouse[$customeWarehouseExpress["express_id"]]) ?
						$tmpExpressWarehouse[$customeWarehouseExpress["express_id"]][0]["price"] : $_tmpExpressWarehouse[$customeWarehouseExpress["express_id"]][0]["price"],
					"vip_price" => isset($tmpExpressWarehouse[$customeWarehouseExpress["express_id"]]) ?
						$tmpExpressWarehouse[$customeWarehouseExpress["express_id"]][0]["vip_price"] : $_tmpExpressWarehouse[$customeWarehouseExpress["express_id"]][0]["vip_price"],
					"site_price" => isset($tmpExpressWarehouse[$customeWarehouseExpress["express_id"]]) ?
						$tmpExpressWarehouse[$customeWarehouseExpress["express_id"]][0]["site_price"] : $_tmpExpressWarehouse[$customeWarehouseExpress["express_id"]][0]["site_price"],
					"id" => $customeWarehouseExpress["express_id"],
					"expressSendName" => isset($expresses[$customeWarehouseExpress["express_id"]]) ? $expresses[$customeWarehouseExpress["express_id"]] : "",
				];
			}
			//快递对应发货地
			foreach ($customWarehouses as $customWarehouse) {
				$datas[] = [
					"id" => $customWarehouse["id"],
					"warehouseName" => $customWarehouse["custom_warehouse_name"],
					"expressSend" => isset($tmpCustomExpress[$customWarehouse["id"]]) ? $tmpCustomExpress[$customWarehouse["id"]] : []
				];
			}
		}
		return $datas;
	}
}
