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

class CheckApiUserDyErpShopMiddleware
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
		if(!empty($params["owner_id"])) {
			$data = UserShopModel::query()->where(["user_id"=>$params["user_id"],"shop_id"=>$params["owner_id"]])->first();
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
		if($json) {
			$response->setJson(json_encode($json));
		}
		return $response;
	}
}
