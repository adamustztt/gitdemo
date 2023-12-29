<?php

/**
 * 实现一个简单日志类
 */
class SimpleLog
{
	public static function info($msg)
	{
		self::write($msg);
	}

	public static function error($msg)
	{
		self::write($msg);
	}

	public static function warning($msg)
	{
		self::write($msg);
	}

	public static function notice($msg)
	{
		self::write($msg);
	}

	public static function write($msg, $file_name = null)
	{
		if ($file_name === null) {
			$file_name = self::DEFAULT_LOG_FILE;
		} else {
			$file_name = __DIR__ . '/../../storage/logs/' . $file_name;
		}
		file_put_contents($file_name, '【' . date('Y-m-d H:m:s') . '】：' . $msg . PHP_EOL, FILE_APPEND);
	}


	const DEFAULT_LOG_FILE = __DIR__ . '/../../storage/logs/app.log';
}
