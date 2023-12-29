<?php


namespace App\Http\Controllers\Cron;


use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Http\Controllers\BaseController;
use App\Http\Utils\LoggerFactoryUtil;
use App\Models\PaymentAlipay;
use App\Models\UserOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use RechargeRecord;
use Taoxiangpay\Taoxiangpay;
use User;
use UserBalanceLog;

class CronPayController extends BaseController
{

    /**
     * 定时任务获取未支付订单的支付结果
     */
    public function getOrderPayResult()
    {
        //获取最近10条待支付订单
        $orders = PaymentAlipay::query()->where("is_complete",0)->whereNotNull("paid_sn")->orderBy("payment_id","desc")->limit(5)->get();
        if($orders){
            //存在则请求接口获取支付状态
            $queryData = [];
            foreach ($orders as $order){
                $queryData[] = [
                    "order_sn"=>$order["order_sn"],
                    "channel_paid_sn"=>$order["paid_sn"]
                ];
            }
            //批量查询订单状态
            if($queryData){
                $config = [
                    "token"=>config('app.taoxiang_token'),
                    "url"=>"http://pay.gatheraccount.com/api/terminal/bill_state",
                    "data"=>$queryData
                ];
                $results = Taoxiangpay::batchQueryPayInfo($config);
                $instance = new LoggerFactoryUtil(CronPayController::class);
                $instance->info("获取支付回调结果:".json_encode($results));
                if($results){
                    foreach ($results as $key=>$result){
                        //判断是否成功
                        if($result["code"]==0){
                            if($result["data"]["pay_status"]=="d"){
                                $payData = [
                                    "reality_amount"=>$result["data"]["reality_amount"],
                                    "trade_sn"=>$result["data"]["type_paid_sn"],
                                    "desc"=>$result["data"]["channel_paid_sn"],
                                    "username"=>$result["data"]["payer"],
                                    "userid"=>"*",	
                                    "amount"=>$result["data"]["amount"],
                                    "status"=>"转账",
                                    "pay_status"=>$result["data"]["pay_status"],
                                    "time"=>$result["data"]["time"],
                                    "order_sn"=>$key
                                ];
                                $this->handleOrder($payData);
                            }
                        }
                    }
                }
            }
        }
        return $this->responseJson();
    }


    /**
     * 处理已支付订单
     * @param array $post 已支付订单数据
     */
    private function handleOrder($post)
    {
        //判断是否存在订单记录
        $payment_alipay = PaymentAlipay::query()->where("order_sn",$post['order_sn'])->first();
        if ($payment_alipay === null || $payment_alipay === []) {
            CommonUtil::throwException(ErrorEnum::DATA_NOT_EXIST);
        }
        //判断该订单是否已完成
        if($payment_alipay["is_complete"] == 1){
            CommonUtil::throwException(ErrorEnum::DATA_EXIST_PAY);
        }

        $lockKey = "paynotify:".$post['order_sn'];
        $lockFlag = Redis::setnx($lockKey,1);
        if($lockFlag){
            //加锁成功
            //给锁设置过期时间
            Redis::setex($lockKey,120,1);
            //开启事务
            DB::beginTransaction();
            //1.更新订单状态
            $ret = PaymentAlipay::query()->where("order_sn",$post['order_sn'])->limit(1)->update(
                [
                    "trade_sn"=>$post["trade_sn"],
                    "desc"=>$post["desc"],
                    "time"=>$post["time"],
                    "username"=>$post["username"],
                    "userid"=>$post["userid"],
                    "amount"=>$post["reality_amount"],
                    "status"=>$post["status"],
                    "is_complete"=>1,
                    "update_time"=>$post["time"]
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
    }
}
