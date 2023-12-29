<?php


namespace App\Http\Controllers;


use App\Http\Logic\InviteLogic;

class InviteController extends BaseController
{
	/**
	 * @SWG\Get(
	 *     path="/recordUserInviteCount",
	 *     tags={"个人中心"},
	 *     summary="复制掉此接口",
	 *     description="复制掉此接口 记录用户复制次数",
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
	public function recordUserInviteCount()
	{
		$result = InviteLogic::recordUserInviteCount($this->_user_info["id"],$this->_site_id);
		return $this->responseJson($result);
	}

	/**
	 * @SWG\Post(
	 *     path="/listInviteLog",
	 *     tags={"个人中心"},
	 *     summary="邀请明细",
	 *     description="邀请明细",
	 *     produces={"application/json"},
	 *     @SWG\Parameter(
	 *          name="page",
	 *          in="query",
	 *          description="页",
	 *          type="string"
	 *     ),
	 *     @SWG\Parameter(
	 *          name="pageSize",
	 *          in="query",
	 *          description="每页数量",
	 *          type="string"
	 *     ),
	 *     @SWG\Parameter(
	 *          name="mobile",
	 *          in="query",
	 *          description="粉丝账号",
	 *          type="string"
	 *     ),
	 *     @SWG\Parameter(
	 *          name="create_time",
	 *          in="query",
	 *          description="注册时间  范围查询 数组格式「开始时间，结束时间」",
	 *          type="string"
	 *     ),
	 *     @SWG\Response(
	 *         response="200",
	 *         description="success",
	 *         @SWG\Schema(
	 *              @SWG\Property(
	 *                  property="total_count",
	 *                  type="string",
	 *                  description="总注册数量"
	 *              ),
	 *              @SWG\Property(
	 *                  property="today_count",
	 *                  type="string",
	 *                  description="今天注册数量"
	 *              ),
	 *              @SWG\Property(
	 *                  property="count",
	 *                  type="string",
	 *                  description="查询总条数"
	 *              ),
	 *              @SWG\Property(
	 *                  property="list.id",
	 *                  type="string",
	 *                  description="编号"
	 *              ),
	 *              @SWG\Property(
	 *                  property="list.invited_user.mobile",
	 *                  type="string",
	 *                  description="粉丝账号"
	 *              ),
	 *              @SWG\Property(
	 *                  property="list.create_time",
	 *                  type="string",
	 *                  description="注册时间"
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
	public function listInviteLog()
	{
		$result = InviteLogic::listInviteLog($this->_user_info["id"]);
		return $this->responseJson($result);
	}
}
