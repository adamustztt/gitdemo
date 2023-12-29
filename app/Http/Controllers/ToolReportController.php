<?php


namespace App\Http\Controllers;

use App\Enums\ErrorEnum;
use App\Helper\CommonUtil;
use App\Models\ToolReport;

class ToolReportController extends BaseController
{
	public function addToolReport() {
		$params = request()->all();
		$data["account"] = $params["account"];
		$data["content"] = $params["content"];
		$data["img"] = implode("###",$params["img"]);
		$data["type"] = implode(",",$params["type"]);
		$data["user_id"] = $this->_user_info["id"];
		$req = ToolReport::create($data);
		if(!$req) {
			CommonUtil::throwException(ErrorEnum::INTERNAL_ERROR);
		}
		return $this->responseJson();
	}
}
