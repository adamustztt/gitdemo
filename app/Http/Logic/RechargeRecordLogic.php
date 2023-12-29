<?php


namespace App\Http\Logic;


use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Http\Utils\LoggerFactoryUtil;
use App\Models\PaymentAlipay;
use App\Models\UserBalanceLog;
use App\Services\UserService;
use Illuminate\Support\Facades\DB;
use Tool\ShanTaoTool\PayTool;

class RechargeRecordLogic extends BaseLogic
{
	public static function query($trade_sn,$amount,$userInfo) {
		$uid = $userInfo['id'];
		$site_id = $userInfo["site_id"];
		$token = env("TAOXIANG_TOKEN");
		$paymentAlipayLog = PaymentAlipay::query()->where(["uid"=>$uid,"trade_sn"=>$trade_sn,"is_complete"=>1])->first();
		if($paymentAlipayLog) {
			CommonUtil::throwException(ErrorEnum::ERROR_SUBMIT_PAY_NO);
		}
		$req = PayTool::getOfficialOrderByOrderNumber($token,$trade_sn,$amount);
		$log = new LoggerFactoryUtil(RechargeRecordLogic::class);
		$log->info(json_encode($req));
		$msg = "充值，订单号：".$req["order_sn"];
		$price = $req["price"];
		DB::beginTransaction();
		try{
			$map["trade_sn"] = $trade_sn;
			$map["desc"] = "alipay";
			$map["username"] = "支付宝支付";
			$map["userid"] = 1;
			$map["amount"] = $price/100;
			$map["is_complete"] = 1;
			$map["status"] = "交易成功";
			$map["time"] = date("Y-m-d H:i:s");
			$map["add_time"] = time();
			$map["uid"] = $uid;
			$map["paid_sn"] = $req["order_sn"];
			$map["order_sn"] = $order_sn = 'mz' . date('Ymd') . $uid . $site_id . time();
			$map["apply_amount"] = $price/100;
			$map["pay_type"] = 0;
			$paymentAlipay = PaymentAlipay::create($map);
			$userService = new UserService();
			$result = $userService->incrUserBalance($uid,$price,$paymentAlipay->payment_id,$msg,"c",0,4);
			DB::commit();
		} catch (\Exception $e) {
			DB::rollBack();
			CommonUtil::throwException([100000,$e->getMessage()]);
		}
		return true;
	}
}
