<?php

use Illuminate\Support\Facades\DB;

class PaymentAlipay
{
	public static function getInfoByOrderSN($order_sn)
	{
		$sql = 'SELECT * FROM payment_alipay WHERE order_sn = ? LIMIT 1';
		$bind = [ $order_sn ];
		return DB::selectOne($sql, $bind);
	}
	
	public static function createPrePayment($uid, $amount, $order_sn,$payType)
	{
		$sql = 'INSERT INTO payment_alipay(uid, apply_amount, order_sn, add_time,pay_type)
				VALUES (?, ?, ?, ?,?)';
		$bind = [ $uid, $amount, $order_sn, time(),$payType ];
		return DB::insert($sql, $bind);
	}
	
	public static function changePaidSNByOrderSN($order_sn, $paid_sn)
	{
		$sql = 'UPDATE payment_alipay SET paid_sn = ? WHERE order_sn = ? LIMIT 1';
		$bind = [ $paid_sn, $order_sn ];
		return DB::update($sql, $bind);
	}
	
	public static function success($order_sn, $data)
	{
		
		$sql = 'UPDATE payment_alipay
				SET trade_sn = ?, `desc` = ?, `time` = ?, username = ?, userid = ?, amount = ?, status = ?, is_complete = ?
				WHERE order_sn = ?
				LIMIT 1';
		$bind = [ $data['trade_sn'], $data['desc'], $data['time'], $data['username'], $data['userid'], $data['amount'],
			$data['status'], 1, $order_sn ];
		return DB::update($sql, $bind);
	}
}
