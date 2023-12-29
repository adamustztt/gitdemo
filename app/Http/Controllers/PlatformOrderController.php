<?php


namespace App\Http\Controllers;


use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Http\Logic\PlatformOrderLogic;
use App\Models\BanCityModel;
use App\Models\ExpressProductModel;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Validator;

class PlatformOrderController extends BaseController
{
	/**
	 * @SWG\Post(
	 *     path="/getPlatformOrder",
	 *     tags={"订单管理"},
	 *     summary="获取电商平台订单列表",
	 *     description="获取电商平台订单列表",
	 *     produces={"application/json"},
	 *     @SWG\Parameter(
	 *         name="body",
	 *         in="body",
	 *         required=true,
	 *          @SWG\Schema(
	 *     			@SWG\Property(
	 *                  property="shop_id",
	 *                  type="string",
	 *                  description="店铺ID",
	 *              ),
	 *     			@SWG\Property(
	 *                  property="product_id",
	 *                  type="string",
	 *                  description="商品id",
	 *              ),
	 *     			@SWG\Property(
	 *                  property="shop_type",
	 *                  type="string",
	 *                  description="店铺类型 tb pdd ks",
	 *              ),
	 *     			@SWG\Property(
	 *                  property="page",
	 *                  type="string",
	 *                  description="第几页  每页固定返回20条",
	 *              ),
	 * 			)
	 *     ),
	 *     @SWG\Response(
	 *         response="200",
	 *         description="success",
	 *          @SWG\Schema(ref="#/definitions/ListPlatformOrderBean")
	 *     ),
	 *     @SWG\Response(
	 *         response="403",
	 *         description="fail",
	 *         @SWG\Schema(ref="#/definitions/ErrorBean")
	 *     )
	 * )
	 */
	public function getPlatformOrder()
	{
		$params = app("request")->all();
		$shop_type = $params["shop_type"];
		$shop_id = $params["shop_id"];
		$redisKey = $shop_type.$shop_id."getPlatformOrder";

		/**
		 * @var \Redis $redis
		 */
		$redis = app('redis');
		if ($redis->get($redisKey)) {
			CommonUtil::throwException([500,"请勿频繁操作"]);
		}
		try {
			$data = PlatformOrderLogic::getPlatformOrder();
		} catch (\Exception $exception) {
			$redis->set($redisKey, 1,["NX","EX"=>300]);
			CommonUtil::throwException([$exception->getCode(),$exception->getMessage()]);
		}
		if(empty($data["list"])) {
			$redis->set($redisKey, 1,["NX","EX"=>300]);
			CommonUtil::throwException([500,"当前暂没有待发货订单"]);
		}
		return $this->responseJson($data);
	}
	/**
	 * @SWG\Post(
	 *     path="/entryCreateOrder",
	 *     tags={"订单管理"},
	 *     summary="密文下单",
	 *     description="密文下单",
	 *     produces={"application/json"},
	 *     @SWG\Parameter(
	 *         name="body",
	 *         in="body",
	 *         required=true,
	 *          @SWG\Schema(
	 *     			@SWG\Property(
	 *                  property="shop_id",
	 *                  type="string",
	 *                  description="店铺ID",
	 *              ),
	 *     			@SWG\Property(
	 *                  property="source",
	 *                  type="string",
	 *                  description="下单平台类型 去店铺里的类型",
	 *              ),
	 *     			@SWG\Property(
	 *                  property="product_id",
	 *                  type="string",
	 *                  description="商品id",
	 *              ),
	 *     	 		@SWG\Property(
	 *                  property="consignees",
	 *                  type="string",
	 *                  description="发货包裹信息 内容取拉取返回数据内容",
	 *      			@SWG\Items(
	 *                      @SWG\Property(
	 *                      	property="ext_platform_order_sn",
	 *                      	type="string",
	 *                      	description="第三方平台编号"
	 *                      ),
	 *                      @SWG\Property(
	 *                      	property="consignee",
	 *                      	type="string",
	 *                      	description="收货人"
	 *                      ),
	 *                      @SWG\Property(
	 *                      	property="consignee_mask",
	 *                      	type="string",
	 *                      	description="收货人脱敏"
	 *                      ),
	 *                      @SWG\Property(
	 *                      	property="mobile",
	 *                      	type="string",
	 *                      	description="手机号"
	 *                      ),
	 *                      @SWG\Property(
	 *                      	property="mobile_mask",
	 *                      	type="string",
	 *                      	description="手机号脱敏"
	 *                      ),
	 *                      @SWG\Property(
	 *                      	property="province",
	 *                      	type="string",
	 *                      	description="省份"
	 *                      ),
	 *                      @SWG\Property(
	 *                      	property="city",
	 *                      	type="string",
	 *                      	description="城市"
	 *                      ),
	 *                      @SWG\Property(
	 *                      	property="district",
	 *                      	type="string",
	 *                      	description="区域"
	 *                      ),
	 *                      @SWG\Property(
	 *                      	property="address",
	 *                      	type="string",
	 *                      	description="详细地址"
	 *                      ),
	 *                      @SWG\Property(
	 *                      	property="address_mask",
	 *                      	type="string",
	 *                      	description="详细地址脱敏数据"
	 *                      ),
	 *                      @SWG\Property(
	 *                      property="oaid",
	 *                      type="string",
	 *                      description="oaid 淘宝密文"
	 *                      )
	 *                  )
	 *              ),

	 * 			)
	 *     ),
	 *     @SWG\Response(
	 *         response="200",
	 *         description="success",
	 *          @SWG\Schema(ref="#/definitions/ListPlatformOrderBean")
	 *     ),
	 *     @SWG\Response(
	 *         response="403",
	 *         description="fail",
	 *         @SWG\Schema(ref="#/definitions/ErrorBean")
	 *     )
	 * )
	 */
	public function entryCreateOrder()
	{
		$params = app("request")->all();
		$validator = Validator::make($params, [
			"shop_id" => "required",
			"product_id" => "required",
			"source" => "required",
			"consignees" => "required"
		]);
		if ($validator->fails()) {
			CommonUtil::throwException([422, $validator->errors()->first()]);
		}
		foreach ($params["consignees"] as $consignee) {
			$validator = Validator::make($consignee, [
				"consignee" => "required",
				"ext_platform_order_sn" => "required",
				"mobile" => "required",
				"address" => "required",
				"province" => "required",
				"city" => "required",
				"district" => "required"
			]);
			if ($validator->fails()) {
				CommonUtil::throwException([422, $validator->errors()->first()]);
			}
		}
		$consignees = $params["consignees"];
		$product_id = $params["product_id"];
		$product_info = Product::getById($product_id);
		$warehouse_info = WareHouse::getById($product_info["warehouse_id"]);
		foreach ($consignees as $consignee) {
			$disable_province=["新疆","西藏","香港","澳门","台湾"];
			foreach($disable_province as $key){
				if(strstr($consignee['province'],$key)){
					CommonUtil::throwException([180,"禁发地址：新疆，西藏，香港，澳门，台湾"]);
				}
			}
			//如果是礼速通（id=8）的 顺丰（id=6）  有一些省份不支持发货
			if(($warehouse_info['channel_id'] == 8) && ($warehouse_info['ext_express_id'] == 6) ){
				$disable_province=["辽宁", "吉林", "黑龙江", "青海", "甘肃", "宁夏", "内蒙古"];
				foreach($disable_province as $key){
					if(strstr($consignee['province'],$key)){
						CommonUtil::throwException([180,"(顺丰)禁发地址：辽宁，吉林，黑龙江，青海，甘肃，宁夏，内蒙，新疆，西藏，香港，澳门，台湾"]);
					}
				}
			}
			$express_id = ExpressProductModel::query()->where("product_id",$product_info["product_id"])->value("damaijia_express_id");
			$ban_address = BanCityModel::getBanAddressExpress($express_id,1,$consignee["province"]);
			if(!$ban_address) {
				$ban_address = BanCityModel::getBanAddressExpress($express_id,2,$consignee["province"],$consignee["city"]);
			}
			if(!$ban_address) {
				$ban_address = BanCityModel::getBanAddressExpress($express_id,3,$consignee["province"],$consignee["city"],$consignee["district"]);
			}
			if($ban_address) {
				CommonUtil::throwException(ErrorEnum::ERROR_BAN_CITY);
			}
		}
		$data = PlatformOrderLogic::entryCreateOrder();
		return $this->responseJson($data);
	}

	public function getPlatformOrderV1()
	{
		$params = app("request")->all();
		$shop_type = $params["shop_type"];
		$shop_id = $params["shop_id"];
		$redisKey = $shop_type.$shop_id."getPlatformOrderV1";

		/**
		 * @var \Redis $redis
		 */
		$redis = app('redis');
		if ($redis->get($redisKey)) {
			CommonUtil::throwException([500,"请勿频繁操作"]);
		}
		try {
			$data = PlatformOrderLogic::getPlatformOrderV1();
		} catch (\Exception $exception) {
			$redis->set($redisKey, 1,["NX","EX"=>300]);
			CommonUtil::throwException([$exception->getCode(),$exception->getMessage()]);
		}
		return $this->responseJson($data);
	}
	public function getPlatformOrderV3()
	{
		$data = PlatformOrderLogic::getPlatformOrderV3();
		return $this->responseJson($data);
	}
}
