<?php
namespace App\Http\Controllers;

use App\Enums\ExpressEnum;
use App\Helper\WhereUtil;
use App\Http\Logic\ExpressLogic;
use App\Http\Logic\ProductLogic;
use App\Models\AddressCity;
use App\Models\AddressProvince;
use App\Models\BanCityModel;
use App\Models\CustomWarehouseExpressModel;
use App\Models\CustomWarehouseModel;
use App\Models\DamaijiaWarehouseUserSource;
use App\Models\ExpressModel;
use App\Models\ExpressProductModel;
use App\Models\Product as ProductModel;
use App\Models\UserLevelModel;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller;
use Base;
use Param;
use Product;
use Filter;
use Site;

class ProductController extends BaseController
{
	
	public function getList()
	{
		$req = Base::getRequestJson();
		Base::checkAndDie([
			"filter" => [ Param::OPTIONAL . Param::POST_ACTION_REPLACE_TO_DB_FILTERS . ERROR_INVALID_FILTER,
				"warehouse_id" => Param::OPTIONAL . Param::extra("warehouse.id")
					. Param::IS_INT_ID . ERROR_INVALID_WAREHOUSE_ID, 
			],
			"sort" => Param::OPTIONAL . Param::POST_ACTION_REPLACE_TO_DB_SORTS . ERROR_INVALID_SORT
				. Param::func("Sort::checkSorts", [ "sales" => "product.sales", "create_time" => "product.create_time",
					"weight" => "product.weight", "id" => "product.id", "price" => "site_product.price" ]),
			"range" => Param::IS_RANGE_INT . ERROR_INVALID_RANGE,
		], $req);
		// 没有设置分站价格的商品不显示
		$filter = array_merge($req["filter"], [
			Filter::makeDBFilter("site_product.price", null, Filter::TYPE_EQUAL, true),
			Filter::makeDBFilter("site_product.site_id", $this->_site_id, Filter::TYPE_EQUAL),
			Filter::makeDBFilter("product.status", PRODUCT_STATUS_ONLINE, Filter::TYPE_EQUAL),
			Filter::makeDBFilter("warehouse.status", WARE_HOUSE_STATUS_NORMAL, Filter::TYPE_EQUAL),
		]);
		if($this->_site_id != 1) { //如果是分站  显示分站状态设置的状态 site_product.status 1上架 2下架
			$filter = array_merge($filter, [
				Filter::makeDBFilter("site_product.status", 1, Filter::TYPE_EQUAL),
			]);
		}
		$list = Product::getList($filter, $req["range"], $req["sort"]);
		// 删除thumb字段
		foreach ($list as &$item) {
			unset($item["thumb"]);
		}
		$total = Product::getCount($filter);
		Base::dieWithResponse([
			"index" => $req["range"][0],
			"list" => $list,
			"total" => $total
		]);
	}

//	public function getInfo()
//	{
//		$req = Base::getRequestJson();
//		Base::checkAndDie([
//			"id" => Param::IS_INT_ID . ERROR_INVALID_ID,
//		], $req);
//		//$site_id = Site::getCurrentSiteID();
//		$site_id = $this->_site_id;
//		$info = Product::getInfo($req["id"], $site_id);
//		return $this->responseJson($info);
////		Base::dieWithResponse($info);
//	}
	/**
	 * @author ztt
	 * @param Request $request
	 * 获取商品信息
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getInfo(Request $request)
	{
		$params = $this->validate($request,[
			'id' => 'required',
		]);
		$site_id = $this->_site_id;
		$info = Product::getInfo($params["id"], $site_id);
		$info["name"] = $info["product_alias_name"];
		if(!empty($info["user_source"])) {
			$user_source = explode(",",$info["user_source"]);
			foreach ($user_source as $k=> $v) {
				$source_info[$k]["user_source"] = $v;
				$source_info[$k]["remark"] = "";
			}
			$info["source_info"] = $source_info;
		} else {
			$source_info = DamaijiaWarehouseUserSource::listByWhere(
				["warehouse_id"=>$info["warehouse_id"],"user_source_status"=>1],
				["user_source","remark"]
			);
			$info["source_info"] = $source_info;
		}
		return $this->responseJson($info);
	}

    /**
     * @author ztt
     * @param Request $request
     * 获取商品信息
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInfoV1(Request $request)
    {
        $params = $this->validate($request,[
            'id' => 'required',
        ]);
        $site_id = $this->_site_id;
        $info = Product::getInfo($params["id"], $site_id);
        if(!empty($info["user_source"])) {
            $user_source = explode(",",$info["user_source"]);
            foreach ($user_source as $k=> $v) {
                $source_info[$k]["user_source"] = $v;
                $source_info[$k]["remark"] = "";
            }
            $info["source_info"] = $source_info;
        } else {
            $source_info = DamaijiaWarehouseUserSource::listByWhere(
                ["warehouse_id"=>$info["warehouse_id"],"user_source_status"=>1],
                ["user_source","remark"]
            );
            $info["source_info"] = $source_info;
        }
        //获取商品的发货地和快递(目前只有一个发货地)
        $sendName = "";//发货地名称
        $expressName = "";//快递名称
        $expressProduct = ExpressProductModel::query()->where("product_id",$params["id"])->first();
        if($expressProduct){
            //根据发货地获取快递
            $express = ExpressModel::query()->find($expressProduct["damaijia_express_id"]);
            if($express){
                $sendName = $express["express_alias_name"];
                $customWarehouseRelation = CustomWarehouseExpressModel::query()->where("express_id",$expressProduct["damaijia_express_id"])->first();
                if($customWarehouseRelation){
                    $customWarehouse = CustomWarehouseModel::query()->find($customWarehouseRelation["custom_warehouse_id"]);
                    $expressName = $customWarehouse?$customWarehouse["custom_warehouse_name"]:"";
                }
            }
        }
        $info["send_name"] = $sendName;
        $info["express_img_url"] = isset(ExpressEnum::EXPRESS_MAP[$expressName])?ExpressEnum::EXPRESS_MAP[$expressName]:"";
		$ban_city = BanCityModel::getBanCityByExpressIdV1($expressProduct["damaijia_express_id"]);
		$ban_citys=[];
		if($ban_city->count()) {
			foreach ($ban_city as $kk=>$vv) {
				$city_names = explode(",",$vv["city_names"]);
				foreach ($city_names as $k=>$v) {
					$ban["province_city"] = $v;
					$ban["ban_type"] =$vv["ban_type"];
					$ban["remark"] =$vv["remark"];
					$ban_citys[] = $ban;
				}
			}
		}
		
		$info["ban_city"] = $ban_citys;
		if($site_id == 1) {
			$info["product_price"] = $info["profit"]+$info["price"];
		} else {
			$user_id = \App\Models\Site::getById($site_id)->user_id;
			$info["product_price"] = ProductLogic::getSiteProductCostPrice($info["id"],$user_id,0)+$info["profit"];
		}
		$express_id = ExpressProductModel::query()->where("product_id",$info["id"])->value("damaijia_express_id");
		if(!empty($this->_user_info)) {
			$info["expressPrice"] = ExpressLogic::getUserExpressPriceLogic($this->_user_info["id"],$express_id)->price;
		}else {
			$info["expressPrice"] = "";
		}
		unset($info["profit"]);
		$siteLevel = UserLevelModel::query()->where("status",1)->where("site_id",$this->_site_id)->get();
		$info["level_money"] = $siteLevel;
		$expressInfo  = ExpressModel::getById($express_id);
		$info["is_support_encry"] = $expressInfo["is_support_encry"];
        return $this->responseJson($info);
    }

	public function getWarehouseList()
	{
		$req = Base::getRequestJson();
		Base::checkAndDie([
			"product_id" => Param::IS_INT_ID . ERROR_INVALID_PRODUCT_ID,
		], $req);
		$list = Product::getWarehouseList($req["product_id"]);
		Base::dieWithResponse([
			"list" => $list,
		]);
	}

	/**
	 * @author ztt
	 * 同步数据信息
	 */
	public function syncDamaijiaWarehouseUserSource() {
		$data = Warehouse::query()->get();
		$arr = ["taobao","tmall","jd","pdd","other"];
		foreach ($data as $k=>$v) {
			foreach ($arr as $vv) {
				$source = DamaijiaWarehouseUserSource::getByWhere(["user_source"=>$vv,"warehouse_id"=>$v["id"]]);
				if(!$source) {
					$source_arr["warehouse_id"]=$v["id"];
					$source_arr["user_source"]=$vv;
					DamaijiaWarehouseUserSource::create($source_arr);
				}
			}
		}
	}

    /**
     * @SWG\Post(
     *     path="/v1/product_get_list",
     *     tags={"商品管理"},
     *     summary="商品列表接口",
     *     description="商品列表接口",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         required=true,
     *          @SWG\Schema(
     *            @SWG\Property(
     *                  property="warehouseId",
     *                  type="int",
     *                  description="快递id",
     *              ),
     *            @SWG\Property(
     *                  property="expressSendId",
     *                  type="int",
     *                  description="发货地ID",
     *              ),
     *            @SWG\Property(
     *                  property="page",
     *                  type="int",
     *                  description="第几页",
     *              ),
     *            @SWG\Property(
     *                  property="pageSize",
     *                  type="int",
     *                  description="每页数量",
     *              ),
     *            @SWG\Property(
     *                  property="sort",
     *                  type="array",
     *                  description="排序方式",
     *                  @SWG\Items(
     *                      @SWG\Property(
     *                      property="field",
     *                      type="string",
     *                      description="排序字段"
     *                      ),
     *                      @SWG\Property(
     *                      property="reverse",
     *                      type="bool",
     *                      description="排序方式(true正序false倒序)"
     *                      )
     *                  )
     *              )
     *          )
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="success",
     *          @SWG\Schema(ref="#/definitions/ProductListV1ResultBean")
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="fail",
     *         @SWG\Schema(ref="#/definitions/ErrorBean")
     *     )
     * )
     */
    public function getListV1()
    {
//        $data = ProductLogic::getListV1Logic($this->_site_id);
		$data = ProductLogic::getListV2Logic($this->_site_id,$this->_user_info);
        return $this->responseJson($data);
	}
}
