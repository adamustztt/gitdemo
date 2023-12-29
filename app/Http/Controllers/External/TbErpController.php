<?php


namespace App\Http\Controllers\External;


use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Http\Controllers\BaseController;
use App\Http\Logic\External\TbErpLogic;
use App\Models\SettingApiUserModel;

class TbErpController extends BaseController
{
//	public function requestTbErp(){
//		$route = $_SERVER['REQUEST_URI'];
//		$routeArr = explode("/",$route);
//		$code = $routeArr[count($routeArr)-1];
//		$params = app("request")->all();
//		$useErp = SettingApiUserModel::query()->where(["user_id"=>$params["user_id"],"code"=>$code,"type"=>"tberp","status"=>1])->first();
//		if(empty($useErp)) {
//			CommonUtil::throwException(ErrorEnum::ERP_USER_AUTH);
//		}
//		$req = TbErpLogic::requestTbErp($code);
//		return $this->responseJson($req);
//	}
	public function requestTbErp($code){
		$params = app("request")->all();
		$useErp = SettingApiUserModel::query()->where(["user_id"=>$params["user_id"],"code"=>$code,"type"=>"tberp","status"=>1])->first();
		if(empty($useErp)) {
			CommonUtil::throwException(ErrorEnum::ERP_USER_AUTH);
		}
		$req = TbErpLogic::requestTbErp($code);
		return $this->responseJson($req);
	}
	 //
	public function tboaidhigh()
	{
		$req = TbErpLogic::tboaidhigh();
		return $this->responseJson($req);
	}
	//
	public function getdecryptbyid()
	{
		$req = TbErpLogic::getdecryptbyid();
		return $this->responseJson($req);
	}
	// 1688商品详情
	public function iteminfo1688()
	{
		$req = TbErpLogic::iteminfo1688();
		return $this->responseJson($req);
	}
	public function iteminfolowprice()
	{
		$req = TbErpLogic::iteminfolowprice();
		return $this->responseJson($req);
	}
}
