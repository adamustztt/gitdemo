<?php

use Symfony\Component\HttpFoundation\File\File;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

class QiNiu
{
	public static function upload(File $file)
	{
	
		$file_path = date('Y/m/d/h/m/s/');
		$filename = rand(0, 99999) . '.' . $file->getExtension();
		$auth = new Auth(config('qiniu.access_key'), config('qiniu.secret_key'));
		$token = $auth->uploadToken(config('qiniu.bucket'));
		$upload_manager = new UploadManager();
		list($ret, $err) = $upload_manager->putFile($token, $filename, $file_path);
		return [ 'ret' => $ret, 'err' => $err ];
	}
}
