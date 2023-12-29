<?php


namespace App\Http\Controllers;


use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Http\Logic\ToolLogic;
use App\Http\Utils\LoggerFactoryUtil;
use App\Models\Site;
use App\Models\Tool;
use App\Models\ToolOrder;
use App\Models\UserLevelModel;
use App\Models\UserToolLog;
use App\Models\UserToolPrice;
use App\Services\SiteService;
use App\Services\ToolService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tool\ShanTaoTool\ExcelTool;
use Tool\ShanTaoTool\QiWeiTool;

class ToolController extends BaseController
{
	/**
	 * @author ztt
	 * 获取工具价格
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getToolPrice() {
		$params = request()->all();
		$tool_price = $this->getToolSellingPrice($params["id"]);
		$user = $this->_user_info;
		$now_price = $tool_price;
		if($user) {
			$userLevel = UserLevelModel::query()->where(["id"=>$user["level_id"]])->first();
			
			if($userLevel) {
				$now_price = $tool_price-$userLevel->tool_preferential_amount;
			}
		}
		
		return $this->responseJson(["original_price"=>$tool_price,"tool_price"=>$now_price]);
	}

	/**
	 * @author ztt
	 * @param $tool_id
	 * 查询工具价格
	 * @return \Illuminate\Database\Eloquent\HigherOrderBuilderProxy|mixed
	 * @throws \App\Exceptions\ApiException
	 */
	public function getToolSellingPrice($tool_id) {
		$user = $this->_user_info;
		$data = ToolLogic::getUserToolPrice($user["id"],$tool_id);
		return $data->tool_selling_price;
	}
	/**
	 * @author ztt
	 * 猜你喜欢
	 * @return \Illuminate\Http\JsonResponse
	 * @throws \App\Exceptions\ApiException
	 */
	public function createOrderGuessLike() {
		$params = request()->all();
		$toolInfo = Tool::getById($params["id"]);
		if(empty($toolInfo)) {
			CommonUtil::throwException(ErrorEnum::VALIDATE_ERROR);
		}
		$data = $this->getQuery($params["link_url"]);
		if(empty($data["id"])) {
			CommonUtil::throwException(ErrorEnum::ERROR_EXT_INVALID_PRODUCT_ID);
		}
		$tool_link  = $toolInfo->tool_link_before.$data["id"].$toolInfo->tool_link_after;
		
		$user_id = $this->_user_info["id"];
		$amount = $this->getToolSellingPrice($params["id"]);
		$tool_preferential_amount = UserLevelModel::query()
			->where(["id"=> $this->_user_info["level_id"]])
			->value("tool_preferential_amount");
		if($tool_preferential_amount) {
			$site = Site::query()->where("id",$this->_site_id)->first();
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
			// 用户金额变动  用户资金流水
			$userService->decrUserBalance($user_id,$amount,$tool_id,"工具扣款:".$toolInfo->tool_name,"p",0,2);
			// 生成订单
			$order_data["id"] = $toolInfo['id'];
			$order_data["site_id"] = $this->_site_id;
			$order_data["user_id"] = $user_id;
			$order_data["user_name"] = $this->_user_info["mobile"];
			$order_data["keyword"] = $params['keyword'];
			$order_data["generate_link_url"] = $tool_link;
			$order_data["link_url"] = $params['link_url'];
			$order_data["tool_type"] = $toolInfo->tool_type;
			$order_data["tool_id"] = $params["id"];
			$order_data["price"] = $amount;
			$result = ToolOrder::create($order_data);
			if(!$result) {
				QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM")." 用户使用工具生成订单失败",env("CHANNEL_MONEY_POLICY"));
				CommonUtil::throwException(ErrorEnum::INTERNAL_ERROR);
			}
			// 站长利润
			if($this->_site_id>1) {
				$siteService = new SiteService();
				$siteInfo = Site::query()->where("id",$this->_site_id)->first();
				$siteToolPrice  = ToolLogic::getUserToolPrice($siteInfo->user_id,$tool_id);
				$change_amount = $amount-$siteToolPrice->site_price; // 站长工具成本价
				$siteService->incrSiteBalance($this->_site_id,$change_amount,$toolInfo['id'],"工具利润",5);
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
					$this->_site_id.";用户ID：".$user_id.";工具ID:".$tool_id.";错误信息：".$e->getMessage(),env("CHANNEL_MONEY_POLICY"));
			}
			DB::rollBack();
			CommonUtil::throwException([$e->getCode(),$e->getMessage()]);
		}
		DB::commit();
		return $this->responseJson($tool_link); 
	}

	/**
	 * @author ztt
	 * 洋淘秀卡首屏
	 * @return \Illuminate\Http\JsonResponse
	 * @throws \App\Exceptions\ApiException
	 * @throws \Throwable
	 */
	public function createOrderFirstScreen() {
		$params = request()->all();
		$toolInfo = Tool::getById($params["id"]);
		if(empty($toolInfo)) {
			CommonUtil::throwException(ErrorEnum::VALIDATE_ERROR);
		}
		$tool_link  = $toolInfo->tool_link_before.$params["link_url"].$toolInfo->tool_link_after;

		$user_id = $this->_user_info["id"];
		$amount = $this->getToolSellingPrice($params["id"]);
		$tool_preferential_amount = UserLevelModel::query()
			->where(["id"=> $this->_user_info["level_id"]])
			->value("tool_preferential_amount");
		if($tool_preferential_amount) {
			$site = Site::query()->where("id",$this->_site_id)->first();
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
			// 用户金额变动  用户资金流水
			$userService->decrUserBalance($user_id,$amount,$tool_id,"工具扣款:".$toolInfo->tool_name,"p",0,2);
			// 生成订单
			$order_data["id"] = $toolInfo['id'];
			$order_data["site_id"] = $this->_site_id;
			$order_data["user_id"] = $user_id;
			$order_data["user_name"] = $this->_user_info["mobile"];
			$order_data["generate_link_url"] = $tool_link;
			$order_data["link_url"] = $params['link_url'];
			$order_data["tool_type"] = $toolInfo->tool_type;
			$order_data["tool_id"] = $params["id"];
			$order_data["price"] = $amount;
			$result = ToolOrder::create($order_data);
			if(!$result) {
				QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM")." 用户使用工具生成订单失败",env("CHANNEL_MONEY_POLICY"));
				CommonUtil::throwException(ErrorEnum::INTERNAL_ERROR);
			}
			// 站长利润
			if($this->_site_id>1) {
				$siteService = new SiteService();
				$siteInfo = Site::query()->where("id",$this->_site_id)->first();
				$siteToolPrice  = ToolLogic::getUserToolPrice($siteInfo->user_id,$tool_id);
				$change_amount = $amount-$siteToolPrice->site_price; // 站长工具成本价
				$siteService->incrSiteBalance($this->_site_id,$change_amount,$toolInfo['id'],"工具利润",5);
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
					$this->_site_id.";用户ID：".$user_id.";工具ID:".$tool_id.";错误信息：".$e->getMessage(),env("CHANNEL_MONEY_POLICY"));
			}
			DB::rollBack();
			CommonUtil::throwException([$e->getCode(),$e->getMessage()]);
		}
		DB::commit();
		return $this->responseJson($tool_link);
	}
	public function getQuery($url='')
	{
		$url=trim($url);
		if($url=='')
		return false;
		$query=parse_url($url,PHP_URL_QUERY);
		if($query===null)
		return null;
		parse_str($query,$params);
		return $params;
	}

	/**
	 * @author ztt
	 * 关键词卡首屏
	 * @return \Illuminate\Http\JsonResponse
	 * @throws \App\Exceptions\ApiException
	 * @throws \Throwable
	 */
	public function createOrderKeywordFirstScreen() {
		$params = request()->all();
		$toolInfo = Tool::getById($params["id"]);
		if(empty($toolInfo)) {
			CommonUtil::throwException(ErrorEnum::VALIDATE_ERROR);
		}
		$tool = new ToolService();
		$tool_param = ["keyword"=>$params["keyword"],"url"=>$params["link_url"],"data_json"=>$params["data_json"]];
		$tool_req = $tool->requestTool(
			"post",
			$tool_param,
			"/tool/conversions/similar"
		);
		if($tool_req['code'] != 0) {
			$policy['result'] = $tool_req;
			$policy = env("POLICE_FROM")." 用户使用工具生成订单失败".json_encode($policy,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
			$policy_req = QiWeiTool::sendMessageToBaoJing($policy,env("CHANNEL_MONEY_POLICY"));
			$instance = new LoggerFactoryUtil(ToolService::class);
			$instance->info("报警数据" . json_encode($policy_req));
			CommonUtil::throwException(ErrorEnum::ERROR_TOOL_LINK);
		}
		$tool_link = $tool_req["data"];
		$user_id = $this->_user_info["id"];
		$amount = $this->getToolSellingPrice($params["id"]);
		$tool_preferential_amount = UserLevelModel::query()
			->where(["id"=> $this->_user_info["level_id"]])
			->value("tool_preferential_amount");
		if($tool_preferential_amount) {
			$site = Site::query()->where("id",$this->_site_id)->first();
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
			// 用户金额变动  用户资金流水
			$userService->decrUserBalance($user_id,$amount,$tool_id,"工具扣款:".$toolInfo->tool_name,"p",0,2);
			// 生成订单
			$order_data["id"] = $toolInfo['id'];
			$order_data["site_id"] = $this->_site_id;
			$order_data["user_id"] = $user_id;
			$order_data["user_name"] = $this->_user_info["mobile"];
			$order_data["keyword"] = $params['keyword'];
			$order_data["generate_link_url"] = $tool_link;
			$order_data["link_url"] = $params['link_url'];
			$order_data["tool_type"] = $toolInfo->tool_type;
			$order_data["tool_id"] = $params["id"];
			$order_data["price"] = $amount;
			$result = ToolOrder::create($order_data);
			if(!$result) {
				QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM")." 用户使用工具生成订单失败",env("CHANNEL_MONEY_POLICY"));
				CommonUtil::throwException(ErrorEnum::INTERNAL_ERROR);
			}
			// 站长利润
			if($this->_site_id>1) {
				$siteService = new SiteService();
				$siteInfo = Site::query()->where("id",$this->_site_id)->first();
				$siteToolPrice  = ToolLogic::getUserToolPrice($siteInfo->user_id,$tool_id);
				$change_amount = $amount-$siteToolPrice->site_price; // 站长工具成本价
				$siteService->incrSiteBalance($this->_site_id,$change_amount,$toolInfo['id'],"工具利润",5);
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
					$this->_site_id.";用户ID：".$user_id.";工具ID:".$tool_id.";错误信息：".$e->getMessage(),env("CHANNEL_MONEY_POLICY"));
			}
			DB::rollBack();
			CommonUtil::throwException([$e->getCode(),$e->getMessage()]);
		}
		DB::commit();
		return $this->responseJson($tool_link);
	}
	/**
	 * @author ztt
	 * 找相似入口
	 * @return \Illuminate\Http\JsonResponse
	 * @throws \App\Exceptions\ApiException
	 * @throws \Throwable
	 */
	public function createOrderSimilar() {
		$params = request()->all();
		$toolInfo = Tool::getById($params["id"]);
		if(empty($toolInfo)) {
			CommonUtil::throwException(ErrorEnum::VALIDATE_ERROR);
		}
		$tool = new ToolService();
		$tool_param = ["keyword"=>$params["keyword"],"url"=>$params["link_url"]];
		$tool_req = $tool->requestTool(
			"post",
			$tool_param,
			"/tool/conversions/similar"
		);
		if($tool_req['code'] != 0) {
			$policy['result'] = $tool_req;
			$policy = env("POLICE_FROM")." 用户使用工具生成订单失败".json_encode($policy,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
			QiWeiTool::sendMessageToBaoJing($policy,env("CHANNEL_MONEY_POLICY"));
			CommonUtil::throwException(ErrorEnum::ERROR_TOOL_LINK);
		}
		$tool_link = $tool_req["data"];
		$user_id = $this->_user_info["id"];
		$amount = $this->getToolSellingPrice($params["id"]);
		$tool_preferential_amount = UserLevelModel::query()
			->where(["id"=> $this->_user_info["level_id"]])
			->value("tool_preferential_amount");
		if($tool_preferential_amount) {
			$site = Site::query()->where("id",$this->_site_id)->first();
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
			// 用户金额变动  用户资金流水
			$userService->decrUserBalance($user_id,$amount,$tool_id,"工具扣款:".$toolInfo->tool_name,"p",0,2);
			// 生成订单
			$order_data["id"] = $toolInfo['id'];
			$order_data["site_id"] = $this->_site_id;
			$order_data["user_id"] = $user_id;
			$order_data["user_name"] = $this->_user_info["mobile"];
			$order_data["keyword"] = $params['keyword'];
			$order_data["generate_link_url"] = $tool_link;
			$order_data["link_url"] = $params['link_url'];
			$order_data["tool_type"] = $toolInfo->tool_type;
			$order_data["tool_id"] = $params["id"];
			$order_data["price"] = $amount;
			$result = ToolOrder::create($order_data);
			if(!$result) {
				QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM")." 用户使用工具生成订单失败",env("CHANNEL_MONEY_POLICY"));
				CommonUtil::throwException(ErrorEnum::INTERNAL_ERROR);
			}
			// 站长利润
			if($this->_site_id>1) {
				$siteService = new SiteService();
				$siteInfo = Site::query()->where("id",$this->_site_id)->first();
				$siteToolPrice  = ToolLogic::getUserToolPrice($siteInfo->user_id,$tool_id);
				$change_amount = $amount-$siteToolPrice->site_price; // 站长工具成本价
				$siteService->incrSiteBalance($this->_site_id,$change_amount,$toolInfo['id'],"工具利润",5);
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
					$this->_site_id.";用户ID：".$user_id.";工具ID:".$tool_id.";错误信息：".$e->getMessage(),env("CHANNEL_MONEY_POLICY"));
			}
			DB::rollBack();
			CommonUtil::throwException([$e->getCode(),$e->getMessage()]);
		}
		DB::commit();
		return $this->responseJson($tool_link);
	}

	/**
	 * @author ztt
	 * 验号工具
	 * @return \Illuminate\Http\JsonResponse
	 * @throws \App\Exceptions\ApiException
	 * @throws \Throwable
	 */
	public function createOrderSearchplus() {
		$params = request()->all();
		$toolInfo = Tool::getById($params["id"]);
		if(empty($toolInfo)) {
			CommonUtil::throwException(ErrorEnum::VALIDATE_ERROR);
		}
		$tool = new ToolService();
		$tool_param = ["account"=>$params["keyword"],"flow"=>2];
		$tool_req = $tool->requestTool(
			"get",
			$tool_param,
			"/tool/accounts/searchplus"
		);
		if($tool_req['code'] != 0) {
			$policy['result'] = $tool_req;
			$policy = env("POLICE_FROM")." 用户使用工具生成订单失败".json_encode($policy,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
			QiWeiTool::sendMessageToBaoJing($policy,env("CHANNEL_MONEY_POLICY"));
			CommonUtil::throwException(ErrorEnum::ERROR_TOOL_ACCOUNT);
		}
		$tool_data = $tool_req["data"];
		$user_id = $this->_user_info["id"];
		$amount = $this->getToolSellingPrice($params["id"]);
		$tool_preferential_amount = UserLevelModel::query()
			->where(["id"=> $this->_user_info["level_id"]])
			->value("tool_preferential_amount");
		if($tool_preferential_amount) {
			$site = Site::query()->where("id",$this->_site_id)->first();
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
			// 用户金额变动  用户资金流水
			$userService->decrUserBalance($user_id,$amount,$tool_id,"工具扣款:".$toolInfo->tool_name,"p",0,2);
			// 生成订单
			$order_data["id"] = $toolInfo['id'];
			$order_data["site_id"] = $this->_site_id;
			$order_data["user_id"] = $user_id;
			$order_data["user_name"] = $this->_user_info["mobile"];
			$order_data["keyword"] = $params['keyword'];
			$order_data["tool_type"] = $toolInfo->tool_type;
			$order_data["tool_id"] = $params["id"];
			$order_data["price"] = $amount;
			$result = ToolOrder::create($order_data);
			if(!$result) {
				QiWeiTool::sendMessageToBaoJing(env("POLICE_FROM")." 用户使用工具生成订单失败",env("CHANNEL_MONEY_POLICY"));
				CommonUtil::throwException(ErrorEnum::INTERNAL_ERROR);
			}
			// 站长利润
			if($this->_site_id>1) {
				$siteService = new SiteService();
				$siteInfo = Site::query()->where("id",$this->_site_id)->first();
				$siteToolPrice  = ToolLogic::getUserToolPrice($siteInfo->user_id,$tool_id);
				$change_amount = $amount-$siteToolPrice->site_price; // 站长工具成本价
				$siteService->incrSiteBalance($this->_site_id,$change_amount,$toolInfo['id'],"工具利润",5);
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
					$this->_site_id.";用户ID：".$user_id.";工具ID:".$tool_id.";错误信息：".$e->getMessage(),env("CHANNEL_MONEY_POLICY"));
			}
			DB::rollBack();
			CommonUtil::throwException([$e->getCode(),$e->getMessage()]);
		}
		DB::commit();
		return $this->responseJson($tool_data);
	}

	/**
	 * @SWG\Post(
	 *     path="/getBlackNumber",
	 *     tags={"电商工具"},
	 *     summary="照妖镜黑号库",
	 *     description="照妖镜黑号库",
	 *     produces={"application/json"},
	 *     @SWG\Parameter(
	 *         name="body",
	 *         in="body",
	 *         required=true,
	 *          @SWG\Schema(
	 *            @SWG\Property(
	 *                  property="id",
	 *                  type="int",
	 *                  description="工具id 7",
	 *              ),
	 *            @SWG\Property(
	 *                  property="account",
	 *                  type="int",
	 *                  description="账号",
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
	public function getBlackNumber(Request $request)
	{
		$params= $this->validate($request, [
			'account'=>'required',
		]);
		$data = ToolLogic::getBlackNumber($this->_user_info,$this->_site_id);
		return $this->responseJson($data);
	}
	/**
	 * @SWG\Post(
	 *     path="/listToolLog",
	 *     tags={"电商工具"},
	 *     summary="照妖镜黑号库记录",
	 *     description="照妖镜黑号库",
	 *     produces={"application/json"},
	 *     @SWG\Parameter(
	 *         name="body",
	 *         in="body",
	 *         required=true,
	 *          @SWG\Schema(
	 *            @SWG\Property(
	 *                  property="invoke_method",
	 *                  type="string",
	 *                  description="请求方法",
	 *              ),
	 *            @SWG\Property(
	 *                  property="invoke_status",
	 *                  type="int",
	 *                  description="请求状态 1待推送 2推送中 3推送成功 4推送失败",
	 *              ),
	 *            @SWG\Property(
	 *                  property="invoke_type",
	 *                  type="int",
	 *                  description="1单条 2 批量",
	 *              ),
	 *          )
	 *     ),
	 *     @SWG\Response(
	 *         response="200",
	 *         description="success",
	 *         @SWG\Schema(
	 *              @SWG\Property(
	 *                  property="invoke_type",
	 *                  type="string",
	 *                  description="1单条 2 批量"
	 *              ),
	 *              @SWG\Property(
	 *                  property="invoke_status",
	 *                  type="string",
	 *                  description="请求状态 1待推送 2推送中 3推送成功 4推送失败"
	 *              ),
	 *              @SWG\Property(
	 *                  property="complete_time",
	 *                  type="string",
	 *                  description="完成时间"
	 *              ),
	 *              @SWG\Property(
	 *                  property="create_time",
	 *                  type="string",
	 *                  description="创建时间"
	 *              ),
	 *              @SWG\Property(
	 *                  property="account",
	 *                  type="string",
	 *                  description="账号"
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
	public function listToolLog(Request $request)
	{
		$params= $this->validate($request, [
			'invoke_method'=>'required',
		]);
		$data = ToolLogic::listToolLog($this->_user_info["id"]);
		return $this->responseJson($data);
	}
	/**
	 * @SWG\Post(
	 *     path="/getToolDetail",
	 *     tags={"电商工具"},
	 *     summary="照妖镜黑号库记录详情",
	 *     description="照妖镜黑号库详情",
	 *     produces={"application/json"},
	 *     @SWG\Parameter(
	 *         name="body",
	 *         in="body",
	 *         required=true,
	 *          @SWG\Schema(
	 *            @SWG\Property(
	 *                  property="id",
	 *                  type="string",
	 *                  description="记录ID",
	 *              ),
	 *          )
	 *     ),
	 *     @SWG\Response(
	 *         response="200",
	 *         description="success",
	 *         @SWG\Schema(
	 *              @SWG\Property(
	 *                  property="account",
	 *                  type="string",
	 *                  description="账号"
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
	public function getToolDetail(Request $request)
	{
		$params= $this->validate($request, [
			'id'=>'required',
		]);
		$data = ToolLogic::getToolDetail();
		return $this->responseJson($data);
	}
	public function exportToolOrder(Request $request)
	{
		$params = $this->validate($request, [
			"ids" => "required",
		]);
		$time = time()-3600;
		$data = UserToolLog::query()->whereIn("id",$params["ids"])->get()->toArray();
		$cellData = [];
		foreach ($data as $k=>$v) {
			$result_data = json_decode($v["result_data"],true);
			$cellData[$k]["create_time"] =$v["create_time"];
			$cellData[$k]["product_name"] =$result_data["account"]."******";
			if(strtotime($v["complete_time"]) >$time) {
				$user_type = $result_data["user_type"] == 1 ? "未实名" : "以实名";
				$vip_level = $result_data["vip_level"] == 10 ? "超级会员" : "普通会员";
				$cellData[$k]["user_type"] =$user_type." ".$vip_level." ".$result_data["vip"];
				$cellData[$k]["sex"] =$result_data["sex"];
				$cellData[$k]["tao_age"] =$result_data["tao_age"];
				$cellData[$k]["buyer_total_point"] =$result_data["buyer_total_point"];
				$cellData[$k]["is_seller"] =$result_data["is_seller"]==0 ? "未开店" : $result_data["seller_total_point"];
				$cellData[$k]["buyer_good_rate"] =$result_data["week_order"];
				$cellData[$k]["week_order"] =$result_data["week_order"];
				$cellData[$k]["register_time"] =$result_data["register_time"];
				$cellData[$k]["escape_num"] =$result_data["escape_num"];
				$cellData[$k]["harass_num"] =$result_data["harass_num"];
				$cellData[$k]["liar_num"] =$result_data["liar_num"];
				$cellData[$k]["ps_num"] =$result_data["ps_num"];
				$cellData[$k]["prnt_num"] =$result_data["prnt_num"];
				$cellData[$k]["dpower_num"] =$result_data["dpower_num"];
				$cellData[$k]["nearWeekShop"] =$result_data["nearWeekShop"];
			} else {
				$cellData[$k]["user_type"] = "已超时";
			}
			
		}
		$head = ["查询时间","买家","账号状态","性别","淘龄","买家信誉","商店信誉","好评率","买家总周平均","注册日期",
			"兔子","蜜罐","狐狸","鳄鱼","老鼠","降权处理","近一周查询商家",];
		ExcelTool::exportToExcel($head,$cellData,"黑号库记录");
	}
	
	
	public function iteminfo1688()
	{
		
	}
}
