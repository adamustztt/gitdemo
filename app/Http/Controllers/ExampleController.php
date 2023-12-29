<?php
/**
 * Created by PhpStorm.
 * User: wzz
 * Date: 2020/9/16
 * Time: 16:55
 */

namespace App\Http\Controllers;


use App\Enums\ErrorEnum;
use App\Exceptions\ApiException;
use App\Helper\CommonUtil;
use App\Helper\WhereUtil;
use App\Http\Bean\Utils\CustomExpress\YundaCreateBmOrderBean;
use App\Http\Utils\CustomExpress\YunDaExpressUtil;
use App\Models\LevelPrice;
use App\Models\UserOrder;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExampleController extends BaseController
{
	
	public function test(Request $request)
	{
		$param = [
		    "orderNumber"=>time(),
            "sendName"=>"测试",
            "sendProvince"=>"浙江省",
            "sendCity"=>"杭州市",
            "sendCountry"=>"江干区",
            "sendAddress"=>"创制绿谷",
            "receiveName"=>"收件人",
            "receiveProvince"=>"江西省",
            "receiveCity"=>"上饶市",
            "receiveCountry"=>"鄱阳县",
            "receiveAddress"=>"鄱阳心谁说",
            "receivePhone"=>"18887898765"
        ];
		$bean = new YundaCreateBmOrderBean($param);

        $res = YunDaExpressUtil::createBmOrder($bean);
        return $this->responseJson($res);
	}

	/**
	 * @author ztt
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getList(Request $request)
	{
		$data = $this->validate($request, [
			'order_sn' => 'string',
			'filter.name' => 'string',
			'filter.age' => 'string',
			'mobile' => 'phone',
			'update_at' => 'date_array',
			'create_time' => 'date_array',
		]);
		$where = [];
		$whereUtil = new WhereUtil($data,$where);
		$whereUtil->applyFilter('filter.name','username');
//		$whereUtil->applyFilter('mobile');
		$whereUtil->applyDateFilter('create_time');

		$list = UserOrder::listPage(['*'],$where,1);
		

		return $this->responseJson($list);
	}
}
