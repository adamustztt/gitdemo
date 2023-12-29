<?php


namespace App\Http\Controllers\Console;


use App\Http\Controllers\BaseController;
use App\Http\Logic\Console\OrderConsigneeDecryptLogic;

class OrderConsigneeDecryptController extends BaseController
{
	/**
	 * @return string
	 * 包裹解密
	 */
	public function taskPackageDecrypt()
	{
		return OrderConsigneeDecryptLogic::taskPackageDecrypt();
	}
}
