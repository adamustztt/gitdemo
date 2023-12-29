<?php


namespace App\Http\Logic;


use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Models\Site;
use App\Models\Tool;
use App\Models\ToolOrder;
use App\Models\User;
use App\Models\UserLevelModel;
use App\Models\UserToolLog;
use App\Models\UserToolPrice;
use App\Services\SiteService;
use App\Services\Tool\YinliuheTool;
use App\Services\UserService;
use Illuminate\Support\Facades\DB;
use Tool\ShanTaoTool\QiWeiTool;

class ToolLogic extends BaseLogic
{
	/**
	 * 获取用户工具售价
	 * @author ztt
	 * @param $user_id
	 * @param $tool_id
	 * @param $multiple
	 * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
	 * @throws \App\Exceptions\ApiException
	 */
	public static function getUserToolPrice($user_id,$tool_id)
	{
		$user = User::query()->where("id",$user_id)->first();
		if(empty($user)) {
			CommonUtil::throwException(ErrorEnum::USER_NOT_EXISTS);
		}
		$data = UserToolPrice::query()->where(["tool_id"=>$tool_id,"user_id"=>$user_id])->first();
		if($data) {
			return $data;
		}
		$site = Site::query()->where("id",$user->site_id)->first();
		$user_id = $site->user_id;
		$data = UserToolPrice::query()->where(["tool_id"=>$tool_id,"user_id"=>$user_id])->first();
		if(empty($data)) {
			CommonUtil::throwException(ErrorEnum::ERROR_TOOL_PRICE);
		}
		return $data;
	}

	public static function getBlackNumber($userInfo,$site_id)
	{
		$params = app("request")->all();
		$toolInfo = Tool::getById($params["id"]);
		if(empty($toolInfo)) {
			CommonUtil::throwException(ErrorEnum::VALIDATE_ERROR);
		}
		$tool = new YinliuheTool();
		$tool_param = ["wangwang"=>$params["account"],"flow"=>1];
		$tool_data = $tool->requestTool(
			"post",
			$tool_param,
			"/api/v1/outer/tool/get_black_number"
		);
		$user_id = $userInfo["id"];
		$amount = self::getToolSellingPrice($params["id"],$userInfo);
		$tool_preferential_amount = UserLevelModel::query()
			->where(["id"=> $userInfo["level_id"]])
			->value("tool_preferential_amount");
		if($tool_preferential_amount) {
			$site = Site::query()->where("id",$site_id)->first();
			$site_cost_price = UserToolPrice::query()->where(["tool_id"=>$params["id"],"user_id"=>$site->user_id])->value("site_price");
			if(($amount - $tool_preferential_amount)<$site_cost_price) {
				CommonUtil::throwException(ErrorEnum::ERROR_SITE_TOOL_COST_PRICE);
			}
			$amount = $amount - $tool_preferential_amount;
		}
		$tool_id = $toolInfo->id;
		$userService = new userService();
		DB::beginTransaction();
		try {
			$log_map["user_id"] = $userInfo["id"];
			$log_map["site_id"] = $site_id;
			$log_map["invoke_params"] = json_encode(["account"=>$params["account"]]);
			$log_map["invoke_status"] = 3;
			$log_map["tool_id"] = $params["id"];
			$log_map["invoke_method"] = "getBlackNumber";
			$log_map["complete_time"] = date("Y-m-d H:i:s");
			$log_map["invoke_type"] = 1;
			$log_map["result_data"] = json_encode($tool_data);
			UserToolLog::create($log_map);
			// 用户金额变动  用户资金流水
			$userService->decrUserBalance($user_id,$amount,$tool_id,"工具扣款:".$toolInfo->tool_name,"p",0,2);
			// 生成订单
			$order_data["id"] = $toolInfo['id'];
			$order_data["site_id"] =$site_id;
			$order_data["user_id"] = $user_id;
			$order_data["user_name"] = $userInfo["mobile"];
			$order_data["keyword"] = $params['keyword'];
			$order_data["tool_type"] = $toolInfo->tool_type;
			$order_data["tool_id"] = $params["id"];
			$order_data["price"] = $amount;
			$result = ToolOrder::create($order_data);
			// 站长利润
			if($site_id>1) {
				$siteService = new SiteService();
				$siteInfo = Site::query()->where("id",$site_id)->first();
				$siteToolPrice  = ToolLogic::getUserToolPrice($siteInfo->user_id,$tool_id);
				$change_amount = $amount-$siteToolPrice->site_price; // 站长工具成本价
				$siteService->incrSiteBalance($site_id,$change_amount,$toolInfo['id'],"工具利润",5);
				if($siteInfo->parent_id > 1) { // 判断改站长是否有上级站长
					$upSiteInfo = Site::query()->where("id",$siteInfo->parent_id)->first();
					$upSiteToolPrice  = ToolLogic::getUserToolPrice($upSiteInfo->user_id,$tool_id);
					$upChange_amount = $siteToolPrice->site_price-$upSiteToolPrice->site_price; // 上级站长工具成本价 = 站长成本价-上级站长成本价
					$siteService->incrSiteBalance($siteInfo->parent_id,$upChange_amount,$toolInfo['id'],"代理商工具利润",8);
				}
			}
		} catch (\Exception $e) {
			if($e->getCode() != 209) {
				QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM")." 用户使用工具扣款失败  站长ID：".
					$site_id.";用户ID：".$user_id.";工具ID:".$tool_id.";错误信息：".$e->getMessage());
			}
			DB::rollBack();
			CommonUtil::throwException([$e->getCode(),$e->getMessage()]);
		}
		DB::commit();
		return $tool_data;
	}
	/**
	 * @author ztt
	 * @param $tool_id
	 * 查询工具价格
	 * @return \Illuminate\Database\Eloquent\HigherOrderBuilderProxy|mixed
	 * @throws \App\Exceptions\ApiException
	 */
	public static function getToolSellingPrice($tool_id,$user) {
		$data = ToolLogic::getUserToolPrice($user["id"],$tool_id);
		return $data->tool_selling_price;
	}

	public static function listToolLog($user_id)
	{
		$params = app("request")->all();
		$query = UserToolLog::query()->where(["invoke_method"=>$params["invoke_method"],"user_id"=>$user_id])->orderBy("id","desc");
		if(!empty($params["invoke_status"])) {
			$query->where("invoke_status",$params["invoke_status"]);
		}
		$date = date("Y-m-d H:i:s",time()-3600*24*30*3);
		$query->where("create_time",">",$date);
		if(!empty($params["invoke_type"])) {
			$query->where("invoke_type",$params["invoke_type"]);
		}
		$page = $params["page"] ?? 1;
		$pageSize = $params["pageSize"] ?? 10;
		$count = $query->count();
		$list = $query->select("id","invoke_params","invoke_type","invoke_status","complete_time","create_time")
			->offset(($page-1) * $pageSize)->limit($pageSize)->get();
		foreach ($list as $k=>$v) {
			$list[$k]["account"] = json_decode($v["invoke_params"],true)["account"];
		}
		return ["count"=>$count,"list"=>$list];
	}

	public static function getToolDetail()
	{
		$params = app("request")->all();
		$data = UserToolLog::getById($params["id"]);
		$date = date("Y-m-d H:i:s");
		$data["account"] = json_decode($data["invoke_params"],true)["account"];
		if(strtotime($data->complete_time) < strtotime($date)-3600) {
			return $data["account"];
		}
		return $data;
	}
}
