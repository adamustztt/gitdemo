<?php


namespace App\Http\Controllers\Cron;


use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Http\Controllers\BaseController;
use App\Models\Channel;
use App\Models\Product;
use App\Models\SiteProduct;
use App\Services\Warehouses\WarehouseService;
use Tool\ShanTaoTool\QiWeiTool;
use Illuminate\Http\Request;
class CronProductController extends BaseController
{
	/**
	 * @author ztt
	 * 定时请求上游更新商品
	 * @param Request $request
	 * @throws \App\Exceptions\ApiException
	 * @throws \Tool\ShanTaoTool\Exception\QiWeiException
	 */
	public function cronSyncProduct(Request $request) {
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
		try {
			// 更新商品
			 WarehouseService::getClass($channel_id)->saveProduct();
			 echo $channel->name."更新商品成功";
		} catch (\Exception $e) {
			try {
				// 更新商品
				WarehouseService::getClass($channel_id)->saveProduct();
				echo $channel->name."更新商品成功";
			} catch (\Exception $e) {
				try {
					// 更新商品
					WarehouseService::getClass($channel_id)->saveProduct();
					echo $channel->name."更新商品成功";
				} catch (\Exception $e) {
					try {
						// 更新商品
						WarehouseService::getClass($channel_id)->saveProduct();
						echo $channel->name."更新商品成功";
					} catch (\Exception $e) {
						try {
							// 更新商品
							WarehouseService::getClass($channel_id)->saveProduct();
							echo $channel->name."更新商品成功";
						} catch (\Exception $e) {
							// 连续同步5次失败再预警出来
							$policy = env("POLICE_FROM").$channel["name"]."渠道同步商品失败".json_encode($e,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
							QiWeiTool::sendMessageToBaoJing($policy);
							echo($e->getCode()."------".$e->getMessage());
						}
					}
				}
			}
		}
	}

	/**
	 * @author ztt
	 * @param Request $request
	 * 定时同步商品价格
	 * @throws \App\Exceptions\ApiException
	 */
	public function cronSyncSiteProductPrice(Request $request) {
		$params = $this->validate($request, [
			"channel_id" => "required",
			"token"=>"required",
		]);
		if($params["token"] != env("CRON_SYNC_PRODUCT_TOKEN")) {
			CommonUtil::throwException(ErrorEnum::VALIDATE_ERROR);
		}
		// 更新分站商品价格
		$channel_id = $params["channel_id"];
		$data= Product::query()->where(["channel_id"=>$channel_id])->get();
		$i=0;
		$j=0;
		foreach ($data as $k=>$v) {
			// 查询站长成本价小于仓库成本价的
			$site_product= SiteProduct::query()
				->where("product_id",$v["id"])
				->where("price","<",$v["cost_price"])->get();
			
			foreach ($site_product as $kk=>$vv) {
				$ls["price"] = $v["cost_price"]; //站长成本价等于商品成本价加1
				// 如果站长售价小于成本价 则售价等于站长成本价  售价已经废弃
				if($vv["selling_price"]<($v["cost_price"]+1)) {
					$ls["selling_price"] = $v["cost_price"]+1;
				}
				$update = SiteProduct::updateById($vv["id"],$ls);
				if($update) {
					$i++;
				} else {
					$j++;
				}
				unset($ls);
			}
		}
		echo "成功条数：".$i.";失败条数：".$j;
	}

}
