<?php


namespace App\Http\Controllers\External;


use App\Http\Controllers\BaseController;
use App\Http\Logic\External\KsErpLogic;

class  KsErpController extends BaseController
{
	public function requestKsErp($code)
	{
		$req = KsErpLogic::requestKsErp($code);
		return $this->responseJson($req);
	}
	
}
