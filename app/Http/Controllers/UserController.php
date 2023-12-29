<?php
namespace App\Http\Controllers;

use App\Enums\ErrorEnum;
use App\Models\UserBalanceLog;
use App\Models\UserInviteLogModel;
use App\Models\UserLevelModel;
use App\Models\UserShopModel;
use Base;
use Illuminate\Http\Request;
use Param;
use User;
use Site;
use App\Helper\CommonUtil;
use App\Models\Site as ST;
use App\Models\User as UserModel;

class UserController extends BaseController
{
	/**
	 * @SWG\Post(
	 *     path="/user_get_info",
	 *     tags={"个人中心"},
	 *     summary="获取用户信息",
	 *     description="获取用户信息",
	 *     produces={"application/json"},
	 *     @SWG\Response(
	 *         response="200",
	 *         description="success",
	 *         @SWG\Schema(
	 *              @SWG\Property(
	 *                  property="id",
	 *                  type="string",
	 *                  description="ID"
	 *              ),
	 *              @SWG\Property(
	 *                  property="max_recharge",
	 *                  type="string",
	 *                  description="单次最大充值金额  点券"
	 *              ),
	 *              @SWG\Property(
	 *                  property="user_level.level_name",
	 *                  type="string",
	 *                  description="用户等级"
	 *              ),
	 *              @SWG\Property(
	 *                  property="balance",
	 *                  type="string",
	 *                  description="余额"
	 *              ),
	 *              @SWG\Property(
	 *                  property="mobile",
	 *                  type="string",
	 *                  description="登录账号"
	 *              ),
	 *       		@SWG\Property(
	 *                  property="invite_code",
	 *                  type="string",
	 *                  description="邀请码"
	 *              ),
	 *       		@SWG\Property(
	 *                  property="is_relation_shop",
	 *                  type="string",
	 *                  description="1已关联店铺  0 未关联店铺"
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
	public function getInfo()
	{
		$user_info = UserModel::query()
			->where("id",$this->_user_info['id'])
			->with("userLevel:id,level_name")
			->with("site:id")
			->first();
		$user_info["fans_count"] = UserInviteLogModel::query()->where("invite_user_id",$user_info["id"])->count();
		$max_recharge = UserBalanceLog::query()->where(["user_id"=>$user_info["id"],"type"=>"c"])->max("amount");
		$user_info["max_recharge"] = empty($max_recharge) ? 0 : $max_recharge;
		$shop = UserShopModel::query()
			->where("authorization_from",2)
			->where("is_delete",0)
			->where("user_id",$this->_user_info['id'])->first();
		$user_info["is_relation_shop"] = $shop ? 1 : 0;
		$recharge = UserBalanceLog::query()->where(["user_id"=>$user_info["id"],"type"=>"c"])->first();
		$user_info["is_recharge"] = empty($recharge) ? false : true;
		
		return $this->responseJson($user_info);
	}

	/**
	 * 发送验证码
	 */
	/*public function sendCode()
	{
		$req = Base::getRequestJson();
		Base::checkAndDie([
			'mobile' => Param::IS_INT_MOBILE . ERROR_INVALID_MOBILE,
		], $req);
		User::sendCode($req['mobile']);
		Base::dieWithResponse();
	}*/
	/**
	 * @author ztt
	 * 发送验证码
	 */
	public function sendCode(Request $request)
	{
		$param = $this->validate($request, [
			'mobile' => 'required|phone',
			'type' => 'required|int',
            "captcha_key"=>"required",
            "captcha_val"=>"required"
		]);

		//校验图形验证啊嘛
        if(app('captcha')->check($param["captcha_val"], $param["captcha_key"]) === false) {
            //校验失败
            CommonUtil::throwException(ErrorEnum::ERROR_CAPTCHA_VAL);
        }

		switch ($param['type']){
			case 1: // 注册 
				if(UserModel::getUserData(array("mobile"=>$param['mobile'],"site_id"=>$this->_site_id))) {
					CommonUtil::throwException(ErrorEnum::MOBILE_EXISTS);
				}
				break;
			case 2: // 忘记密码
				if(!UserModel::getUserData(array("mobile"=>$param['mobile'],"site_id"=>$this->_site_id))) {
					CommonUtil::throwException(ErrorEnum::USER_NOT_EXISTS);
				}
				break;
			default:
				CommonUtil::throwException(ErrorEnum::VALIDATE_ERROR);
				break;
		}

		//判断

		User::sendCode($param['mobile']);

		return $this->responseJson();
	}

	/**
	 * @author ztt
	 * 用户注册
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 * @throws \App\Exceptions\ApiException
	 */
	public function register(Request $request)
	{
		$params = $this->validate($request, [
			'mobile' => 'required|phone',
			'password' => 'required|string|min:6',
			'verify_code' => 'required|int',
			'aff' => 'int',
			"promote_code"=>"int",
		]);
		$user_info = UserModel::getUserData(["mobile"=>$params['mobile'],"site_id"=>$this->_site_id]);
		if($user_info) {
			CommonUtil::throwException(ErrorEnum::MOBILE_EXISTS);
		}
		// 验证短信验证码是否正确
		$ret = User::verifyCode($params['verify_code'],$params['mobile']);
		if ($ret === false) {
			CommonUtil::throwException(ErrorEnum::CAPTCHA_ERROR);
		}

		// 生成邀请码六位数
		$invite_code = UserModel::inviteCode();
		$data["invite_code"] = $invite_code;
		$data["site_id"] = $this->_site_id;
		$data["domain"] = $this->_domain;
		$data["mobile"] = $params['mobile'];
		$data["password"] = password_hash($params['password'], PASSWORD_DEFAULT);
		$data["username"] = $params['mobile']."_".$this->_site_id;
		$data["status"] = USER_STATUS_NORMAL;
		if(!empty($params["promote_code"])) {
			$data["promote_code"] = $params["promote_code"];
		}
		$level_id = UserLevelModel::query()->where(["site_id"=>$this->_site_id,"invite_count"=>0,"single_recharge"=>0])->value("id");
		$data["level_id"] = $level_id;
		$result = UserModel::create($data);
		// 邀请注册
		if(!empty($params["aff"])) {
			$inviteUser = \App\Models\User::query()->where("invite_code",$params["aff"])->first();
			if($inviteUser) {
				$map["site_id"] = $this->_site_id;
				$map["invite_code"] = $params["aff"];
				$map["invite_user_id"] = $inviteUser->id;
				$map["invited_user_id"] = $result->id;
				UserInviteLogModel::query()->create($map);
			}
		}
		if ($result === false) {
			CommonUtil::throwException(ErrorEnum::INTERNAL_ERROR);
		}
		return $this->responseJson();
	}
//	public function register(Request $request)
//	{
//		$referer = $request->header('referer');
//		$referer = parse_url($referer);
//		$referer = $referer['host'];
//		$req = Base::getRequestJson();
//		$param = $this->validate($request, [
//			'mobile' => 'required|phone',
//			'password' => 'required|string|min:6',
//			'verify_code' => 'required|int',
//			'invite_code' => 'int',
//		]);
//		if(UserModel::isUserRegister($param['mobile'])) {
//			CommonUtil::throwException(ErrorEnum::MOBILE_EXISTS);
//		}
//		// 验证短信验证码是否正确
//		$ret = User::verifyCode($req['verify_code'],$req['mobile']);
//		if ($ret === false) {
//			//Base::dieWithError(ERROR_INVALID_VERIFY_CODE);
//			CommonUtil::throwException(ErrorEnum::CAPTCHA_ERROR);
//		}
//		
//		// 生成邀请码六位数
//		$invite_code = UserModel::inviteCode();
//		// 判断是否是被邀请
//		$site = ST::getWhereData(['domain'=>$referer]);
//		if($req['invite_code']){
//			$user = UserModel::getUserData(['invite_code'=>$req['invite_code']]);
//			if(!$user){
//				CommonUtil::throwException(ErrorEnum::USER_NOT_EXISTS);
//			}
//			$parent = $user['id'];
//			if($user['parent']==0){
//				$parent_path = $user['id'];
//			}else{
//				$parent_path = $user['parent_path'].','.$parent;
//			}
//			$ret = User::registerInternal($invite_code,$site['id'],$req['mobile'], $req['mobile'], $req['password'],
//				   $parent,$parent_path);
//			if ($ret === false) {
//				CommonUtil::throwException(ErrorEnum::MOBILE_ILLEGAL);
//			}
//			return $this->responseJson();
//		}
//		$site_id = empty($site) ? 0 : $site['id'];
//		$ret = User::registerInternal($invite_code,$site_id,$req['mobile'], $req['mobile'], $req['password']);
//		if ($ret === false) {
//			CommonUtil::throwException(ErrorEnum::MOBILE_ILLEGAL);
//		}
//		return $this->responseJson();
//	}

	/**
	 * @author ztt
	 * 用户登录
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 * @throws \App\Exceptions\ApiException
	 */
	public function login(Request $request)
	{
		$params = $this->validate($request, [
			'username' => 'required|string',
			'password' => 'required|string|min:6',
		]);
		$user_info = UserModel::getUserData(["mobile"=>$params['username'],"site_id"=>$this->_site_id]);
		if(empty($user_info)) {
			CommonUtil::throwException(ErrorEnum::USER_NOT_EXISTS);
		}
		if ($user_info['status'] === USER_STATUS_FROZEN) {
			CommonUtil::throwException(ErrorEnum::USER_STATUS_OlREADY_FROZEN);
		}

		$ret = password_verify($params["password"], $user_info['password']);
		if ($ret === false) {
			CommonUtil::throwException(ErrorEnum::PASSWORD_ERROR);
		}
		if (!$ret) {
			CommonUtil::throwException(ErrorEnum::LOGIN_FAILURE);
		}
		$user_token = md5($params['username'] . substr($params['password'], 0, 3) . rand(0, 99999));
		$old_token = app('redis')->get('user_token:'.$user_info['id']);
		if($old_token){
			app('redis')->del('user_info:'.$old_token);
		}
		unset($user_info['password']);
		app('redis')->set('user_info:'.$user_token, json_encode($user_info));
		app('redis')->set('user_token:'.$user_info['id'], $user_token);
//		return $this->responseJson($user_token);
        return response()->json([
            'data' => $user_token,
            'status' => 0,
            "userId"=>$user_info["id"],
            "mobile"=>$params['username']
        ]);
		// Base::dieWithResponse($user_token);
	}
//	public function login1(Request $request)
//	{
//		$req = Base::getRequestJson();
//		$this->validate($request, [
//			'username' => 'required|string',
//			'password' => 'required|string|min:6',
//		]);
//		$ret = User::login($req['username'], $req['password'],$this->_site_id);
//		if (!$ret) {
//			CommonUtil::throwException(ErrorEnum::LOGIN_FAILURE);
//		}
//		$user_token = md5($req['username'] . substr($req['password'], 0, 3) . rand(0, 99999));
//		$user_info = User::getInfoByUserName($req['username']);
//		$old_token = app('redis')->get('user_token:'.$user_info['id']);
//		if($old_token){
//			app('redis')->del('user_info:'.$old_token);
//		}
//		unset($user_info['password']);
//		$user_info['site_id'] = Site::getCurrentSiteID();
//		app('redis')->set('user_info:'.$user_token, json_encode($user_info));
//		app('redis')->set('user_token:'.$user_info['id'], $user_token);
//		return $this->responseJson($user_token);
//		// Base::dieWithResponse($user_token);
//	}
	/**
	 * @author ztt
	 * 更改密码
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 * @throws \App\Exceptions\ApiException
	 */
	public function updateUserPassword(Request $request) {
		$params = $this->validate($request, [
			'mobile' => 'required|phone',
			'password' => 'required|string|min:6',
			'verify_code' => 'required|int',
		]);
		$userInfo = UserModel::getUserData(["mobile"=>$params['mobile'],"site_id"=>$this->_site_id]);
		if(!$userInfo) {
			CommonUtil::throwException(ErrorEnum::USER_NOT_EXISTS);
		}
		// 验证短信验证码是否正确
		$ret = User::verifyCode($params['verify_code'],$params['mobile']);
		if ($ret === false) {
			CommonUtil::throwException(ErrorEnum::CAPTCHA_ERROR);
		}
		$data["password"] = password_hash($params['password'], PASSWORD_DEFAULT);
		$result = UserModel::updateById($userInfo->id,$data);
		if(!$result) {
			CommonUtil::throwException(ErrorEnum::USER_NOT_EXISTS);
		}
		return $this->responseJson();
	}
	public function  set_notify_url(Request $request) {
		$params = $this->validate($request, [
			'notify_url' => 'required|min:1|max:100',
		]);
		$update = UserModel::updateById($this->_user_info['id'],["notify_url"=>$params["notify_url"]]);
		return $this->responseJson();
	}
}
