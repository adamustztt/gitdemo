<?php

class RequestUtil
{
	public static function checkAssetId()
	{
		return Param::IS_STRING . Param::regexMatch('/^[0-9a-zA-Z#\-_]{1,48}$/');
	}

	public static function checkBorrowingId()
	{
		return Param::IS_STRING . Param::regexMatch('/^[0-9a-zA-Z#\-_]{1,48}$/');
	}

	public static function checkContractId()
	{
		return Param::IS_STRING . Param::regexMatch('/^[0-9a-zA-Z#\-_]{1,48}$/');
	}

	public static function checkRepayId()
	{
		return Param::IS_STRING . Param::regexMatch('/^[0-9a-zA-Z#\-_]{1,48}$/');
	}

	public static function checkLeaseId()
	{
		return Param::IS_STRING . Param::regexMatch('/^[0-9a-zA-Z#\-_]{1,48}$/');
	}

	public static function checkConsumerId()
	{
		return Param::IS_STRING . Param::regexMatch('/^[0-9a-zA-Z#\-_]{1,48}$/');
	}

	public static function checkLesseeId()
	{
		return Param::IS_STRING . Param::regexMatch('/^[0-9a-zA-Z#\-_]{1,48}$/');
	}

	public static function checkLenderName()
	{
		return Param::IS_STRING . Param::regexMatch('/^[0-9a-zA-Z#\-_]{1,48}$/');
	}

	public static function checkSN()
	{
		return Param::IS_STRING . Param::regexMatch('/^[0-9a-zA-Z#\-_\/]{1,32}$/');
	}

	public static function checkLicenseCode()
	{
		return Param::IS_STRING . Param::func('Util::checkLicense');
	}

	public static function checkRequestId()
	{
		return Param::IS_STRING . Param::regexMatch('/^[0-9a-zA-Z\-_#]{1,32}$/');
	}

	public static function checkQQ()
	{
		return Param::IS_INT . Param::regexMatch('/^\d{5,11}$/');
	}

	public static function checkEmail()
	{
		return Param::IS_STRING . Param::regexMatch('/^[0-9a-zA-Z\-_\.]+@[0-9a-zA-Z\-_]+(\.[0-9a-zA-Z\-_]+)+$/');
	}

	public static function checkTelephone()
	{
		return Param::IS_STRING . Param::regexMatch('/^(0\d{2,3}-)?[1-9]\d{7}$/');
	}

	public static function checkCompanyBankcard()
	{
		return Param::IS_STRING . Param::regexMatch('/^[0-9a-zA-Z\-_#]{1,32}$/');
	}

	/**
	 * @param int $max_len
	 * @return string
	 */
	public static function safeAnsi($max_len)
	{
		return Param::regexMatch('/^[a-zA-Z0-9_#\-\/]{1,' . $max_len . '}$/');
	}

	/**
	 * @param int $max_len
	 * @return string
	 */
	public static function safeString($max_len)
	{
		return Param::regexMatch('/^[^<>]{1,' . $max_len . '}$/i');
	}
}
