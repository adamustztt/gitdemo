<?php


namespace App\Http\Logic\External;


use App\Http\Logic\BaseLogic;
use App\Http\Utils\BaseUtil;
use App\Services\Erp\PddErpService;
use Illuminate\Support\Facades\DB;

class PddErpLogic extends BaseLogic
{
	public static function requestPddErp($code)
	{
		$params = app("request")->all();
		Db::beginTransaction();
		try {
			$price = ErpLogic::_createErpOrder($params["user_id"], $code);
			$data = PddErpService::pddRequest($params,$code);
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
