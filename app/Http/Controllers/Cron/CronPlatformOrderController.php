<?php


namespace App\Http\Controllers\Cron;


use App\Http\Controllers\BaseController;
use App\Http\Controllers\PlatformOrderController;
use App\Http\Logic\PlatformOrderLogic;
use App\Models\OrderSyncTaskChildModel;

class CronPlatformOrderController extends BaseController
{
	//页面获取订单定时获取
	public function cronGetPlatformOrder()
	{
		$tasks = OrderSyncTaskChildModel::query()->where("status", "=", 0)
			->limit(10)->get();
		foreach ($tasks as $task) {
			try {
				request()->merge([$task->id => $task]);
				//执行中
				OrderSyncTaskChildModel::query()->where("id", "=", $task["id"])->update(["status"=>3]);
				PlatformOrderLogic::cronGetPlatformOrder($task);
			} catch (\Exception $e) {
				if ($task->error_count > 4) {
					$task->status = 2; //失败五次不在重试
				}
				$task->error_count = $task->error_count + 1;
				$task->save();
				echo $e->getMessage();
			}
		}
		return $this->responseJson();
	}
	
	
	
}
