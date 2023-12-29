<?php
namespace App\Http\Controllers\External;

use App\Helper\WhereUtil;
use App\Http\Controllers\BaseController;
use App\Http\Logic\External\OrderLogic;
use App\Models\OrderConsignee as OrderConsigneeModel;
use Base;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Param;
use Laravel\Lumen\Routing\Controller;
use OrderConsignee AS Package;
use Filter;
use Sort;
use XiuPinJieSync;
use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Models\UserOrder;
use App\Models\OrderConsignee;

class PackageController extends BaseController
{

	public function getList(Request $request) {
		$req = Base::getRequestJson();
//		Base::checkAndDie([
//			'filter' => [Param::OPTIONAL . Param::POST_ACTION_REPLACE_TO_DB_FILTERS . ERROR_INVALID_FILTER,
//				'create_time' => Param::OPTIONAL . Param::extra('user_order.create_time')
//					. Param::IS_RANGE_DATETIME . ERROR_INVALID_DATETIME,
//				'order_id' => Param::OPTIONAL . Param::extra('user_order.id')
//					. Param::IS_INT_ID . ERROR_EXT_INVALID_ORDER_ID,
//				'order_sn' => Param::OPTIONAL . Param::IS_STRING . ERROR_INVALID_ORDER_SN,
//				'warehouse_id' => Param::OPTIONAL . Param::extra('user_order.warehouse_id') . Param::IS_INT_ID .
//					ERROR_INVALID_WAREHOUSE_ID,
//				'channel_id' => Param::OPTIONAL . Param::IS_INT_ID . Param::extra('user_order.channel_id') .
//					ERROR_INVALID_CHANNEL,
//				'mobile' =>  Param::OPTIONAL . Param::IS_INT_MOBILE
//					. Param::extra('order_consignee.mobile') . ERROR_INVALID_MOBILE,
//				'source' => Param::OPTIONAL . Param::isOP('USER_ORDER_SOURCE_') . Param::extra('order_consignee.source')
//					. ERROR_EXT_INVALID_SOURCE,
//				'status' => Param::OPTIONAL . Param::extra('order_consignee.status')
//					. Param::isOP('PACKAGE_STATUS_') . ERROR_INVALID_STATUS
//			],
//			'sort' => Param::OPTIONAL . Param::POST_ACTION_REPLACE_TO_DB_SORTS . ERROR_INVALID_SORT .
//				Param::func('Sort::checkSorts', [ 'id' ]),
//			'range' => Param::IS_RANGE_INT . ERROR_INVALID_RANGE,
//		], $req);
//		$filter = array_merge($req['filter'], [
//			Filter::makeDBFilter('user_order.user_id', $request->user_id, Filter::TYPE_EQUAL)
//		]);
//		$sort = [
//			Sort::makeSort('order_consignee.id', true)
//		];
		//		$list = Package::getList($filter, $req['range']);
//		$total = Package::getCount($filter);
		//		Base::dieWithResponse([
//			'index' => $req['range'][0],
//			'list' => $list,
//			'total' => $total
//		]);
		$data = $this->validate($request, [
			'filter.create_time'=>'array',
			'filter.order_sn'=>'string',
			'filter.mobile'=>'phone',
			'filter.status'=>'string',
			'range' => 'array',
		]);
		$where = [];
		$whereUtil = new WhereUtil($data['filter'],$where);
		$whereUtil->applyFilter('order_sn','order_sn');
		$whereUtil->applyFilter('mobile','order_consignee.mobile');
		$whereUtil->applyFilter('status','order_consignee.status');
		if(!empty($request['filter']['create_time']))
			$whereUtil->applyDateFilter('create_time','user_order.create_time');
		//查询字段
		$product = ['product.name as product_name','product.weight as product_weight'];
		$user_order = selectList('user_order','order_sn,product_number,shipping_fee,price,
		create_time,update_time,id');
		$order_consignee = selectList('order_consignee','consignee,mobile,province,city,district,
		address,express_no,express_company_name,ext_platform_order_sn,additional,status,cancel_reason');
		$field = array_merge($product,$user_order,$order_consignee);
		try {
			$list = UserOrder::listPackagePage($request,$where,$field);
		}catch (\Exception $e){
			CommonUtil::throwException(ErrorEnum::INTERNAL_ERROR);
		}
		$total = $list->total();
		$list = $list->toArray();

		unset($list['first_page_url']);
		unset($list['from']);
		unset($list['last_page']);
		unset($list['last_page_url']);
		unset($list['next_page_url']);
		unset($list['path']);
		unset($list['per_page']);
		unset($list['prev_page_url']);
		unset($list['to']);
		unset($list['total']);

		return $this->responseJson([
			'index' => $request['range'][0],
			'list' => $list,
			'total' => $total
//			'total' => $list->total()
		]);
	}

	/**
	 * 取消发货
	 */
	public function cancel(Request $request)
	{
		$req = Base::getRequestJson();
		$this->validate($request, [
			'package_id'=>'required|string',
		]);
		$site_id = $request->site_id;
		$where = ['site_id'=>$site_id,'site_order_consignee_id'=>$req['package_id']];
		$package_info = OrderConsignee::getInfo($where);
		if ($package_info === null) {
			CommonUtil::throwException(ErrorEnum::ERROR_EXT_INVALID_PACKAGE_ID);
		}
		if($package_info['status'] === PACKAGE_STATUS_CANCELED){
			return $this->responseJson('该包裹已经取消发货');
		}
		try {
			Db::transaction(function()use($package_info){
				// 如果还未发货，直接取消即可
				if ($package_info['status'] === PACKAGE_STATUS_PENDING) {
					Package::shipCancelAndRefund($package_info);
					return $this->responseJson('该包裹取消发货成功');
				}
			});
		}catch (\Exception $exception){
			CommonUtil::throwException(ErrorEnum::ERROR_EXT_CANCEL_PACKAGE_FAILED);
		}
		
		
		switch ($package_info['channel_id']) {
			case 1:
				$ret = XiuPinJieSync::cancelSingleOrder($package_info['id'], $package_info['ext_order_sn']);
				if ($ret === false) {
					CommonUtil::throwException(ErrorEnum::ERROR_EXT_PACKAGE_HAS_DELIVERED);
				}
				break;
			case 2:
				CommonUtil::throwException(ErrorEnum::INTERNAL_ERROR);
				break;
			case 3:

				break;
		}
		return $this->responseJson('该包裹已经取消发货');
	}
	public function cancelV1(Request $request)
	{
		$this->validate($request, [
			'package_id'=>'required|string',
			'user_id'=>'required'
		]);
		$req = OrderLogic::cancelPackage();
		return $this->responseJson([],"取消成功");
	}
	public function getPackageById(Request $request)
	{
		$this->validate($request, [
			'package_id'=>'required',
//			'user_id'=>'required'
		]);
		$params = app("request")->all();
		$package_id = $params['package_id'];
		$order_consignee = OrderConsigneeModel::query()
			->where("id",$package_id)
			->select("site_order_consignee_id","consignee","mobile","province","city","district","address","express_no","status","additional","cancel_reason")
			->first();
			return $this->responseJson($order_consignee);

	}
}
