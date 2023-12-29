<?php
namespace App\Http\Controllers\External;

use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller;
use Base;
use User;
use Param;
use Site;
use App\Models\User as US;

class UserController extends BaseController
{

	/**
	 * 接口用户登录
	 * 
	 */
	public function login(Request $request) {
		$req = Base::getRequestJson();
		$this->validate($request, [
			'app_id' => 'required|string',
			'app_secret' => 'required|string|max:32',
		]);
		$ret = User::apiUserLogin($req['app_id'], $req['app_secret']);
		if (is_string($ret) === false) {
			CommonUtil::throwException($ret);
		}
		$data = US::getUserData(['app_id'=>$req['app_id'],'app_secret'=>$req['app_secret']]);
		$user_token = md5($data['username'] . substr($data['password'], 0, 3) . rand(0, 99999));
		$user_info = User::getInfoByUserName($data['username']);
		if($user_info->status == "f") {
			CommonUtil::throwException(ErrorEnum::USER_STATUS_OlREADY_FROZEN);
		}
		$old_token = app('redis')->get('api_user_token:'.$user_info['id']);
		if($old_token){
			app('redis')->del('api_user_info:'.$old_token);
		}
		unset($user_info['password']);
		$user_info['site_id'] = Site::getCurrentSiteID();
		/**
		 * @var \Redis $REDIS
		 */
		$REDIS = app('redis');
		$REDIS->set('api_user_info:'.$user_token, json_encode($user_info));
		$REDIS->set('api_user_token:'.$user_info['id'], $user_token);
		return $this->responseJson([ 'access_token' => $user_token ]);
	}

	/**
	 * 获取用户余额
	 */
	public function getBalance(Request $request)
	{
		$user_id = $request->user_id;
		$userBalance = User::getBalance($user_id);
		return $this->responseJson([ 'balance' => $userBalance ]);
	}
}
