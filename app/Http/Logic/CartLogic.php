<?php


namespace App\Http\Logic;


use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Http\Utils\LoggerFactoryUtil;
use App\Models\ExpressProductModel;
use App\Models\ExpressWarehouseModel;
use App\Models\Site;
use App\Models\UserShopModel;
use App\Services\Vtool\ErpService;
use Curl\Curl;
use Tool\ShanTaoTool\HttpCurl;

class CartLogic extends BaseLogic
{
	/**
	 * 计算订单价格
	 * @param $siteId
	 */
	public static function computeAmountV1($siteId)
	{

	}

	/**
	 * @param $siteId
	 * @param $upSiteId
	 * @param $product_id
	 * @author ztt
	 * 计算站长利润
	 */
	public static function computeSiteProfit($siteId, $upSiteId, $productId, $warehouseId)
	{
		//1.获取用户信息
		$siteInfo = Site::query()->where("id", $siteId)->first();
		$upSiteInfo = Site::query()->where("id", $upSiteId)->first();
		if (empty($siteInfo) || empty($upSiteInfo)) {
			CommonUtil::throwException(ErrorEnum::ERROR_SITE_INFO);
		}
		//2.获取该商品的发货地ID
		$expressProduct = ExpressProductModel::query()->where("product_id", $productId)->first();
		if (!$expressProduct) {
			CommonUtil::throwException(ErrorEnum::ERROR_EXPRESS_SEND);
		}
		//3.获取发货地仓库的基本价
		$baseExpressWarehouse = ExpressWarehouseModel::query()->where("user_id", 0)
			->where("warehouse_id", $warehouseId)->where("express_id", $expressProduct["damaijia_express_id"])
			->first();
		if (!$baseExpressWarehouse) {
			CommonUtil::throwException(ErrorEnum::ERROR_BASE_EXPRESS_SEND);
		}
		$damaijia_express_id = $expressProduct["damaijia_express_id"];
		$siteExpressInfo = ExpressWarehouseModel::query()
			->where(["user_id" => $siteInfo->user_id, "express_id" => $damaijia_express_id])->first();
		if (empty($siteExpressInfo)) {
			CommonUtil::throwException(ErrorEnum::ERROR_SITE_INFO);
		}
		$upSiteExpressInfo = ExpressWarehouseModel::query()
			->where(["user_id" => $upSiteInfo->user_id, "express_id" => $damaijia_express_id])->first();
		if (empty($upSiteExpressInfo)) {
			CommonUtil::throwException(ErrorEnum::ERROR_SITE_INFO);
		}
		return $siteExpressInfo->site_price - $upSiteExpressInfo->site_price;
	}

	/*
	 * 验证订单号是否支持拼多多发货
	 */
	public static function checkPlatformOrderSnIsSupportPddShopDeliver($platform_order_sn, $shop_id)
	{
		$pdd_domain = env("PDD_ERP_DOMAIN");
		$url = "/api/v1/api/getOrderByShopIdAndOrderSn";
		$uid = env("AT_VTOOL_PROJECT_USER_ID");
		$project_id = env("PROJECT_ID");
		$params = [
			"project_id" => $project_id,
			"uid" => md5($uid),
			"shop_id" => $shop_id,
			"order_sn" => $platform_order_sn
		];
		$req = HttpCurl::postCurl($pdd_domain . $url, $params);
		if (isset($req["data"]) && !empty($req["data"])) {
			return true;
		}
		return false;
	}

	/*
	 * 验证订单号是否支持tb发货
	 */
	public static function checkPlatformOrderSnIsSupportTbShopDeliver($platform_order_sn, $shop_id)
	{
		try {
			$shop = UserShopModel::query()
				->where("is_delete",0)
				->where("shop_id", $shop_id)->first();
			$callback_params = $shop["callback_params"];
			$callback_params = json_decode($callback_params, true);
			$sellerNick = $callback_params["sellernick"] ?? "";
			$code = $callback_params["code"] ?? "";
			$params = [
				'sellernick' => $sellerNick,
				"code" => $code,
				"shop_id" => $shop_id,
				"tid" => $platform_order_sn,
				"fields" => "seller_nick, buyer_nick, title, type, created, tid, seller_rate,buyer_flag, buyer_rate, status, payment, adjust_fee, post_fee, total_fee, pay_time, end_time, modified, consign_time, buyer_obtain_point_fee, point_fee, real_point_fee, received_payment, commission_fee, buyer_memo, seller_memo, alipay_no,alipay_id,buyer_message, pic_path, num_iid, num, price, buyer_alipay_no, receiver_name, receiver_state, receiver_city, receiver_district, receiver_address, receiver_zip, receiver_mobile, receiver_phone,seller_flag, seller_alipay_no, seller_mobile, seller_phone, seller_name, seller_email, available_confirm_fee, has_post_fee, timeout_action_time, snapshot_url, cod_fee, cod_status, shipping_type, trade_memo, is_3D,buyer_email,buyer_area",
			];
			$url = "/api/damajia/erp/tool/tb/getOrderInfo";
			$domain = env("QIANTAI_VTOOL");
			$req = HttpCurl::postCurl($domain . $url, $params);
			$log = new LoggerFactoryUtil(CartLogic::class);
			$log->info("vtool返回结果".json_encode($req));

			if (
				isset($req["data"]["status"])
				&&
				($req["data"]["status"] == "WAIT_SELLER_SEND_GOODS"))
			{
				return true;
			}
			return false;
		} catch (\Exception $e) {
//			dd($params, $e->getMessage());
			return false;
		}

	}
	/*
	 * 验证订单号是否支持ks发货
	 */
	public static function checkPlatformOrderSnIsSupportKsShopDeliver($platform_order_sn, $shop_id)
	{
		$ks_domain = env("KUAISHOU_DOMAIN");
		$url = "/api/v1/api/getOrderByShopIdAndOrderSn";
		$params = [
			"oid" => $platform_order_sn,
			"shop_id" => $shop_id,
		];
		$req = HttpCurl::postCurl($ks_domain . $url, $params);
		if (isset($req["data"]) && !empty($req["data"])) {
			return true;
		}
		return false;

	}

	/**
	 * @param $platform_order_sn
	 * @param $shop_id
	 * @return array
	 * 验证抖音订单
	 */
	public static function checkPlatformOrderSnIsSupportDyShopDeliver($platform_order_sn, $shop_id)
	{
		$domain = env("DOUYIN_DOMAIN");
		$url = "/api/dy/order/detail";
		$params = [
			"order_number" => $platform_order_sn,
			"shop_id" => $shop_id,
		];
		$req = HttpCurl::postCurl($domain . $url, $params);
		$log = new LoggerFactoryUtil(CartLogic::class);
		$log->info("请求参数：".json_encode($params));
		$log->info("url：".$domain.$url);
		$log->info("请求返回：".json_encode($req));
		if(isset($req["data"]["order_status"]) && $req['data']['order_status'] == 2){
			return ['status'=>true,'msg'=>'匹配成功'];
		}
		return ['status'=>false,'msg'=>'订单号错误或不是待发货订单'];
	}
}
