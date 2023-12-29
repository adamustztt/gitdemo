<?php


namespace App\Http\Controllers;


use App\Http\Logic\UserLevelLogLogic;
use App\Models\UserLevelLogModel;

class UserLevelLogController extends BaseController
{


	/**
	 * @SWG\Post(
	 *     path="/userLevelChangePopup",
	 *     tags={"个人中心"},
	 *     summary="升级弹窗",
	 *     description="升级弹窗",
	 *     produces={"application/json"},
	 *     @SWG\Response(
	 *         response="200",
	 *         description="success",
	 *         @SWG\Schema(
	 *              @SWG\Property(
	 *                  property="level_name",
	 *                  type="string",
	 *                  description="等级名称"
	 *              ),
	 *              @SWG\Property(
	 *                  property="img",
	 *                  type="string",
	 *                  description="等级图片"
	 *              )
 	 *            )
	 *     ),
	 *     @SWG\Response(
	 *         response="403",
	 *         description="fail",
	 *         @SWG\Schema(ref="#/definitions/ErrorBean")
	 *     )
	 * )
	 */
	public function userLevelChangePopup()
	{
		$data = UserLevelLogLogic::userLevelChangePopup($this->_user_info["id"]);
		return $this->responseJson($data);
	}
}
