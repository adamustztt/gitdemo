<?php


namespace App\Http\Logic;


use App\Enums\ErrorEnum;
use App\Enums\ExpressEnum;
use App\Helper\CommonUtil;
use App\Http\Utils\BaseUtil;
use App\Http\Utils\LoggerFactoryUtil;
use App\Models\CustomWarehouseExpressModel;
use App\Models\CustomWarehouseModel;
use App\Models\DamaijiaWarehouseUserSource;
use App\Models\ExpressModel;
use App\Models\ExpressProductModel;
use App\Models\ExpressWarehouseModel;
use App\Models\Product;
use App\Models\Site;
use App\Models\SiteProduct;
use App\Models\User;
use App\Models\UserLevelModel;
use App\Models\UserProductProfit;
use App\Models\Warehouse;

class ProductLogic extends BaseLogic
{
    /**
     * 获取商品列表逻辑
     * @param int $site 站点ID
     */
  /*  public static function getListV1Logic($site=1)
    {
        //获取有效的上游仓库
        $warehouseIds = Warehouse::query()->where("status","n")->pluck("id");
        $warehouseIds = $warehouseIds?$warehouseIds->toArray():[];
        $params = app("request")->all();
        $pageQuery = Product::query()->orderBy("isort","asc")->leftJoin("site_product","site_product.product_id","product.id")
        ->whereIn("product.warehouse_id",$warehouseIds)->where("product.status","n")->where("site_product.site_id",$site);

        //1.判断是否存在快递id
        $relationWarehouseIds = [];
        if(isset($params["warehouseId"])&&$params["warehouseId"]){
            //判断是否存在发货地ID
            if(isset($params["expressSendId"])&&$params["expressSendId"]){
                //根据发货地找到上游仓库ID
                $relationWarehouseIds = ExpressProductModel::query()->where("damaijia_express_id",$params["expressSendId"])
                    ->pluck("product_id");
                $relationWarehouseIds = $relationWarehouseIds?$relationWarehouseIds->toArray():[];
                $pageQuery->whereIn("product.id",$relationWarehouseIds);
            }else{
                //不存在则获取该快递下所有的发货地
                $expressIds = CustomWarehouseExpressModel::query()->where("custom_warehouse_id",$params["warehouseId"])->pluck("express_id");
                $expressIds = $expressIds?$expressIds->toArray():[];
                if($expressIds){
                    //根据发货地找到上游仓库ID
                    $relationWarehouseIds = ExpressProductModel::query()->whereIn("damaijia_express_id",$expressIds)
                        ->pluck("product_id");
                    $relationWarehouseIds = $relationWarehouseIds?$relationWarehouseIds->toArray():[];
                    $pageQuery->whereIn("product.id",$relationWarehouseIds);
                }
            }
        }
//        if($relationWarehouseIds){
//            $pageQuery->whereIn("product.id",$relationWarehouseIds);
//        }
        //2.判断是否主站,不是主站则需要获取分站上架的商品
        $fieldSql = "product.id AS product_id, product.alias_name as name,weight,
					isort, product.create_time, product.othumb, product.sales,
					product.warehouse_id,
					product.stock,product.user_source";

//        if($site!=1){
//            $pageQuery->where("site_product.status",1);
//            $fieldSql.=",site_product.selling_price as price";
//        }else{
//            //主站
//            $fieldSql.=",site_product.price as price";
//        }
		if($site!=1){
			$pageQuery->where("site_product.status",1);
		}
		$fieldSql.=",site_product.selling_price as price";
        //3.判断是否排序
        if(isset($params["sort"])&&$params["sort"]){
            $orderBy = $params["sort"][0]["reverse"]?"asc":"desc";
            $pageQuery->orderBy($params["sort"][0]["field"],$orderBy);
        }
        $pageRes = $pageQuery->selectRaw($fieldSql)->paginate($params["pageSize"]);
        $datas = $pageRes->items();
        //获取有效的商品ID
        $productIds = [];
        foreach ($datas as $data){
            $productIds[] = $data["product_id"];
        }
        //获取发货地信息
        $expressSendNames = ExpressModel::query()->pluck("express_alias_name","id");
        $expressSendNames = $expressSendNames?$expressSendNames->toArray():[];

        //获取所有快递
        $warehouseExpresses = CustomWarehouseModel::query()->pluck("custom_warehouse_name","id");
        $warehouseExpresses = $warehouseExpresses?$warehouseExpresses->toArray():[];

        //获取发货地对应的快递  发货地ID=>快递ID
        $warehouseExpressSends = CustomWarehouseExpressModel::query()->pluck("custom_warehouse_id","express_id");
        $warehouseExpressSends = $warehouseExpressSends?$warehouseExpressSends->toArray():[];

        //获取所有商品对应的发货地(目前只是一个商品对应一个发货地)
        $productExpressSends = ExpressProductModel::query()->whereIn("product_id",$productIds)->get();
        $productExpressSends = $productExpressSends?$productExpressSends->toArray():[];
        $productSends = [];//商品ID=>发货地ID数组
        foreach ($productExpressSends as $productExpressSend){
            $productSends[$productExpressSend["product_id"]] = $productExpressSend["damaijia_express_id"];
        }
        $tmpProductSends = [];//商品ID=>发货地名称
        foreach ($productSends as $key=>$productSend){
            if(isset($expressSendNames[$productSend])){
                $tmpProductSends[$key] = [
                    "sendName"=>$expressSendNames[$productSend],//发货地名称
                    "warehouseId"=>isset($warehouseExpressSends[$productSend])?$warehouseExpressSends[$productSend]:""//快递ID
                ];
            }
        }
        //给商品加上仓库名和运费
        foreach ($datas as &$data){
            //发货地
            $data["warehouse_alias_name"] = isset($tmpProductSends[$data["product_id"]]["sendName"])?$tmpProductSends[$data["product_id"]]["sendName"]:"";
            //快递
            $expressName = isset($warehouseExpresses[$tmpProductSends[$data["product_id"]]["warehouseId"]])?$warehouseExpresses[$tmpProductSends[$data["product_id"]]["warehouseId"]]:"";
            $data["express_img_url"] = isset(ExpressEnum::EXPRESS_MAP[$expressName])?ExpressEnum::EXPRESS_MAP[$expressName]:"";
			// 商品发货平台
            if(!empty($data["user_source"])) {
				$user_source = explode(",",$data["user_source"]);
				$source_info = [];
				foreach ($user_source as $k=> $v) {
					$source_info[$k]["user_source"] = $v;
					$source_info[$k]["remark"] = "";
				}
				$data["source_info"] = $source_info;
			} else {
				$source_info = DamaijiaWarehouseUserSource::listByWhere(
					["warehouse_id"=>$data["warehouse_id"],"user_source_status"=>1],
					["user_source","remark"]
				);
				$data["source_info"] = $source_info;
			}

        }
        return [
            "index"=>$params["page"],
            "total"=>$pageRes->total(),
            "list"=>BaseUtil::parseToArray($datas)
        ];
    }*/
	/**
	 * 获取商品列表逻辑
	 * @param int $site 站点ID
	 */
	public static function getListV2Logic($site=1,$userInfo)
	{
		$user_id = Site::query()->where("id",$site)->value("user_id");
		//获取有效的上游仓库
		$warehouseIds = Warehouse::query()->where("status","n")->pluck("id");
		$warehouseIds = $warehouseIds?$warehouseIds->toArray():[];
		$params = app("request")->all();
		
		if($site!=1){
			$pageQuery = Product::query()->leftJoin("site_product","site_product.product_id","product.id")
				->whereIn("product.warehouse_id",$warehouseIds)->where("product.status","n")->where("site_product.site_id",$site);
		} else {
			$pageQuery = Product::query()->leftJoin("site_product","site_product.product_id","product.id")
				->whereIn("product.warehouse_id",$warehouseIds)->where("product.status","n")->where("web_status","n")->where("site_product.site_id",$site);
		}
		//1.判断是否存在快递id
		$relationWarehouseIds = [];
		if(isset($params["warehouseId"])&&$params["warehouseId"]){
			//判断是否存在发货地ID
			if(isset($params["expressSendId"])&&$params["expressSendId"]){
				//根据发货地找到上游仓库ID
				$relationWarehouseIds = ExpressProductModel::query()->where("damaijia_express_id",$params["expressSendId"])
					->pluck("product_id");
				$relationWarehouseIds = $relationWarehouseIds?$relationWarehouseIds->toArray():[];
				$pageQuery->whereIn("product.id",$relationWarehouseIds);
			}else{
				//不存在则获取该快递下所有的发货地
				$expressIds = CustomWarehouseExpressModel::query()->where("custom_warehouse_id",$params["warehouseId"])->pluck("express_id");
				$expressIds = $expressIds?$expressIds->toArray():[];
				if($expressIds){
					//根据发货地找到商品ID集合
					$relationWarehouseIds = ExpressProductModel::query()->whereIn("damaijia_express_id",$expressIds)
						->pluck("product_id");
					$relationWarehouseIds = $relationWarehouseIds?$relationWarehouseIds->toArray():[];
					$pageQuery->whereIn("product.id",$relationWarehouseIds);
				}
			}
		}
		//2.判断是否主站,不是主站则需要获取分站上架的商品
		$fieldSql = "product.id AS product_id, product.alias_name as name,weight,
					isort, product.create_time, product.othumb, product.sales,
					product.warehouse_id,
					product.stock,product.user_source";
		if($site!=1){
			$pageQuery->where("site_product.status",1);
		}
		$fieldSql.=",site_product.profit,site_product.price";
		//3.判断是否排序
		if(isset($params["sort"])&&$params["sort"]){
			$orderBy = $params["sort"][0]["reverse"]?"desc":"asc";
			if($params["sort"][0]["field"] == "created_time") {
				$params["sort"][0]["field"]="product.created_time";
			}
			$pageQuery->orderBy($params["sort"][0]["field"],$orderBy);
		} else {
			$pageQuery->orderBy("isort","asc");
		}
		$pageRes = $pageQuery->selectRaw($fieldSql)->paginate($params["pageSize"]);
		$datas = $pageRes->items();
		//获取有效的商品ID
		$productIds = [];
		foreach ($datas as $data){
			$productIds[] = $data["product_id"];
		}
		//获取发货地信息
		$expressSendNames = ExpressModel::query()->pluck("express_alias_name","id");
		$expressSendNames = $expressSendNames?$expressSendNames->toArray():[];//获取发货地信息
		$expressNames = ExpressModel::query()->pluck("express_name","id");
		$expressNames = $expressNames?$expressNames->toArray():[];

		//获取所有快递
		$warehouseExpresses = CustomWarehouseModel::query()->pluck("custom_warehouse_name","id");
		$warehouseExpresses = $warehouseExpresses?$warehouseExpresses->toArray():[];

		//获取发货地对应的快递  发货地ID=>快递ID
		$warehouseExpressSends = CustomWarehouseExpressModel::query()->pluck("custom_warehouse_id","express_id");
		$warehouseExpressSends = $warehouseExpressSends?$warehouseExpressSends->toArray():[];

		//获取所有商品对应的发货地(目前只是一个商品对应一个发货地)
		$productExpressSends = ExpressProductModel::query()->whereIn("product_id",$productIds)->get();
		$productExpressSends = $productExpressSends?$productExpressSends->toArray():[];
		$productSends = [];//商品ID=>发货地ID数组
		foreach ($productExpressSends as $productExpressSend){
			$productSends[$productExpressSend["product_id"]] = $productExpressSend["damaijia_express_id"];
		}
		$tmpProductSends = [];//商品ID=>发货地名称
		foreach ($productSends as $key=>$productSend){
			if(isset($expressSendNames[$productSend])){
				$tmpProductSends[$key] = [
					"expressId"=>$productSend,
					"sendName"=>$expressSendNames[$productSend],//发货地名称
					"express_name"=>$expressNames[$productSend],//发货地名称
					"warehouseId"=>isset($warehouseExpressSends[$productSend])?$warehouseExpressSends[$productSend]:""//快递ID
				];
			}
		}
		//给商品加上仓库名和运费
		$customWarehouseIdMap = CustomWarehouseExpressModel::query()->pluck("custom_warehouse_id","express_id")->toArray();
		$preferential_amount = 0;
		if(!empty($userInfo)) {
			$preferential_amount = UserLevelModel::query()->where(["id"=>$userInfo["level_id"],"status"=>1])->value("preferential_amount");
			$expressMap = ExpressLogic::listUserExpressPriceLogic($userInfo["id"]);
		}
		foreach ($datas as &$data){
			//发货地
			$data["expressId"] = isset($tmpProductSends[$data["product_id"]]["expressId"])?$tmpProductSends[$data["product_id"]]["expressId"]:"";
			//$data["custom_warehouse_id"] = CustomWarehouseExpressModel::query()->where("express_id",$data["expressId"])->value("custom_warehouse_id");
			$data["custom_warehouse_id"] =$customWarehouseIdMap[$data["expressId"]];
			// 获取运费
			$express_price = "";
			$log = new LoggerFactoryUtil(ProductLogic::class);
			$log->info("仓库ID".$data["expressId"].json_encode($userInfo));
			if(!empty($userInfo) && !empty($data["expressId"])) {
//				$express_price = ExpressLogic::getUserExpressPriceLogic($userInfo["id"],$data["expressId"])->price;
				$express_price = $expressMap[$data["expressId"]];
				if($preferential_amount) {
					$express_price = $express_price-$preferential_amount;
				}
			} 
			$data["express_price"] = $express_price;
			$data["warehouse_alias_name"] = isset($tmpProductSends[$data["product_id"]]["sendName"])?$tmpProductSends[$data["product_id"]]["sendName"]:"";
			$data["express_name"] = isset($tmpProductSends[$data["product_id"]]["express_name"])?$tmpProductSends[$data["product_id"]]["express_name"]:"";
			//快递
			$expressName = isset($warehouseExpresses[$tmpProductSends[$data["product_id"]]["warehouseId"]])?$warehouseExpresses[$tmpProductSends[$data["product_id"]]["warehouseId"]]:"";
			$data["express_img_url"] = isset(ExpressEnum::EXPRESS_MAP[$expressName])?ExpressEnum::EXPRESS_MAP[$expressName]:"";
			// 商品发货平台
			if(!empty($data["user_source"])) {
				$user_source = explode(",",$data["user_source"]);
				$source_info = [];
				foreach ($user_source as $k=> $v) {
					$source_info[$k]["user_source"] = $v;
					$source_info[$k]["remark"] = "";
				}
				$data["source_info"] = $source_info;
			} else {
				$source_info = DamaijiaWarehouseUserSource::listByWhere(
					["warehouse_id"=>$data["warehouse_id"],"user_source_status"=>1],
					["user_source","remark"]
				);
				$data["source_info"] = $source_info;
			}
			if($site == 1) {
				$data["price"] = $data["price"]+$data["profit"];
			} else {
				$data["price"] = self::getSiteProductCostPrice($data["product_id"],$user_id,0)+$data["profit"];
			}

		}
		return [
			"index"=>$params["page"],
			"total"=>$pageRes->total(),
			"list"=>BaseUtil::parseToArray($datas)
		];
	}

	
    /**
     * @param $product_id int 商品ID
     * @param $user_id int 站长用户ID
     * @param int $cost_price 成本价
     * @return int|mixed
     * @throws \App\Exceptions\ApiException
     */
	public static function getSiteProductCostPrice($product_id,$user_id,$cost_price=0,$costCount=0)
	{
        $instance = new LoggerFactoryUtil(ProductLogic::class);
        $instance->info("user_id:".$user_id);
        $instance->info("层数:".$costCount);
	    //判断层数是否超出限制,防止出现死循环
        if($costCount>=5){
            CommonUtil::throwException(ErrorEnum::ERROR_COST_PRODUCT);
        }
		$user = User::getById($user_id);
		if(empty($user)) {
			CommonUtil::throwException(ErrorEnum::PARAM_ERROR);
		}
		// 1判断是否为用户单独设置利润
		$user_profit = UserProductProfit::query()->where(["user_id"=>$user_id])->value("user_profit");
		$siteInfo = Site::query()->where("id",$user->site_id)->first();
        if(empty($siteInfo)) {
            CommonUtil::throwException(ErrorEnum::PARAM_ERROR);
        }
		$site_user_id = $siteInfo->user_id;
		if($siteInfo->id ==1) {//代表取到主站
			$basePrice = SiteProduct::query()->where(["user_id"=>$site_user_id,"product_id"=>$product_id])->first();
			if(!$basePrice) {
				CommonUtil::throwException(ErrorEnum::PARAM_ERROR);
			}
			if($user_profit) {
				return $cost_price+$user_profit+$basePrice->price;
			} else {
				$SiteProduct=SiteProduct::query()->where(["user_id"=>$user_id,"product_id"=>$product_id])->first();
				return $cost_price+$SiteProduct->site_cost_profit + $basePrice->price; //累计成本利润+ 该用户成本利润 + 平台成本利润
			}
		}
		// 2 如果单独设置
		if($user_profit) {
			$cost_price = $cost_price + $user_profit;
		} else {
			//3 未单独设置 去站长的默认利润
			$SiteProduct=SiteProduct::query()->where(["user_id"=>$user_id,"product_id"=>$product_id])->first();
			$cost_price = $cost_price+$SiteProduct->site_cost_profit; // 累计成本利润
		}
		$costCount++;
		$user_id = $site_user_id;
		return self::getSiteProductCostPrice($product_id,$user_id,$cost_price,$costCount);
	}
}
