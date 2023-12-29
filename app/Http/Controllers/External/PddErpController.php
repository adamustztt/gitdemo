<?php


namespace App\Http\Controllers\External;


use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Http\Controllers\BaseController;
use App\Http\Logic\External\PddErpLogic;
use App\Models\SettingApiUserModel;

class PddErpController extends BaseController
{
	public function requestPddErp($code)
	{
		$code = str_replace("-",".",$code);
		$params = app("request")->all();
		$useErp = SettingApiUserModel::query()->where(["user_id"=>$params["user_id"],"code"=>$code,"type"=>"pdderp","status"=>1])->first();
		if(empty($useErp)) {
			CommonUtil::throwException(ErrorEnum::ERP_USER_AUTH);
		}
		$req = PddErpLogic::requestPddErp($code);
		return $this->responseJson($req);
	}
}
