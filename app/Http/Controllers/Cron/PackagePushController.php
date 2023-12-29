<?php


namespace App\Http\Controllers\Cron;


use App\Http\Controllers\BaseController;
use App\Models\OrderConsignee;
use App\Services\OrderConsigneePushDownService;

class PackagePushController extends BaseController
{
	public function addPackagePush()
	{
		$packages = OrderConsignee::query()
			->where("status","s")
			->where("express_no","!=","")
			->where("is_add_push",0)
			->limit(100)
			->orderBy("id","desc")
			->get();
		$ids=[];
		foreach ($packages as $package) {
			$ids[] = $package["id"];
			OrderConsigneePushDownService::addPush($package["id"],2);
		}
		$packages = OrderConsignee::query()
			->where("status","c")
			->where("is_add_push",0)
			->limit(100)
			->get();
		foreach ($packages as $package) {
			OrderConsigneePushDownService::addPush($package["id"],1);
		}
		return $this->responseJson($ids);
	}
}
