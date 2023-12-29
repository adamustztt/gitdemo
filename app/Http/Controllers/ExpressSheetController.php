<?php


namespace App\Http\Controllers;


use App\Http\Logic\ExpressSheetLogic;
use Illuminate\Http\Request;

class ExpressSheetController extends BaseController
{
	/**
	 * @SWG\Post(
	 *     path="/submitExpressSheet",
	 *     tags={"订单管理"},
	 *     summary="包裹申请底单",
	 *     description="包裹申请底单",
	 *     produces={"application/json"},
	 *     @SWG\Parameter(
	 *         name="body",
	 *         in="body",
	 *         required=true,
	 *          @SWG\Schema(
	 *            @SWG\Property(
	 *                  property="email",
	 *                  type="string",
	 *                  description="email",
	 *              ),
	 *            @SWG\Property(
	 *                  property="reason",
	 *                  type="string",
	 *                  description="申请原因",
	 *              ),
	 *            @SWG\Property(
	 *                  property="package_id",
	 *                  type="string",
	 *                  description="包裹ID",
	 *              )
	 *          )
	 *     ),
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
	public function submitExpressSheet(Request $request)
	{
		$params  = $this->validate($request,[
			"email"=>"required",
			"reason"=>"required",
			"package_id"=>"required"
		]);
		$result = ExpressSheetLogic::submitExpressSheet($this->_user_info);
		return $this->responseJson($result);
	}
}
