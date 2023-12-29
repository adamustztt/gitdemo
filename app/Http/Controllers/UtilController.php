<?php


namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Tool\ShanTaoTool\UploadTool;

class UtilController extends BaseController
{
	/**
	 * 文件上传
	 */
	public function fileUpload(Request $request)
	{
		$file = $request->file('file');
		$url =  UploadTool::uploadFile($file);
		return $this->responseJson(["url"=>$url]);
	}
}
