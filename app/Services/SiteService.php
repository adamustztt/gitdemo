<?php


namespace App\Services;


use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Models\Site;
use App\Models\SiteBalanceLog;

class SiteService
{
	/**
	 * @author ztt
	 * @param int $site_id
	 * @param int $change_amount
	 * @param $context_id
	 * @param $msg
	 * @return bool
	 */
	public  function incrSiteBalance(int $site_id, int $change_amount, $context_id, $msg,$type_name=5) {
		return $this->changeSiteBalance(1,$site_id,$change_amount,$context_id,$msg,$type_name);
	}

	/**
	 * @author ztt
	 * @param $type
	 * @param $site_id
	 * @param $change_amount
	 * @param $context_id
	 * @param $msg
	 * @return bool
	 * @throws \App\Exceptions\ApiException
	 * @throws \Throwable
	 */
	private function changeSiteBalance($type,$site_id,$change_amount,$context_id,$msg="工具利润",$type_name)
	{
		if (!in_array($type, [1, 2])) {
			CommonUtil::throwException(ErrorEnum::DATABASE_HANDLE_ERROR);
		}
		$siteInfo= Site::getByIdLockForUpdate($site_id);
		// 站长流水记录
		$logData["site_id"] = $site_id;
		$logData["context_id"] = $context_id;// 上下文ID
		$logData["before_balance"] = $siteInfo->site_balance;
		$logData["change_balance"] = $change_amount;
		$logData["after_balance"] = $siteInfo->site_balance + $change_amount;
		$logData["status"] = 2; // 1 审核中 2审核通过 3拒绝
		$logData["type"] = 1; // 1：收入   2：支出
		$logData["type_name"] = $type_name; //类型名称   1:微信提现 2:支付宝提现 3:银行卡提现 4:包裹利润 5:工具利润 6：提现退款 7:代理商包裹利润 8:代理商工具利润'
		$logData["remark"] = $msg;
		$insert = SiteBalanceLog::insertSiteBalanceLog($logData);
		if(!$insert) {
			CommonUtil::throwException(ErrorEnum::DATABASE_HANDLE_ERROR);
		}
		//站长金额变动
		if($change_amount>0) {
			$siteInfo->site_balance = $siteInfo->site_balance+$change_amount;
			$update = $siteInfo->saveOrFail();
			if(!$update) {
				CommonUtil::throwException(ErrorEnum::DATABASE_HANDLE_ERROR);
			}
		}
		return true;
	}

	/**
	 * @author ztt
	 * 站长退款
	 * @return bool
	 * @throws \App\Exceptions\ApiException
	 * @throws \Throwable
	 */
	public static function siteRefund($site_id,$context_id,$change_amount,$type_name=4)
	{
		$siteInfo= Site::getByIdLockForUpdate($site_id);
		// 站长流水记录
		$logData["site_id"] = $site_id;
		$logData["context_id"] = $context_id;// 上下文ID
		$logData["before_balance"] = $siteInfo->site_balance;
		$logData["change_balance"] = $change_amount;
		$logData["after_balance"] = $siteInfo->site_balance - $change_amount;
		$logData["status"] = 2; // 1 审核中 2审核通过 3拒绝
		$logData["type"] = 2; // 1：收入   2：支出
		$logData["type_name"] = $type_name; //类型名称  1:微信提现 2:支付宝提现 3:银行卡提现 4:包裹利润 5:工具利润 6：提现退款 7:代理商包裹利润 8:代理商工具利润'
		$logData["remark"] = "取消包裹退款";
		if($type_name ==7) {
			$logData["remark"] = "代理商包裹取消包裹退款";
		}
		$insert = SiteBalanceLog::insertSiteBalanceLog($logData);
		if(!$insert) {
			CommonUtil::throwException(ErrorEnum::SITE_MONEY_LOG_ERROR);
		}
		//站长金额变动
		if($siteInfo->site_balance<$change_amount) {
			CommonUtil::throwException(ErrorEnum::SITE_MONEY_NEGATIVE_ERROR);
		}
		if($change_amount>0) {
			$siteInfo->site_balance = $siteInfo->site_balance-$change_amount;
			$update = $siteInfo->saveOrFail();
			if(!$update) {
				CommonUtil::throwException(ErrorEnum::SITE_MONEY_ERROR);
			}
		}
		return true;
	}
	
}
