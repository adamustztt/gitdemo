<?php
namespace App\Http\Controllers;

use App\Enums\UserBalanceLogEnum;
use App\Http\Logic\UserBalanceLogic;
use Base;
use Illuminate\Http\Request;
use Param;
use UserBalanceLog;
use App\Models\UserBalanceLog as UserBalanceLogModel;
use Filter;

class UserBalanceController extends BaseController
{
//	public function getList() {
//		$req = Base::getRequestJson();
//		Base::checkAndDie([
//			'filter' => [ Param::OPTIONAL . Param::POST_ACTION_REPLACE_TO_DB_FILTERS . ERROR_INVALID_FILTER,
//				'type' => Param::OPTIONAL . Param::extra('user_balance_log.type')
//					. Param::isOP('USER_BALANCE_TYPE_') . ERROR_INVALID_TYPE,
//				'create_time' => Param::OPTIONAL . Param::IS_RANGE_DATETIME
//					. Param::extra('user_balance_log.create_time') . ERROR_INVALID_DATETIME
//			],
//			'sort' => Param::OPTIONAL . Param::POST_ACTION_REPLACE_TO_DB_SORTS . ERROR_INVALID_SORT .
//				Param::func('Sort::checkSorts', [ 'id' => 'user_balance_log.id' ]),
//			'range' => Param::IS_RANGE_INT . ERROR_INVALID_RANGE,
//		], $req);
//		$filter = array_merge($req['filter'], [
//			Filter::makeDBFilter('user_balance_log.user_id', $this->_user_info['id'], Filter::TYPE_EQUAL)
//		]);
//		$list = UserBalanceLog::getList($filter, $req['range'], $req['sort']);
//		$total = UserBalanceLog::getCount($filter);
//		Base::dieWithResponse([
//			'index' => $req['range'][0],
//			'list' => $list,
//			'total' => $total
//		]);
//	}
	public function getList(Request $request) {
		$params = $request->all();
		$user_id = $this->_user_info['id'];
		$query =  UserBalanceLogModel::query()->where(["user_id"=>$user_id])->where("status",1)->orderBy("id","desc");
		if(!empty($params["filter"]["type"])) {
			$query->where("type",$params["filter"]["type"]);
		}
		if(!empty($params["filter"]["create_time"])) {
			$query->whereBetween("create_time",$params["filter"]["create_time"][0],$params["filter"]["create_time"][1]);
		}
		$total = $query->count();
		$range = $params["range"];
		$list = $query->offset($range[0])->limit(10)->get();
		$date = date("Y-m-d H:i:s",time()-3600*24*90);
		foreach ($list as $k=>$v) {
			$list[$k]["type_name"] = UserBalanceLogEnum::CHANGE_TYPE[$v["type"]];
			if((strpos($v["additional"],'工具扣款') !==false) && $date > $v["create_time"]){
				$v["additional"] = "工具扣款";
			}
		}
		return $this->responseJson([
					'index' => $params["range"][0],
					'list' => $list,
					'total' => $total
				]);
	}
    /**
     * @SWG\Get(
     *     path="/v1/balance_get_list",
     *     tags={"个人中心管理"},
     *     summary="点券明细列表接口",
     *     description="点券明细列表接口",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *          name="page",
     *          in="query",
     *          description="第几页",
     *          type="string"
     *     ),
     *     @SWG\Parameter(
     *          name="pageSize",
     *          in="query",
     *          description="每页数量",
     *          type="string"
     *     ),
     *     @SWG\Parameter(
     *          name="startTime",
     *          in="query",
     *          description="开始时间",
     *          type="string"
     *     ),
     *     @SWG\Parameter(
     *          name="endTime",
     *          in="query",
     *          description="结束时间",
     *          type="string"
     *     ),
     *     @SWG\Parameter(
     *          name="type",
     *          in="query",
     *          description="交易类型",
     *          type="string"
     *     ),
     *     @SWG\Parameter(
     *          name="tradeNumber",
     *          in="query",
     *          description="变更编号",
     *          type="string"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="success",
     *          @SWG\Schema(ref="#/definitions/GetUserBalanceListV1ResultBean")
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
        $data = UserBalanceLogic::getListV1Logic($this->_user_info['id']);
        return $this->responseJson($data);
	}/**
 * @SWG\Get(
 *     path="getBalanceLogType",
 *     tags={"个人中心管理"},
 *     summary="点券明细-变更类型",
 *     description="点券明细-变更类型",
 *     produces={"application/json"},
 *     @SWG\Response(
 *         response="200",
 *         description="success",
 *          @SWG\Schema(ref="#/definitions/SuccessBean")
 *     ),
 *     @SWG\Response(
 *         response="403",
 *         description="fail",
 *         @SWG\Schema(ref="#/definitions/ErrorBean")
 *     )
 * )
 */
	public function getBalanceLogType()
	{
		$data = [
			["type"=>"c","type_name"=>"充值"],
			["type"=>"p","type_name"=>"支出"],
			["type"=>"r","type_name"=>"退款"],
			["type"=>"i","type_name"=>"后台添加"],
			["type"=>"d","type_name"=>"后台减少"]
		];
		return $this->responseJson($data);
	}
}
