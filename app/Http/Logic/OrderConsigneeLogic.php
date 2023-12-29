<?php


namespace App\Http\Logic;


use App\Models\OrderConsignee;
use Illuminate\Support\Carbon;
use Tool\ShanTaoTool\QiWeiTool;

class OrderConsigneeLogic extends BaseLogic
{
	/**
	 * @author ztt
	 * @param $express_no
	 * @param $package_id
	 * @return bool
	 * @throws \Tool\ShanTaoTool\Exception\QiWeiException
	 * 检查近三十天单号是否重复
	 */
	public static function checkExpressNo($express_no,$package_id)
	{
		$data = OrderConsignee::query()
			->where("express_no",$express_no)
			->where("id","!=",$package_id)
			->where('create_time', '>', date("Y-m-d H:i:s",time()-30*24*60*60))
			->get();
		
		if($data->count()) {
			$package_ids[] = $package_id;
			foreach ($data as $k=>$v) {
				$package_ids[] = $v["id"];
			}
			$policy["预警原因"]="快递单号重复";
			$policy["重复快递单号"]=$express_no;
			$policy["重复包裹id"]=implode(",",$package_ids);
			$policy["预警时间"]=date("Y-m-d H:i:s");
//			$policy_code = env("POLICE_CODE");
			$policy_code = env("CHANNEL_MONEY_POLICY");
			QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM").json_encode($policy,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),$policy_code);
		}
		return true;
	}
}
