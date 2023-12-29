<?php
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use App\Models\UserBalanceLog as UBL;

class UserBalanceLog
{

	/**
	 * 获取列表
	 * @param array $filter
	 * @param array $range
	 * @param array $sort
	 * @return array
	 */
	public static function getList($filter = null, $range = null, $sort = null)
	{
		$bind = [];
		$sql = 'SELECT user_balance_log.*, user.username, user.mobile
				FROM user_balance_log
				INNER JOIN user ON user.id = user_balance_log.user_id
				' . DBHelper::getFilterSQLs('WHERE', $filter, $bind) . '
				' . DBHelper::getSortSQLs($sort, $bind) . '
				' . DBHelper::getRangeSQL($range);
		return DB::select($sql, $bind);
	}
	public static function getCount($filter = null)
	{
		$bind = [];
		$sql = 'SELECT COUNT(*) AS total
				FROM user_balance_log
				INNER JOIN user ON user.id = user_balance_log.user_id
				' . DBHelper::getFilterSQLs('WHERE', $filter, $bind);
		return DB::select($sql, $bind)[0]['total'];
	}
	
	public static function addInternal($user_id, $balance, $type, $amount, $context_id, $additional = null)
	{
		$insert = [
			'user_id'=>$user_id,'balance'=>$balance,'type'=>$type,'amount'=>$amount,'context_id'=>$context_id,
			'additional'=>$additional
		];
		$res = UBL::userBalanceLogCreate($insert);
		if(!$res){
			return false;
		}
//		$sql = 'INSERT INTO user_balance_log (user_id, balance, type, amount, context_id, additional)
//				VALUES (?, ?, ?, ?, ?, ?)';
//		$bind = [ $user_id, $balance, $type, $amount, $context_id, $additional ];
//		return DB::insert($sql, $bind);
	}
}
