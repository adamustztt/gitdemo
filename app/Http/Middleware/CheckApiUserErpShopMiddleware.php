<?php


namespace App\Http\Middleware;


use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Models\SettingApiUserModel;
use App\Models\Site;
use App\Models\UserShopModel;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckApiUserErpShopMiddleware
{
	/**
	 * 
	 * @author ztt
	 * @param Request $request
	 * @param Closure $next
	 * @return mixed
	 * @throws \App\Exceptions\ApiException
	 */
	public function handle(Request $request, Closure $next)
	{
		$params = $request->all();
		$useErp = SettingApiUserModel::query()->where(["user_id"=>$params["user_id"],"status"=>1])
			->whereIn("type",["tb","tberp"])
			->first();
		if(empty($useErp)) {
			CommonUtil::throwException(ErrorEnum::ERP_USER_AUTH);
		}
		if(!empty($params["shop_id"])) {
			$data = UserShopModel::query()->where(["user_id"=>$params["user_id"],"shop_id"=>$params["shop_id"]])->first();
			if(empty($data)) {
				CommonUtil::throwException(ErrorEnum::ERP_USER_SHOP);
			}
		}
		
		/**
		 * @var $response JsonResponse
		 */
		$response = $next($request);
		$json = json_decode($response->content(),true);
		if(isset($json["data"]["withholding_money"])) {
			unset($json["data"]["withholding_money"]);
		}
		$response->setJson(json_encode($json));
		return $response;
	}
}
