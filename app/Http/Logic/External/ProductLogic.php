<?php


namespace App\Http\Logic\External;


use App\Http\Logic\BaseLogic;
use App\Models\CustomWarehouseExpressModel;
use App\Models\DamaijiaWarehouseUserSource;
use App\Models\ExpressModel;
use App\Models\ExpressProductModel;
use App\Models\ExpressWarehouseModel;
use App\Models\Product;
use App\Models\SiteProduct;
use App\Models\Warehouse;

class ProductLogic extends BaseLogic
{
    /**
     * 获取商品逻辑
     */
    public static function getListV1Logic()
    {
        $request = app("request");
		$user_id = $request->user_id;
        $params = $request->all();
        //1.获取发货地ID的商品id
        $productIds = ExpressProductModel::query()->where("damaijia_express_id",$params["id"])->pluck("product_id");
        $productIds = $productIds?$productIds->toArray():[];
		$expressInfo = ExpressModel::getById($params["id"]);
        //2.获取有效的仓库
        $warehouseIds = Warehouse::query()->where("status","n")->pluck("id");
        $warehouseIds = $warehouseIds?$warehouseIds->toArray():[];

        //3.获取商品数据
        $pageRes = Product::query()->leftJoin('site_product','site_product.product_id','product.id')
            ->where("site_id",$request->site_id)
            ->whereIn("product.warehouse_id",$warehouseIds)->where("product.status","n")
            ->whereIn("product.id",$productIds)
            ->select(["product.user_source","product.warehouse_id","product.id","product.api_name as name",
				"product.othumb","site_product.price as cost_price","site_product.api_profit","product.weight",
				"product.stock","product.sales","product.create_time","product.signing_method","product.support_entry_source"])
            ->paginate($params["pageSize"] ?? 20);

        //获取源仓的收件人来源
        $warehouseSources = DamaijiaWarehouseUserSource::query()->get();
        $warehouseSources = $warehouseSources?$warehouseSources->toArray():[];
        $warehouseSourceMap = [];
        foreach ($warehouseSources as $warehouseSource){
            $warehouseSourceMap[$warehouseSource["warehouse_id"]][] = $warehouseSource["user_source"];
        }
        $datas = $pageRes->items();
        foreach ($datas as &$data){
            if($data["user_source"]){
                $data["user_source"] = explode(",",$data["user_source"]);
            }else{
                $data["user_source"] = isset($warehouseSourceMap[$data["warehouse_id"]])?$warehouseSourceMap[$data["warehouse_id"]]:[];
            }
            $api_profit = SiteProduct::query()->where(["user_id"=>$user_id,"product_id"=>$data["id"]])->value("api_profit");
            if(empty($api_profit)) {
				$api_profit = $data["api_profit"];
			}
			$data["cost_price"] = $data["cost_price"]+$api_profit;
            $data["is_support_encry"] = $expressInfo["is_support_encry"];
            unset($data["api_profit"]);
            unset($data["warehouse_id"]);
        }


        return [
            "total"=>$pageRes->total(),
            "list"=>$datas
        ];
    }
}
