<?php


namespace App\Http\Controllers;


use App\Http\Logic\DamaijiaUserDeliverShopLogic;
use App\Http\Logic\UserShopCallbackLogic;
use App\Http\Utils\BaseUtil;
use App\Http\Utils\LoggerFactoryUtil;

class DamaijiaUserDeliverShopController extends BaseController
{
	/**
	 * @SWG\Post(
	 *     path="/listUserShop",
	 *     tags={"店铺管理"},
	 *     summary="店铺列表",
	 *     description="店铺列表",
	 *     produces={"application/json"},
	 *     @SWG\Parameter(
	 *         name="body",
	 *         in="body",
	 *         required=true,
	 *          @SWG\Schema(
	 *          )
	 *     ),
	 *     @SWG\Response(
	 *         response="200",
	 *         description="success",
	 *          @SWG\Schema(ref="#/definitions/ListUserShopBean")
	 *     ),
	 *     @SWG\Response(
	 *         response="403",
	 *         description="fail",
	 *         @SWG\Schema(ref="#/definitions/ErrorBean")
	 *     )
	 * )
	 */
	public function listUserShop()
	{
		$data = DamaijiaUserDeliverShopLogic::listUserShop();
		return  $this->responseJson($data);
	}
	/**
	 * @SWG\Post(
	 *     path="/getUserShop",
	 *     tags={"店铺管理"},
	 *     summary="店铺详情",
	 *     description="店铺详情",
	 *     produces={"application/json"},
	 *     @SWG\Parameter(
	 *         name="body",
	 *         in="body",
	 *         required=true,
	 *          @SWG\Schema(
	 *     			@SWG\Property(
	 *                  property="id",
	 *                  type="string",
	 *                  description="店铺数据id 非必填",
	 *              )
	 * 			)
	 *     ),
	 *     @SWG\Response(
	 *         response="200",
	 *         description="success",
	 *          @SWG\Schema(ref="#/definitions/ListUserShopBean")
	 *     ),
	 *     @SWG\Response(
	 *         response="403",
	 *         description="fail",
	 *         @SWG\Schema(ref="#/definitions/ErrorBean")
	 *     )
	 * )
	 */
	public function getUserShop()
	{
		$data = DamaijiaUserDeliverShopLogic::getUserShop();
		return  $this->responseJson($data);
	}
	/**
	 * @SWG\Post(
	 *     path="/setUserShop",
	 *     tags={"店铺管理"},
	 *     summary="店铺设置",
	 *     description="店铺设置",
	 *     produces={"application/json"},
	 *     @SWG\Parameter(
	 *         name="body",
	 *         in="body",
	 *         required=true,
	 *          @SWG\Schema(
	 *     			@SWG\Property(
	 *                  property="id",
	 *                  type="string",
	 *                  description="店铺数据id",
	 *              ),
	 *     			@SWG\Property(
	 *                  property="shop_status",
	 *                  type="string",
	 *                  description="店铺状态 1开启 0关闭",
	 *              ),
	 *     			@SWG\Property(
	 *                  property="is_tag",
	 *                  type="string",
	 *                  description="是否标记 1是 0否",
	 *              ),
	 *     			@SWG\Property(
	 *                  property="tag_color",
	 *                  type="string",
	 *                  description="标记颜色 'red','yellow','green','purple','blue'",
	 *              ),
	 *     			@SWG\Property(
	 *                  property="remark",
	 *                  type="string",
	 *                  description="店铺备注",
	 *              ),
	 *          )
	 *     ),
	 *     @SWG\Response(
	 *         response="200",
	 *         description="success",
	 *          @SWG\Schema(ref="#/definitions/ListUserShopBean")
	 *     ),
	 *     @SWG\Response(
	 *         response="403",
	 *         description="fail",
	 *         @SWG\Schema(ref="#/definitions/ErrorBean")
	 *     )
	 * )
	 */
	public function setUserShop()
	{
		$data = DamaijiaUserDeliverShopLogic::setUserShop();
		return  $this->responseJson($data);
	}
	/**
	 * @SWG\Post(
	 *     path="/deleteShop",
	 *     tags={"店铺管理"},
	 *     summary="店铺删除",
	 *     description="店铺删除",
	 *     produces={"application/json"},
	 *     @SWG\Parameter(
	 *         name="body",
	 *         in="body",
	 *         required=true,
	 *          @SWG\Schema(
	 *     			@SWG\Property(
	 *                  property="id",
	 *                  type="string",
	 *                  description="店铺数据id",
	 *              ),
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
	public function deleteShop()
	{
		$data = DamaijiaUserDeliverShopLogic::deleteShop();
		return  $this->responseJson($data);
	}
	/**
	 * @SWG\Post(
	 *     path="/refreshGerUserShop",
	 *     tags={"店铺管理"},
	 *     summary="刷新获取绑定订购店铺信息",
	 *     description="刷新获取绑定订购店铺信息",
	 *     produces={"application/json"},
	 *     @SWG\Parameter(
	 *         name="body",
	 *         in="body",
	 *         required=true,
	 *          @SWG\Schema(
	 *     			@SWG\Property(
	 *                  property="shop_type",
	 *                  type="string",
	 *                  description="店铺类型  tb pdd ",
	 *              )
	 *          )
	 *     ),
	 *     @SWG\Response(
	 *         response="200",
	 *         description="success",
	 *          @SWG\Schema(ref="#/definitions/ListUserShopBean")
	 *     ),
	 *     @SWG\Response(
	 *         response="403",
	 *         description="fail",
	 *         @SWG\Schema(ref="#/definitions/ErrorBean")
	 *     )
	 * )
	 */
	public function refreshGerUserShop()
	{
		$data = DamaijiaUserDeliverShopLogic::refreshGerUserShop();
		return  $this->responseJson($data);
	}
	/**
	 * @SWG\Post(
	 *     path="/authorizationShop",
	 *     tags={"店铺管理"},
	 *     summary="重新授权",
	 *     description="重新授权",
	 *     produces={"application/json"},
	 *     @SWG\Parameter(
	 *         name="body",
	 *         in="body",
	 *         required=true,
	 *          @SWG\Schema(
	 *     			@SWG\Property(
	 *                  property="id",
	 *                  type="string",
	 *                  description="id",
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
	public function authorizationShop()
	{
		$data = DamaijiaUserDeliverShopLogic::authorizationShop();
		return  $this->responseJson($data);
	}

	/**
	 * @SWG\Post(
	 *     path="/getUserShopByProductId",
	 *     tags={"店铺管理"},
	 *     summary="获取商品支持发货店铺",
	 *     description="获取商品支持发货店铺",
	 *     produces={"application/json"},
	 *     @SWG\Parameter(
	 *         name="body",
	 *         in="body",
	 *         required=true,
	 *          @SWG\Schema(
	 *     			@SWG\Property(
	 *                  property="product_id",
	 *                  type="string",
	 *                  description="商品id",
	 *              )
	 *          )
	 *     ),
	 *     @SWG\Response(
	 *         response="200",
	 *         description="success",
	 *          @SWG\Schema(ref="#/definitions/ListUserShopBean")
	 *     ),
	 *     @SWG\Response(
	 *         response="403",
	 *         description="fail",
	 *         @SWG\Schema(ref="#/definitions/ErrorBean")
	 *     )
	 * )
	 */
	public function getUserShopByProductId()
	{
		$data = DamaijiaUserDeliverShopLogic::getUserShopByProductId();
		return  $this->responseJson($data);
	}

	/**
	 * @return \Illuminate\Http\JsonResponse
	 * 快手授权回掉
	 */
	public function ksShopCallback()
	{
		$data = DamaijiaUserDeliverShopLogic::ksShopCallback();
		return  $this->responseJson($data);
	}
	/**
	 * 淘宝店铺授权回掉
	 */
	public function webCallbackShopInfo()
	{
		$data = DamaijiaUserDeliverShopLogic::webCallbackShopInfo();
		return  $this->responseJson($data);
	}
	/**
	 * 获取淘宝店铺订购链接
	 */
	public function getShopOrderLink()
	{
		$uid = $this->_user_info["id"];
		$vtid = base64_encode(env("AT_VTOOL_PROJECT_USER_ID"));
		$auth =  "https://oauth.taobao.com/authorize?response_type=code&client_id=12227239&redirect_uri=https://alitest.hyx123.cn/tb/token/tb?appkey=12227239&state=erp_12470205123_".$vtid."_RE1K_".$uid;
		$order_link = "https://fuwu.taobao.com/ser/detail.htm?spm=a1z13.pc_search_result.1234-fwlb.72.b2125aca17NCy7&service_code=ts-8597";
		return  $this->responseJson(["auth_link"=>$auth,"order_link"=>$order_link]);
	}
	/**
	 * 淘宝店铺授权回掉打单软件3
	 */
	public function webCallbackShopInfo3()
	{
		$data = DamaijiaUserDeliverShopLogic::webCallbackShopInfo3();
		return  $this->responseJson($data);
	}
	/**
	 * 淘宝店铺授权回掉打单软件4
	 */
	public function webCallbackShopInfo4()
	{
		$data = DamaijiaUserDeliverShopLogic::webCallbackShopInfo4();
		return  $this->responseJson($data);
	}
	/**
	 * 获取打单软件4
	 * 订购链接
	 * 授权链接
	 */
	public function tbOrderLink()
	{
		$user_id = $this->_user_info["id"];
		$order_link = "https://fuwu.taobao.com/ser/detail.htm?spm=a1z13.pc_search_result.1234-fwlb.72.b2125aca17NCy7&service_code=ts-15319";
		//dmj-vtool用户ID-大买家用户ID
		$log = new LoggerFactoryUtil(DamaijiaUserDeliverShopController::class);
		
		$p = "dmj-".env("AT_VTOOL_PROJECT_USER_ID")."-".$user_id;
		$log->info("参数：".$p);
		$base64 = base64_encode($p);
		$auth_link="http://www.damaijia168.com/vv/tb_auth_link4?id=".$base64;
		$data =  [
			"order_link"=>$order_link,
			"auth_link"=>$auth_link,
		];
		return $this->responseJson($data);
	}

	/**
	 * @return \Illuminate\Http\RedirectResponse|\Laravel\Lumen\Http\Redirector
	 * 打单软件4授权链接
	 */
	public function tb_auth_link4()
	{
		$params = app("request")->all();
		return redirect("http://tb.print.6669.cn/authentication/tb2?code=ba897853bc55cf5d&tag=".$params["id"]);
	}
	/**
	 * @return \Illuminate\Http\JsonResponse
	 * 获取京东店铺授权链接
	 */
	public function getJdAuthLink()
	{
		$data = UserShopCallbackLogic::getJdAuthLink($this->_user_info["id"]);
		return  $this->responseJson($data);
	}
	/**
	 * 京东店铺授权回掉
	 */
	public function jdCallbackShopInfo()
	{
		$data = UserShopCallbackLogic::jdCallbackShopInfo();
		return  $this->responseJson($data);
	}
	/**
	 * @return \Illuminate\Http\JsonResponse
	 * 抖音授权回掉
	 */
	public function dyShopCallback()
	{
		$data = DamaijiaUserDeliverShopLogic::dyShopCallback();
		return  $this->responseJson($data);
	}
	public function listShopOrder()
	{
		$data = DamaijiaUserDeliverShopLogic::listShopOrder();
		return  $this->responseJson($data);
	}
}
