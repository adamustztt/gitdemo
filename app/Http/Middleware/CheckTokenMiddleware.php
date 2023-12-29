<?php

namespace App\Http\Middleware;

use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Http\Utils\LoggerFactoryUtil;
use Closure;
use Base;
class CheckTokenMiddleware
{
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		$token = $request->header('X-TOKEN', null);
		$user_info = app('redis')->get('user_info:'.$token);
		if ($user_info === false || $user_info === null) {
			CommonUtil::throwException(ErrorEnum::ERROR_INVALID_TOKEN);
		}
		$request->merge(['user_info' => json_decode($user_info, true)]);

//        $instance = new LoggerFactoryUtil(CheckExtTokenMiddleware::class);
//        $instance->info("用户信息:".$user_info);

        //设置日志需要的用户ID
        $user_info = json_decode($user_info, true);
        LoggerFactoryUtil::setUserId($user_info["id"]);
		return $next($request);
	}
}
