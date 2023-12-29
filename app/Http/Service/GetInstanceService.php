<?php


namespace App\Http\Service;


class GetInstanceService
{
	private function __construct()
	{
		echo 12;
	}
	public static $_instance;
	public static function getInstance()
	{
		dd(212);
		if(!(self::$_instance instanceof self)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
}
