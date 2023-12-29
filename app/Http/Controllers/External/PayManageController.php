<?php


namespace App\Http\Controllers\External;


use App\Http\Controllers\BaseController;
use App\Http\Logic\External\PayManageLogic;
use Illuminate\Http\Request;

class PayManageController extends BaseController
{
	// 获取支付二维码v2接口
	public function getScanCode(Request  $request)
	{
		$params = $this->validate($request, [
			'orderPrice'=>'required',
			'merchantNum'=>'required',
			'relationOrderNumber'=>'required',
			'orderTitle'=>'string',
		]);
		$data = PayManageLogic::getScanCode($params,$request->user_id);
		return $this->responseJson($data); 
	}
	// 获取支付二维码v2接口
	public function listMerchant(Request  $request)
	{
		$data = PayManageLogic::listMerchant($request->user_id);
		return $this->responseJson($data);
	}
	// 获取支付二维码v2接口
	public function getOrderDetail(Request $request)
	{
		$params = $this->validate($request, [
			'orderNumber'=>'required',
			'merchantNum'=>'required',
			'type'=>'required',
		]);
		$data = PayManageLogic::getOrderDetail($params,$request->user_id);
		return $this->responseJson($data);
	}
}
