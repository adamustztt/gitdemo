<?php


namespace App\Http\Logic\External;


use App\Http\Logic\BaseLogic;
use App\Http\Utils\BaseUtil;
use App\Models\SettingApiModel;
use App\Services\Erp\DouYinErpService;
use App\Services\Erp\KuaiShouErpService;
use App\Services\Vtool\ErpService;
use Illuminate\Support\Facades\DB;

class DyErpLogic extends BaseLogic
{
	// 请求抖音下单
	public static function requestDyErp($code)
	{
		$params = app("request")->all();
		Db::beginTransaction();
		try {
			$price = ErpLogic::_createErpOrder($params["user_id"], $code);
			$data = DouYinErpService::dyRequest($params, $code);
			$data["deduct_money"] = $price;
			Db::commit();
		} catch (\Exception $e) {
			Db::rollBack();
			throw $e;
		}
		$data = BaseUtil::parseArrayToLine($data);
		return $data;
	}
}
