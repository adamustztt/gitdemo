<?php


namespace App\Http\Middleware;



use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Http\Utils\LoggerFactoryUtil;
use App\Models\RequestLog;
use App\Models\Site;
use App\Models\SiteDomainModel;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckSiteDomainMiddleware
{
	/**
	 * 根据分站请求域名获取站长信息
	 * Class RequestLogMiddlleware
	 * @package App\Http\Middleware
	 */
	public function handle(Request $request, Closure $next)
	{
		//获取请求域名
		$referer = $request->header('referer');
		$referer = parse_url($referer);
		$referer = $referer['host'];
		$site_info  = Site::getWhereData(["domain"=>$referer]);
		if(!$site_info) {
			$site_id = SiteDomainModel::query()->where(["domian"=>$referer])->value("site_id");
			if($site_id) {
				$site_info  = Site::getById($site_id);
			}
		}
		if ($site_info === false || $site_info === null || $site_info->id == 3) {
			$g_site_id = 1;
		} else {
			if($site_info->status == "f") {
				CommonUtil::throwException(ErrorEnum::ERROR_EXT_SITE_FROZEN);
			}
			$g_site_id = $site_info->id;
		}
		// 将站长UID site_user_id 站长ID site_id 存入request中 避免后端再去查询一次
		$request->merge(["g_site_id"=>$g_site_id,"g_domain"=>$referer]);
		return $next($request);
	}
}
