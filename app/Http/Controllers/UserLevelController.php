<?php


namespace App\Http\Controllers;


use App\Http\Logic\UserLevelLogic;
use App\Models\ExpressModel;

class UserLevelController extends BaseController
{
	/**
	 * @SWG\Post(
	 *     path="/listUserLevel",
	 *     tags={"个人中心"},
	 *     summary="会员专属特权",
	 *     description="会员专属特权",
	 *     produces={"application/json"},
	 *     @SWG\Response(
	 *         response="200",
	 *         description="success",
	 *         @SWG\Schema(
	 *              @SWG\Property(
	 *                  property="img",
	 *                  type="string",
	 *                  description="等级图片"
	 *              ),
	 *              @SWG\Property(
	 *                  property="level_name",
	 *                  type="string",
	 *                  description="等级名称"
	 *              ),
	 *              @SWG\Property(
	 *                  property="level_condition",
	 *                  type="string",
	 *                  description="等级条件 【邀请数量 最大充值金额】"
	 *              ),
	 *              @SWG\Property(
	 *                  property="preferential_amount",
	 *                  type="string",
	 *                  description="快递价格"
	 *              ),
	 *              @SWG\Property(
	 *                  property="tool_preferential_img",
	 *                  type="string",
	 *                  description="工具优惠图片"
	 *              ),
	 *              @SWG\Property(
	 *                  property="tool_preferential_name",
	 *                  type="string",
	 *                  description="工具优惠名称"
	 *              ),
	 *              @SWG\Property(
	 *                  property="customer_service",
	 *                  type="string",
	 *                  description="专属客服"
	 *              ),
	 *              @SWG\Property(
	 *                  property="tutor_service",
	 *                  type="string",
	 *                  description="专属运营导师"
	 *              ),
	 *              @SWG\Property(
	 *                  property="complaint_service",
	 *                  type="string",
	 *                  description="投诉建议"
	 *              ),
	 *              @SWG\Property(
	 *                  property="new_product_service",
	 *                  type="string",
	 *                  description="新产品优先使用"
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
	public function listUserLevel()
	{
		$result = UserLevelLogic::listUserLevel($this->_site_id,$this->_user_info["id"]);
		return $this->responseJson($result);
	}
	/**
	 * @SWG\Post(
	 *     path="/listUserLevelExpress",
	 *     tags={"个人中心"},
	 *     summary="会员专属快递价格",
	 *     description="会员专属快递价格",
	 *     produces={"application/json"},
	 *     @SWG\Response(
	 *         response="200",
	 *         description="success",
	 *      	@SWG\Schema(ref="#/definitions/SuccessBean")
	 *     ),
	 *     @SWG\Response(
	 *         response="403",
	 *         description="fail",
	 *         @SWG\Schema(ref="#/definitions/ErrorBean")
	 *     )
	 * )
	 */
	public function listUserLevelExpress()
	{
		$result = UserLevelLogic::listUserLevelExpress($this->_site_id,$this->_user_info["id"]);
		return $this->responseJson($result);
		
	}
}
