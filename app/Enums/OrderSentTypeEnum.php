<?php


namespace App\Enums;


class OrderSentTypeEnum
{
	/*订单发送方式*/
	const HAND = 1; //手工下单发货
	const BATCH = 2; //批量下单发货
	const API= 3; //api下单发货
}
