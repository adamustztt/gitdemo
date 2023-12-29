<?php

namespace App\Http\Controllers\External;

use App\Enums\ErrorEnum;
use App\Helper\WhereUtil;
use App\Http\Logic\External\OrderLogic;
use App\Models\OrderConsignee;
use App\Models\Product;
use App\Models\UserOrder as UO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Laravel\Lumen\Routing\Controller;
use Base;
use Param;
use UserOrder;
use App\Helper\CommonUtil;
use App\Http\Controllers\BaseController;
use OrderConsignee as OrderConsigneeCheck;

class OrderController extends BaseController
{
	/**
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 * @throws \App\Exceptions\ApiException
	 */
	public function create(Request $request)
	{
		CommonUtil::throwException(ErrorEnum::ERROR_ENABLE_API);
//		$req = Base::getRequestJson();
		$params = $this->validate($request, [
			'site_order_id' => 'required',
			'product_id' => 'required|int',
			'warehouse_id' => 'required|int',
			'product_number' => 'required|int|min:1',
			'source' => 'required|string|min:1|max:10',
			'consignees' => 'required|array',

			'consignees.*.consignee' => 'required|string',
			'consignees.*.mobile' => 'required|phone',
			'consignees.*.province' => 'required|string',
			'consignees.*.city' => 'required|string',
			'consignees.*.district' => 'required|string',
			'consignees.*.address' => 'required|string',
			'consignees.*.platform_order_sn' => 'required|string',
//			'remark' => 'required|string|max:255',
		]);
		// 验证包裹信息
		if (OrderConsigneeCheck::check($params['consignees']) === false) {
			CommonUtil::throwException(ErrorEnum::ERROR_EXT_INVALID_CONSIGNEE);
		}

		$data = UserOrder::createFromApi($params['site_order_id'], $request->user_id, $request->site_id, $params['product_id'], $params['product_number'],
			$params['warehouse_id'], $params['source'], $params['consignees'], $params['remark']);
//		if ($code === ERROR_NOT_ENOUGH_MONEY) {
//			CommonUtil::throwException(ErrorEnum::ERROR_EXT_BALANCE_NOT_ENOUGH);
//		}
//		if ($code === false) {
//			CommonUtil::throwException(ErrorEnum::ERROR_EXT_UNKNOWN);
//		}
		return $this->responseJson($data);
	}

	/**
	 * @SWG\Post(
	 *     path="/external/v1/order_create",
	 *     tags={"API接口管理"},
	 *     summary="新版创建订单接口",
	 *     description="新版创建订单接口",
	 *     produces={"application/json"},
	 *     @SWG\Parameter(
	 *         name="body",
	 *         in="body",
	 *         required=true,
	 *          @SWG\Schema(
	 *            @SWG\Property(
	 *                  property="product_id",
	 *                  type="int",
	 *                  description="商品ID",
	 *              ),
	 *            @SWG\Property(
	 *                  property="site_order_id",
	 *                  type="int",
	 *                  description="自定义订单ID",
	 *              ),
	 *            @SWG\Property(
	 *                  property="product_number",
	 *                  type="int",
	 *                  description="下单数量",
	 *              ),
	 *            @SWG\Property(
	 *                  property="remark",
	 *                  type="int",
	 *                  description="备注",
	 *              ),
	 *            @SWG\Property(
	 *                  property="source",
	 *                  type="int",
	 *                  description="来源 taobao:淘宝 tmall：天猫 jd：京东 pdd：拼多多 other:其他",
	 *              ),
	 *            @SWG\Property(
	 *                  property="consignees",
	 *                  type="array",
	 *                  description="收货人信息",
	 *                  @SWG\Items(
	 *                      @SWG\Property(
	 *                      property="site_order_consignee_id",
	 *                      type="string",
	 *                      description="自定义包裹id"
	 *                      ),
	 *                      @SWG\Property(
	 *                      property="consignee",
	 *                      type="string",
	 *                      description="收货人"
	 *                      ),
	 *                      @SWG\Property(
	 *                      property="mobile",
	 *                      type="string",
	 *                      description="手机号"
	 *                      ),
	 *                      @SWG\Property(
	 *                      property="province",
	 *                      type="string",
	 *                      description="省份"
	 *                      ),
	 *                      @SWG\Property(
	 *                      property="city",
	 *                      type="string",
	 *                      description="城市"
	 *                      ),
	 *                      @SWG\Property(
	 *                      property="district",
	 *                      type="string",
	 *                      description="区域"
	 *                      ),
	 *                      @SWG\Property(
	 *                      property="address",
	 *                      type="string",
	 *                      description="详细地址"
	 *                      ),
	 *                      @SWG\Property(
	 *                      property="platform_order_sn",
	 *                      type="string",
	 *                      description="第三方平台订单号：淘、猫、京、拼等平台订单编号，如无订单编号可随机填写"
	 *                      )
	 *                  )
	 *              )
	 *          )
	 *     ),
	 *     @SWG\Response(
	 *         response="200",
	 *         description="success",
	 *          @SWG\Schema(
	 *              @SWG\Property(
	 *                  property="order_id",
	 *                  type="string",
	 *                  description="订单ID"
	 *              ),
	 *              @SWG\Property(
	 *                  property="order_sn",
	 *                  type="string",
	 *                  description="订单编号"
	 *              )
	 *          )
	 *     ),
	 *     @SWG\Response(
	 *         response="403",
	 *         description="fail",
	 *         @SWG\Schema(ref="#/definitions/ErrorBean")
	 *     )
	 * )
	 */
	public function createV1()
	{
		$data = OrderLogic::createV1Logic();
		return $this->responseJson($data);
	}

	/**
	 * 支付提交
	 * @param Request $request
	 */
	public function submitPayment(Request $request)
	{
		$this->validate($request, [
			'order_id' => 'required',
		]);
		UserOrder::submitPayment((string)$request['order_id'], $request->user_id, $request->site_id);
		return $this->responseJson();
	}

//    /**
//     * API订单查询接口
//     * @param Request $request
//     * @return \Illuminate\Http\JsonResponse
//     */
//	public function orderList(Request $request)
//    {
//        $this->validate($request, [
//            'filter.create_time'=>'array',
//            'filter.source' => 'string|min:1|max:10',
//            'filter.order_sn' => 'string',
//            'range' => 'array',
//        ]);
//        $where = [];
//        $whereUtil = new WhereUtil($request['filter'],$where);
//        $whereUtil->applyFilter('source','user_order.source');
//        $whereUtil->applyFilter('order_sn','user_order.order_sn');
//        if(!empty($request['filter']['create_time']))
//            $whereUtil->applyDateFilter('create_time','user_order.create_time');
//        $user_order = selectList('user_order','id,order_sn,page_number,source,remark,create_time,total_price');
//        $product = selectList('product','name');
//        $columns = array_merge($user_order,$product);
//        $list = UO::listGetUserOrder($request,$where,$columns);
//        return $this->responseJson([
//            'index' => $request['range'][0],
//            'list' => $list,
//            'total' => $list->total()
//        ]);
//    }
	public function orderList(Request $request)
	{
		$params = $this->validate($request, [
			'filter.create_time' => 'array',
			'filter.source' => 'string|min:1|max:10',
			'filter.order_sn' => 'string',
			'range' => 'array',
		]);
		$user_id = $request->user_id ?? "";
		$date = date("Y-m-d H:i:s", strtotime("-1 month"));
		$query = \App\Models\UserOrder::query();
		if (!empty($params["filter"]["create_time"][0]) && !empty($params["filter"]["create_time"][1])) {
			if ($params["filter"]["create_time"][1] < $date) {
				$params["filter"]["create_time"][1] = $date;
			}
			$query->whereBetween("user_order.create_time", $params["filter"]["create_time"]);
		} else {
			$query->where("user_order.create_time", ">", $date);
		}
		if (!empty($params["filter"]["source"])) {
			$query->where("user_order.source", $params["filter"]["source"]);
		}
		if (!empty($params["filter"]["order_sn"])) {
			$query->where("user_order.order_sn", $params["filter"]["order_sn"]);
		}
		if ($user_id) {
			$query->where("user_order.user_id", $user_id);
		}

		$range = [1, 10];
		if (!empty($params["range"])) {
			$range = $params["range"];
		}
		$user_order = selectList('user_order', 'id,order_sn,page_number,source,remark,create_time,total_price');
		$product = selectList('product', 'name');
		$columns = array_merge($user_order, $product);
		$query->join('product', 'product.id', 'user_order.product_id')
			->select($columns);
		$count = $query->count();
		$list = $query->offset($range[0] - 1)->limit($range[1])
			->get();
		return $this->responseJson([
			'index' => $request['range'][0],
			'list' => $list,
			'total' => $count
		]);
	}

	/**
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 * @author ztt
	 *
	 * 同步第三方订单 返回包裹列表状态
	 */
	public function listOrderConsignee(Request $request)
	{
		$param = $this->validate($request, [
			'site_order_consignee_ids' => 'array'
		]);
		$site_order_consignee_ids = $param['site_order_consignee_ids'];
		$user_id = $request->user_id ?? "";
		$order_ids = OrderConsignee::query()
			->whereIn('site_order_consignee_id', $site_order_consignee_ids)
			->pluck("order_id")->toArray();
		$orderIdMap = \App\Models\UserOrder::query()
			->whereIn("id", $order_ids)
			->where("user_id", $user_id)->pluck("id")->toArray();
		$where[] = [function ($query) use ($site_order_consignee_ids, $orderIdMap) {
			$query->whereIn('site_order_consignee_id', $site_order_consignee_ids);
			$query->whereIn('order_id', $orderIdMap);
		}];
		$list = OrderConsignee::listOrderConsigneeByWhere($where, ['id', 'site_order_consignee_id', 'status', 'express_company_name', 'express_no']);
		return $this->responseJson([
			'list' => $list,
		]);
	}

	// 创建订单
	public function orderCreateEncryption()
	{
		$params = app("request")->all();
		$validator = Validator::make($params, [
			"shop_id" => "required",
			"product_id" => "required",
			"platform_order_sn" => "required",
			"source" => "required",
			"is_deliver" => "" // 0不自动发货 1自动发货
		]);
		if ($validator->fails()) {
			CommonUtil::throwException([422, $validator->errors()->first()]);
		}
		$product = Product::query()->where("id",$params["product_id"])->first();
		$support_entry_source = $product["support_entry_source"];
		$support_entry_source_arr = explode(",",$support_entry_source);
		if(!in_array($params["source"],$support_entry_source_arr)) {
			CommonUtil::throwException([422, "该商品密文下单不支持该平台"]);
		}
		$data = OrderLogic::orderCreateEncryption();
		return $this->responseJson($data);
	}
}
