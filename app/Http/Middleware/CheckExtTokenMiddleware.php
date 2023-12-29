<?php
/**
 * 认证API用户登录中间件
 */

namespace App\Http\Middleware;

use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Http\Utils\LoggerFactoryUtil;
use App\Models\User;
use Closure;
use Base;
use Site;

class CheckExtTokenMiddleware
{
	/**
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		$access_token = $request->header('Authorization', null);
		$user_info = app('redis')->get('api_user_info:'.$access_token);
		if ($access_token === null || $user_info === false) {
		    if(!$access_token){
                CommonUtil::throwException(ErrorEnum::ERROR_EXT_AUTH_FAILED);
            }
		    //缓存中不存在,有可能是使用新的密钥进行请求
            $user = User::query()->where("api_key",$access_token)->first();
            if($user){
                // 将user_id 存入request中 避免后端再去查询一次
                $request->merge([ 'user_id' => $user->id, 'site_id' => $user->site_id ]);

                //设置日志需要的用户ID
                LoggerFactoryUtil::setUserId($user->id);
                return $next($request);
            }
			CommonUtil::throwException(ErrorEnum::ERROR_EXT_AUTH_FAILED);
		}
		//缓存中存在,则说明是老方式token调用

//		$user_info = User::getUserInfoByAccessToken($access_token);

		if ($user_info === null || $user_info === false) {
			CommonUtil::throwException(ErrorEnum::ERROR_EXT_AUTH_FAILED);
		}
		$user_info = json_decode($user_info);
		// 将user_id 存入request中 避免后端再去查询一次
		$request->merge([ 'user_id' => $user_info->id, 'site_id' => $user_info->site_id ]);

		//设置日志需要的用户ID
        LoggerFactoryUtil::setUserId($user_info->id);
		return $next($request);
	}
}
