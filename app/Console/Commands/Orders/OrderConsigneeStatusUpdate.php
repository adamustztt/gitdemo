<?php
/**
 * Created by PhpStorm.
 * User: wzz
 * Date: 2020/10/31
 * Time: 17:58
 */

namespace App\Console\Commands\Orders;


use App\Models\OrderConsignee;
use OrderConsignee as OrderConsigneeold;
use App\Models\UserOrder;
use App\Services\Warehouses\WarehouseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class OrderConsigneeStatusUpdate extends Command
{
	protected $signature = 'order:order-consignee-status-update';

	protected $description = '定时更新订单包裹';

	/**
	 */
	public function handle()
	{
	    //已废弃,迁移到定时任务
	    return;
		OrderConsignee::query()
			->where(['status' => PACKAGE_STATUS_SHIPPED])
			->where(['sync_query_status' => USER_ORDER_SYNC_QUERY_STATUS_PENDING])
			->chunk(100, function ($orderConsigneeArr){
			foreach ($orderConsigneeArr as $index => $orderConsignee) {
				$order = UserOrder::getById($orderConsignee->order_id);
				$abstractWarehouse = WarehouseService::getClass($order->channel_id);
				if (empty($abstractWarehouse)){
					echo 'continue' . PHP_EOL;
					OrderConsignee::updateById($orderConsignee->id, ['sync_query_status' => USER_ORDER_SYNC_QUERY_STATUS_FAILED]);
					continue;
				}
				echo $orderConsignee->id . PHP_EOL;
				$abstractWarehouse->saveOrderByQuery($orderConsignee);
			}
		});
	}

}
