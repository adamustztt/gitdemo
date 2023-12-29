<?php

use Illuminate\Support\Facades\DB;

class Site 
{
	public static function getSiteInfo($user_id)
	{
		$sql = 'SELECT * FROM site WHERE user_id = ? LIMIT 1';
		$bind = [ $user_id ];
		return DB::select($sql, $bind)[0];
	}
	
	public static function checkActive($user_id)
	{
		$site_info = self::getSiteInfo($user_id);
		if ($site_info['status'] === SITE_STATUS_NORMAL) {
			return true;
		}
		if ($site_info['status'] === SITE_STATUS_FROZEN) {
			return false;
		}
		return false;
	}
	
	public static function getSiteIDByDomain($domain)
	{
		$sql = 'SELECT id FROM site WHERE domain = ? LIMIT 1';
		$bind = [ $domain ];
		return DB::select($sql, $bind)[0]['id'];
	}
	
	public static function getHOST()
	{
		return $_SERVER['HTTP_HOST'];
	}
	
	public static function getCurrentSiteID()
	{
		return 1;
		// 兼容下测试需要
		$ignore_domains = [ 'localhost', 'local.api.customer.damaijia.com' ];
		if (in_array(self::getHOST(), $ignore_domains)) {
			return 1;
		}
		$domain = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
		if ($domain === null) {
			return 1;
		}
		return self::getSiteIDByDomain($domain);
	}
}
