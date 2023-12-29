<?php


namespace App\Http\Controllers\Cron;


use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Http\Controllers\BaseController;
use App\Models\Channel;
use App\Services\Warehouses\WarehouseService;
use Illuminate\Http\Request;
use Tool\ShanTaoTool\QiWeiTool;

class CronWarehouseController extends BaseController
{
	/**
	 * @author ztt
	 * 定时更新仓库信息
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 * @throws \App\Exceptions\ApiException
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 * @throws \Tool\ShanTaoTool\Exception\QiWeiException
	 */
	public function cronSyncWarehouse(Request $request) {
		$params = $this->validate($request, [
			"channel_id" => "required",
			"token"=>"required",
		]);
		if($params["token"] != env("CRON_SYNC_PRODUCT_TOKEN")) {
			CommonUtil::throwException(ErrorEnum::VALIDATE_ERROR);
		}
		$channel_id = $params["channel_id"];
		$channel = Channel::getById($channel_id);
		if(!$channel) {
			CommonUtil::throwException(ErrorEnum::VALIDATE_ERROR);
		}
		try{
			$ret = WarehouseService::getClass($channel_id)->saveWarehouse();
			if(!$ret) {
				echo $channel["name"]."同步仓库失败";
			} else {
				echo $channel["name"]."同步仓库成功";
			}
		}catch (\Exception $e) { // 连续5次失败在预警出来
			try{
				$ret = WarehouseService::getClass($channel_id)->saveWarehouse();
				if(!$ret) {
					echo $channel["name"]."同步仓库失败";
				} else {
					echo $channel["name"]."同步仓库成功";
				}
			}catch (\Exception $e) {
				try{
					$ret = WarehouseService::getClass($channel_id)->saveWarehouse();
					if(!$ret) {
						echo $channel["name"]."同步仓库失败";
					} else {
						echo $channel["name"]."同步仓库成功";
					}
				}catch (\Exception $e) {
					try{
						$ret = WarehouseService::getClass($channel_id)->saveWarehouse();
						if(!$ret) {
							echo $channel["name"]."同步仓库失败";
						} else {
							echo $channel["name"]."同步仓库成功";
						}
					}catch (\Exception $e) {
						try{
							$ret = WarehouseService::getClass($channel_id)->saveWarehouse();
							if(!$ret) {
								echo $channel["name"]."同步仓库失败";
							} else {
								echo $channel["name"]."同步仓库成功";
							}
						}catch (\Exception $e) {
							$err_policy["code"] = $e->getCode();
							$err_policy["code"] = $e->getMessage();
							$policy = env("POLICE_FROM").$channel["name"]."同步仓库失败".json_encode($err_policy,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
							QiWeiTool::sendMessageToBaoJing($policy);
							echo json_encode($err_policy,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
						}
					}
				}
			}
		}
	}
}
