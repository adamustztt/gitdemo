<?php


namespace App\Services\Shop;



abstract class xAbstractShop
{
	protected $requestParams;
	protected $respond;
	abstract protected function requestQueryOrder($shopId,$tid,$third_user_id);
	public function queryOrder($shopId,$tid,$third_user_id='')
	{
		return $this->requestQueryOrder($shopId,$tid,$third_user_id);
	}
}
