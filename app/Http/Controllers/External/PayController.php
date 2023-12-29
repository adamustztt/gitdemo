<?php

namespace App\Http\Controllers\External;

use App\Helper\CommonUtil;
use App\Enums\ErrorEnum;
use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Laravel\Lumen\Routing\Controller;
use Base;
use Param;
use RechargeRecord;
use UserBalanceLog;
use UserOrder;
use PaymentAlipay;
use User;

class PayController extends BaseController
{

	public function notify()
	{
		$post = $_POST;
		if (isset($post['order_sn']) === false) {
			CommonUtil::throwException(ErrorEnum::DATA_NOT_EXIST);
		}

		//判断是否存在订单记录
//		$payment_alipay = PaymentAlipay::getInfoByOrderSN($post['order_sn']);
        $payment_alipay = \App\Models\PaymentAlipay::query()->where("order_sn",$post['order_sn'])->first();
		if ($payment_alipay === null || $payment_alipay === []) {
            return $this->responseJson();//不存在直接返回成功
//			CommonUtil::throwException(ErrorEnum::DATA_NOT_EXIST);
		}

		$payment_alipay11 = \App\Models\PaymentAlipay::query()->where("trade_sn",$post['trade_sn'])->first();
		if ($payment_alipay11) {
			return $this->responseJson();//存在直接返回成功
//			CommonUtil::throwException(ErrorEnum::DATA_NOT_EXIST);
		}
		
		//判断该订单是否已完成
        if($payment_alipay["is_complete"] == 1){
            return $this->responseJson();//完成直接返回成功
        }

        $lockKey = "paynotify:".$post['order_sn'];
        $lockFlag = Redis::setnx($lockKey,1);
        if($lockFlag){
            //加锁成功
            //给锁设置过期时间
            Redis::setex($lockKey,120,1);
            //开启事务
            DB::beginTransaction();
            $pay_type = 0;
            if(isset($post["order_type"]) && $post["order_type"]==1){
                //微信支付
                $pay_type = 1;
            }
            //1.更新订单状态
            $ret = \App\Models\PaymentAlipay::query()->where("order_sn",$post['order_sn'])->limit(1)->update(
                [
                    "trade_sn"=>$post["trade_sn"],
                    "desc"=>$post["desc"],
                    "time"=>$post["time"],
                    "username"=>$post["username"],
                    "userid"=>$post["userid"],
                    "amount"=>$post["reality_amount"],
                    "status"=>$post["status"],
                    "is_complete"=>1,
                    "update_time"=>$post["time"],
                    "pay_type"=>$pay_type
                ]
            );
            if(!$ret){
                //更新失败
                Redis::del($lockKey);
                DB::rollBack();
                CommonUtil::throwException(ErrorEnum::DATA_EXIST_PAY);
            }
            //2.成功之后充值余额
            $addRes = User::balanceChargeInternal($payment_alipay['uid'], $post['reality_amount'] * 100);
            if(!$addRes){
                //余额充值失败
                Redis::del($lockKey);
                DB::rollBack();
                CommonUtil::throwException(ErrorEnum::ERROR_UPDATE_USER);
            }
            //3.添加支付记录
            $addRechargeRes = RechargeRecord::addInternal($payment_alipay['uid'], RECHARGE_PAY_TYPE_ALIPAY, $post['trade_sn'], $post['reality_amount'] * 100);
            if(!$addRechargeRes){
                //支付记录添加失败
                Redis::del($lockKey);
                DB::rollBack();
                CommonUtil::throwException(ErrorEnum::ERROR_ADD_RECHARGE);
            }
            //4.添加余额变动记录
            $balance = User::getBalanceForLock($payment_alipay['uid']);
            $addBalanceLogRes = UserBalanceLog::addInternal($payment_alipay['uid'], $balance, USER_BALANCE_TYPE_CHARGE,
                $post['reality_amount'] * 100, $payment_alipay['id'], '充值，订单号：' . $post['trade_sn']);
            if($addBalanceLogRes===false){
                //余额变动记录添加失败
                Redis::del($lockKey);
                DB::rollBack();
                CommonUtil::throwException(ErrorEnum::ERROR_ADD_BALANCE_LOG);
            }
            //提交事务
            Redis::del($lockKey);
            DB::commit();
        }

		return $this->responseJson();
	}
}
