<?php


namespace App\Http\Controllers\External;


use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Http\Controllers\BaseController;
use App\Http\Logic\External\OrderLogic;
use App\Http\Logic\External\PlatformOrderLogic;
use App\Models\BanCityModel;
use App\Models\ExpressProductModel;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Validator;

class PlatformOrderController extends BaseController
{
	public function createEntryOrder()
	{
		$params = app("request")->all();
		$validator = Validator::make($params, [
			"shop_id" => "required",
			"product_id" => "required",
			"consignees" => "required",
			"source" => "required",
			"is_deliver" => "" // 0不自动发货 1自动发货
		]);
		if ($validator->fails()) {
			CommonUtil::throwException([422, $validator->errors()->first()]);
		}
		$data = PlatformOrderLogic::createEntryOrder();
		return $this->responseJson($data);
	}
}
