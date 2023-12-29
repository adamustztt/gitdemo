<?php
namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Http\Logic\RechargeRecordLogic;
use Base;
use Composer\Package\Archiver\BaseExcludeFilter;
use Illuminate\Http\Request;
use Param;
use Taoxiangpay\Taoxiangpay;
use Tool\ShanTaoTool\Bean\Pay\GetCodePayUrlParamBean;
use Tool\ShanTaoTool\HttpCurl;
use Tool\ShanTaoTool\PayTool;
use UserBalanceLog;
use Filter;
use RechargeRecord;
use PaymentAlipay;
use HTTP;
use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
class RechargeRecordController extends BaseController
{
//	public function query()
//	{
//		$req = Base::getRequestJson();
//		Base::checkAndDie([
//			'trade_sn' => Param::IS_STRING . ERROR_INVALID_RECHARGE_TRADE_SN,
//			'amount' => Param::IS_INT_AMOUNT. ERROR_INVALID_AMOUNT,
//			'pay_type' => Param::isOP('RECHARGE_PAY_TYPE_') . ERROR_INVALID_CHANNEL
//		], $req);
//		$filter = [
//			Filter::makeDBFilter('trade_sn', $req['trade_sn'], Filter::TYPE_EQUAL)
//		];
//		$recharge_info = RechargeRecord::getList($filter, [ 0, 1 ])[0];
//		if ($recharge_info !== null) {
//			Base::dieWithResponse();
//		}
//		$ret = RechargeRecord::addInternal($this->_user_info['id'], $req['pay_type'], $req['trade_sn'], $req['amount']);
//		if ($ret === false) {
//			Base::dieWithError(ERROR_INVALID_RECHARGE_TRADE_SN);
//		}
//		Base::dieWithResponse();
//	}
	public function query(Request $request)
	{
		$params = $this->validate($request, [
			'pay_type'=>'required',
			'amount'=>'required',
			'trade_sn'=>'required',
		]);
		
		$req = RechargeRecordLogic::query($params["trade_sn"],$params["amount"],$this->_user_info);
		return $this->responseJson($req);
		
	}

	/**
	 * 申请支付二维码
	 */
	public function applyPaymentCode()
	{
        $req = request()->all();
		$uid = $this->_user_info['id'];
		$proxy_site_id = $this->_user_info['site_id'];
		$order_sn = 'mz' . date('Ymd') . $uid . $proxy_site_id . time();
		$payType = 0;
        if(isset($req["pay_type"])&&$req["pay_type"]=="wechat"){
            $payType = 1;
        }
        $ret = PaymentAlipay::createPrePayment($uid, $req['amount']/100, $order_sn,$payType);
        if ($ret === false) {
            CommonUtil::throwException(ErrorEnum::INTERNAL_ERROR);
        }
		//判断支付类型
//        if(isset($req["pay_type"])&&$req["pay_type"]=="wechat"){
//            //微信支付
//            $param = [
//                'orderPrice' => intval($req['amount']),
//                'accountId' => 5,
//                'relationOrderNumber' => $order_sn,
//                "payType"=>1
//            ];
//            $res = HttpCurl::postCurl(env("OFFICIAL_PAY_URL")."v1/codePayUrl",$param);
//            if(!$res["status"]){
//                throw new ApiException([2,"系统错误"]);
//            }
//            $paid_sn = $res['data']['order_number'];
//            $data = [
//                'qr' => $res['data']['qrcode_url'],
//                'order_sn' => $order_sn
//            ];
//        }else{
//            //支付宝支付
//            //修改为composer包调用
//            $config = [
//                'amount' => $req['amount'],
//                'order_sn' => $order_sn,
//                'token' => config('app.taoxiang_token'),
//                "url"=>config('app.taoxiang_url')
//            ];
//            $resp = Taoxiangpay::generateQRcode($config);
//            $paid_sn = $resp['channel_paid_sn'];
//            $data = [
//                'qr' => $resp['qr_code'],
//                'order_sn' => $order_sn
//            ];
//        }
		$params["appKey"] = env("PAY_APP_KEY");
		$params["appSecret"] = env("PAY_APP_SECRET");
		$params["orderPrice"] = $req['amount'];
		$params["relationOrderNumber"] = $order_sn;
		$params["payType"] = ($payType == 1) ? 1 : 2;
		$GetCodePayUrlParamBean = new GetCodePayUrlParamBean($params);
//	  	$api_req = PayTool::getCodePayUrl($GetCodePayUrlParamBean);
	  	$api_req = PayTool::getCodePayUrlV3($GetCodePayUrlParamBean);
		$paid_sn = $api_req["order_number"];
		PaymentAlipay::changePaidSNByOrderSN($order_sn, $paid_sn);
		$data = [
                'qr' => $api_req['qrcode_url'],
                'order_sn' => $paid_sn,
				'remark'=>$api_req['remark'],
                "pay_type"=>$payType,
                "pay_son_type"=>$api_req["pay_son_type"]??2
            ];
		return $this->responseJson($data);
	}
	
	public function queryRechargeStatus()
	{
		$order_sn = $_GET['order_sn'];
		$info = PaymentAlipay::getInfoByOrderSN($order_sn);
		if ($info === null) {
			//Base::dieWithError(ERROR_INVALID_DATA, '支付单号不存在');
			CommonUtil::throwException(ErrorEnum::DATA_NOT_EXIST);
			
			
		}
		if ($info['is_complete'] !== 1) {
			Base::dieWithResponse([
				'finish' => false
			]);
		}
		Base::dieWithResponse([
			'finish' => true
		]);
	}
	/**
	 * @SWG\Get(
	 *     path="/getPayType",
	 *     tags={"充值"},
	 *     summary="获取支持充值方式",
	 *     description="获取支持充值方式",
	 *     produces={"application/json"},
	 *     @SWG\Response(
	 *         response="200",
	 *         description="success",
	 *         @SWG\Schema(
	 *              @SWG\Property(
	 *                  property="alipay",
	 *                  type="boolean ",
	 *                  description="true支持false不支持"
	 *              ),
	 *              @SWG\Property(
	 *                  property="wechat",
	 *                  type="boolean ",
	 *                  description="true支持false不支持"
	 *              ),
	 *          )
	 *     ),
	 *     @SWG\Response(
	 *         response="403",
	 *         description="fail",
	 *         @SWG\Schema(ref="#/definitions/ErrorBean")
	 *     )
	 * )
	 */
	public function getPayType()
	{
		$appKey= env("PAY_APP_KEY");
		$appSecret = env("PAY_APP_SECRET");
		$api_result = PayTool::getPayMethod($appKey,$appSecret);
		return $this->responseJson($api_result);
	}
}
