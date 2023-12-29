<?php


namespace App\Http\Controllers;


use App\Models\Site;
use App\Models\SiteBalanceLog;
use App\Models\SiteWebSitting;
use App\Services\SiteService;
use Illuminate\Http\Request;

class SiteController extends  BaseController
{
	public function getSiteInfo() {
		$data = Site::getById($this->_site_id);
		//判断站点是否设置轮播图,没有则使用官方的
        if($data["rotation_list"]){
            $lists = explode(",",$data["rotation_list"]);
            $data["rotation_list"] = explode(",",$data["rotation_list"]);

            $temp = [];
            foreach ($lists as $list){
                $temp[] = [
                    "img_url"=>$list,
                    "href"=>""
                ];
            }
            $data["rotation_list"] = $temp;
        }else{
            $data["rotation_list"] = [
                [
                  "img_url"=>"https://pic.rmb.bdstatic.com/bjh/e451c7db5da8286841cc38a9491ba79e.gif",
                  "href"=>""

                ],
                [
                    "img_url"=>"https://pic.rmb.bdstatic.com/bjh/49b7ae7a2a472f0efbda0dfdcca5c7a9.png",
                    "href"=>"/user/info/vipcenter"
                ],
                [
                    "img_url"=>"https://pic.rmb.bdstatic.com/bjh/aaf57d9cace853b5ddea39fa50e13d22.png",
                    "href"=>"/tool/blackLibrary"
                ]
            ];
        }
		return $this->responseJson([
			'data' => $data
		]);
	}
	public function siteBalance()
	{
		$data = SiteBalanceLog::query()->join("order_consignee","order_consignee.id","=","site_balance_log.context_id")
			->where("site_balance_log.type",1)->where("site_balance_log.type_name",4)->where("order_consignee.status","c")
			->select("site_balance_log.site_id","site_balance_log.context_id","site_balance_log.change_balance")->get();
		foreach ($data as $k=>$v) {
			SiteService::siteRefund($v->site_id,$v->context_id,$v->change_balance);
		}
	}
}
