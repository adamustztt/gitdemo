<?php


namespace App\Enums;


class UserBalanceLogEnum
{
	// c:充值 p:支出 r:取消订单退回金额 i：后台添加 d：后台减少',
	const CHANGE_TYPE = [
		"c" => "充值",
		"p" => "支出",
		"r" => "退款",
		"i" => "管理员添加",
		"d" => "管理员扣减",
	];
}
