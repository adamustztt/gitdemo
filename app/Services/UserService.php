<?php


namespace App\Services;

use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Models\User as UserModel;
use App\Models\UserBalanceLog;

class UserService
{
	/**
	 * @author ztt
	 * @param int $user_id
	 * @param int $change_amount
	 * @param $context_id
	 * @param $msg
	 * @throws \App\Exceptions\ApiException
	 * @throws \Throwable
	 */
	public function incrUserBalance(int $user_id, int $change_amount, $context_id, $msg, $change_type="",$platform_total_profit=0,$log_type)
	{
		return $this->changeUserBalance(1,$user_id,$change_amount,$context_id,$msg,$change_type,$platform_total_profit,$log_type);
	}

	/**
	 * @param int $user_id
	 * @param int $change_amount
	 * @param $context_id
	 * @param $msg
	 * @param string $change_type
	 * @param int $platform_total_profit
	 * @param $log_change_type
	 * @param $log_type
	 * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
	 * @throws \App\Exceptions\ApiException
	 * @throws \Throwable
	 */
	public function decrUserBalance(int $user_id, int $change_amount, $context_id, $msg,$change_type="",$platform_total_profit=0,$log_type)
	{
		return $this->changeUserBalance(2,$user_id,$change_amount,$context_id,$msg,$change_type,$platform_total_profit,$log_type);
	}

	/**
	 * @author ztt
	 * @param $type
	 * @param $user_id
	 * @param $change_amount
	 * @param $context_id
	 * @param $msg
	 * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
	 * @throws \App\Exceptions\ApiException
	 * @throws \Throwable
	 */
	private function changeUserBalance($type,$user_id,$change_amount,$context_id,$msg,$change_type="",$platform_total_profit,$log_type)
	{
		if (!in_array($type, [1, 2])) {
			CommonUtil::throwException(ErrorEnum::DATABASE_HANDLE_ERROR);
		}
		$user = UserModel::getByIdLockForUpdate($user_id);
		$log_data['user_id'] = $user_id;
		$log_data['context_id'] = $context_id;
		$log_data['additional'] = $msg;
		$log_data['amount'] = $change_amount;
		$log_data['platform_income'] = $platform_total_profit;
		$log_data['log_type'] = $log_type; // 记录类型 1礼品商城 2流量工具
		if ($type == 1) {
			$log_data['log_change_type']=1;// 变更类型 1收入 2支出
			$log_data['balance'] = $change_amount + $user->balance;
			$log_data['type'] = empty($change_type) ? USER_BALANCE_TYPE_REFUND : $change_type;
		} else if ($type == 2) {
			if ($user->balance - $change_amount < 0) {
				CommonUtil::throwException(ErrorEnum::ERROR_EXT_BALANCE_NOT_ENOUGH);
			}
			$log_data['log_change_type']=2;// 变更类型 1收入 2支出
			$log_data['balance'] = $user->balance - $change_amount;
			$log_data['type'] = USER_BALANCE_TYPE_PAY;
			$user->consume_total = $user->consume_total+$change_amount; //用户累计消费
		}
		$user->balance = $log_data['balance'];
		$user->saveOrFail();
		return UserBalanceLog::create($log_data);
	}
}
