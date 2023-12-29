<?php
/**
 * Created by PhpStorm.
 * User: wzz
 * Date: 2020/10/31
 * Time: 17:58
 */

namespace App\Console\Commands\Orders;


use App\Models\OrderConsignee;
use App\Models\UserOrder;
use Illuminate\Console\Command;


class UserOrderTimeOut extends Command
{
	protected $signature = 'order:user-order-time-out';

	protected $description = '检查订单是否超时';

	/**
	 */
	public function handle()
	{
        //已废弃,迁移到定时任务
        return;
		$time = date('Y-m-d H:i:s', (time() - 2 * 60 * 60));
		$whereTime = ['<', 'create_time', $time];
		$where['status'] = PACKAGE_STATUS_PAYMENT;
		// 检查订单超时现象
		UserOrder::query()
			->where($where)->where($whereTime)
			->chunk(100, function ($userOrder) {
				foreach ($userOrder as $value) {
					UserOrder::query()->where(['id' => $value['id']])->update([
						'status' => PACKAGE_STATUS_CANCELED, 'update_time' => date('Y-m-d H:i:s')
					]);
				}
			});
		// 检查包裹超时现象
		OrderConsignee::query()
			->where($where)->where($whereTime)
			->chunk(100,function ($orderConsignee){
				foreach ($orderConsignee as $value){
					OrderConsignee::query()->where(['id'=>$value['id']])->update([
						'status' => PACKAGE_STATUS_CANCELED, 'update_time' => date('Y-m-d H:i:s')
					]);
				}
			});
	}
}
